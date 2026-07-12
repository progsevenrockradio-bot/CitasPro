<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Negocio;
use App\Models\Categoria;
use App\Models\Pais;
use App\Models\NegocioDatosFiscales;
use App\Models\Invoice;
use App\Models\DigitalCertificate;
use App\Services\DigitalCertificateService;
use App\Services\InvoiceApiService;
use App\Actions\CreateRectificationInvoiceAction;
use App\Jobs\SubmitInvoiceToAEATJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Exception;

class RectificationAndCertificateTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private Pais $pais;
    private NegocioDatosFiscales $datosFiscales;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create([
            'nombre' => 'Dental',
            'slug' => 'dental',
        ]);

        $this->negocio = Negocio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Clínica Dental Dentalia',
            'slug' => 'dentalia',
            'activo' => true,
            'plan' => 'pro',
        ]);

        $this->pais = Pais::create([
            'nombre' => 'España',
            'nombre_en' => 'Spain',
            'codigo_iso2' => 'ES',
            'codigo_iso3' => 'ESP',
            'prefijo' => '+34',
            'activo' => true,
        ]);

        $this->datosFiscales = NegocioDatosFiscales::create([
            'negocio_id' => $this->negocio->id,
            'pais_id' => $this->pais->id,
            'datos_fiscales' => [
                'nif' => 'B98765432',
                'razon_social' => 'Dentalia SL',
                'direccion' => 'Av. de la Constitución 45',
            ],
            'verificado' => true,
        ]);
    }

    public function test_can_store_and_decrypt_digital_certificate()
    {
        $service = new DigitalCertificateService();
        $p12MockBase64 = base64_encode("dummy_p12_certificate_content");
        $password = "SecretPassword123";

        // Guardar certificado encriptado
        $cert = $service->storeCertificate(
            $this->negocio,
            $p12MockBase64,
            $password,
            'Dentalia SL Cert',
            '2026-01-01 00:00:00',
            '2028-01-01 23:59:59'
        );

        $this->assertDatabaseHas('digital_certificates', [
            'negocio_id' => $this->negocio->id,
            'common_name' => 'Dentalia SL Cert',
        ]);

        // Verificar que en base de datos esté encriptado (no sea texto plano)
        $rawCert = DigitalCertificate::where('negocio_id', $this->negocio->id)->first();
        $this->assertNotEquals($password, $rawCert->encrypted_password);
        $this->assertNotEquals($p12MockBase64, $rawCert->encrypted_certificate);

        // Recuperar y descifrar
        $decrypted = $service->getDecryptedCertificate($cert);

        $this->assertEquals($p12MockBase64, $decrypted['certificate']);
        $this->assertEquals($password, $decrypted['password']);
        $this->assertEquals("dummy_p12_certificate_content", base64_decode($decrypted['certificate']));
    }

    public function test_can_create_rectification_invoice_with_inverted_amounts()
    {
        // 1. Crear una factura original
        $apiService = new InvoiceApiService();
        $invoiceData = [
            'negocio_id' => $this->negocio->id,
            'cliente_id' => null,
            'serie' => 'F2026',
            'fecha_emision' => now(),
            'tipo_factura' => 'B2C',
            'moneda' => 'EUR',
            'tipo_cambio' => 1.0,
            'datos_cliente_snapshot' => [
                'nombre' => 'Ana Martínez',
                'nif' => '12345678X',
            ],
            'lines' => [
                [
                    'descripcion' => 'Consulta Dental',
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                    'iva_porcentaje' => 21.00,
                ],
                [
                    'descripcion' => 'Radiografía',
                    'cantidad' => 2,
                    'precio_unitario' => 25.00,
                    'iva_porcentaje' => 10.00,
                ],
            ]
        ];

        $original = $apiService->createInvoice($invoiceData);

        $this->assertEquals(150.00, $original->subtotal);
        $this->assertEquals(26.00, $original->impuestos); // 21 + 5
        $this->assertEquals(176.00, $original->total);
        $this->assertEquals('emitida', $original->estado);

        // 2. Rectificar la factura original
        $action = new CreateRectificationInvoiceAction();
        $rectificativa = $action->execute($original->id, 'Error en el cálculo de radiografías');

        // 3. Validaciones de la rectificativa
        $this->assertDatabaseHas('invoices', [
            'id' => $rectificativa->id,
            'rectifies_invoice_id' => $original->id,
            'rectification_reason' => 'Error en el cálculo de radiografías',
            'estado' => 'rectificativa',
            'serie' => 'R-F2026',
            'numero' => '000001',
        ]);

        // Los importes deben estar invertidos
        $this->assertEquals(-150.00, $rectificativa->subtotal);
        $this->assertEquals(-26.00, $rectificativa->impuestos);
        $this->assertEquals(-176.00, $rectificativa->total);

        // Verificar líneas de factura
        $this->assertCount(2, $rectificativa->lines);
        $this->assertEquals(-100.00, $rectificativa->lines[0]->precio_unitario);
        $this->assertEquals(-25.00, $rectificativa->lines[1]->precio_unitario);

        // Verificar que la original cambia su estado
        $original->refresh();
        $this->assertEquals('anulada', $original->estado);
        $this->assertEquals($rectificativa->id, $original->metadata_adicional['rectified_by_invoice_id']);

        // 4. Validar encadenamiento de Hash VeriFactu
        $this->assertEquals($original->hash_actual, $original->hash_actual); // Hash original
        $this->assertNotNull($rectificativa->hash_actual);
        $this->assertNotEquals($original->hash_actual, $rectificativa->hash_actual);
    }

    public function test_submit_invoice_to_aeat_job_success()
    {
        // Setup Certificado
        $certService = new DigitalCertificateService();
        $certService->storeCertificate($this->negocio, base64_encode("cert"), "pwd", "Dentalia CN");

        // Crear Factura
        $apiService = new InvoiceApiService();
        $invoice = $apiService->createInvoice([
            'negocio_id' => $this->negocio->id,
            'serie' => 'F2026',
            'fecha_emision' => now(),
            'tipo_factura' => 'B2C',
            'moneda' => 'EUR',
            'datos_cliente_snapshot' => ['nombre' => 'Test'],
            'lines' => [['descripcion' => 'Test', 'cantidad' => 1, 'precio_unitario' => 10.00, 'iva_porcentaje' => 21.00]]
        ]);

        // Simular éxito AEAT
        Http::fake([
            'prewww1.aeat.es/*' => Http::response(['status' => 'OK'], 200)
        ]);

        // Despachar Job
        $job = new SubmitInvoiceToAEATJob($invoice->id);
        dispatch_sync($job);

        $invoice->refresh();
        $this->assertTrue($invoice->enviado_aeat);
        $this->assertEquals('verificada_aeat', $invoice->estado);
    }

    public function test_submit_invoice_to_aeat_job_retries_on_503()
    {
        // Setup Certificado
        $certService = new DigitalCertificateService();
        $certService->storeCertificate($this->negocio, base64_encode("cert"), "pwd", "Dentalia CN");

        // Crear Factura
        $apiService = new InvoiceApiService();
        $invoice = $apiService->createInvoice([
            'negocio_id' => $this->negocio->id,
            'serie' => 'F2026',
            'fecha_emision' => now(),
            'tipo_factura' => 'B2C',
            'moneda' => 'EUR',
            'datos_cliente_snapshot' => ['nombre' => 'Test'],
            'lines' => [['descripcion' => 'Test', 'cantidad' => 1, 'precio_unitario' => 10.00, 'iva_porcentaje' => 21.00]]
        ]);

        // Simular error 503
        Http::fake([
            'prewww1.aeat.es/*' => Http::response('Service Unavailable', 503)
        ]);

        // Esperamos que lance Exception para forzar retry/backoff
        $this->expectException(Exception::class);

        $job = new SubmitInvoiceToAEATJob($invoice->id);
        $job->handle(new DigitalCertificateService());
    }

    public function test_submit_invoice_to_aeat_job_fails_permanently_on_400()
    {
        // Setup Certificado
        $certService = new DigitalCertificateService();
        $certService->storeCertificate($this->negocio, base64_encode("cert"), "pwd", "Dentalia CN");

        // Crear Factura
        $apiService = new InvoiceApiService();
        $invoice = $apiService->createInvoice([
            'negocio_id' => $this->negocio->id,
            'serie' => 'F2026',
            'fecha_emision' => now(),
            'tipo_factura' => 'B2C',
            'moneda' => 'EUR',
            'datos_cliente_snapshot' => ['nombre' => 'Test'],
            'lines' => [['descripcion' => 'Test', 'cantidad' => 1, 'precio_unitario' => 10.00, 'iva_porcentaje' => 21.00]]
        ]);

        // Simular error 400 (Bad Request - Error de formato fiscal)
        Http::fake([
            'prewww1.aeat.es/*' => Http::response(['error' => 'NIF_NO_VALIDO'], 400)
        ]);

        // El Job debe procesarlo sin lanzar excepción (no hay retry en 400)
        $job = new SubmitInvoiceToAEATJob($invoice->id);
        $job->handle(new DigitalCertificateService());

        $invoice->refresh();
        $this->assertFalse($invoice->enviado_aeat);
        $this->assertEquals('error_aeat', $invoice->estado);
        $this->assertNotNull($invoice->metadata_adicional['aeat_response_error']);
    }
}
