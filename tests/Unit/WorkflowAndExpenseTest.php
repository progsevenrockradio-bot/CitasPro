<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Negocio;
use App\Models\Categoria;
use App\Models\NegocioDatosFiscales;
use App\Events\AppointmentCompleted;
use App\Actions\ProcessExpenseAction;
use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class WorkflowAndExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_completed_workflow_creates_and_signs_invoice()
    {
        // Desactivar eventos para configurar datos manualmente
        Event::fake([
            // Podemos dejar los listeners reales activos o probarlos directamente
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Peluquería',
            'slug' => 'peluqueria',
        ]);

        $negocio = Negocio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Negocio Test SL',
            'slug' => 'negocio-test-sl',
            'activo' => true,
            'plan' => 'free',
        ]);
        
        $pais = \App\Models\Pais::create([
            'nombre' => 'España',
            'nombre_en' => 'Spain',
            'codigo_iso2' => 'ES',
            'codigo_iso3' => 'ESP',
            'prefijo' => '+34',
            'activo' => true,
        ]);

        NegocioDatosFiscales::create([
            'negocio_id' => $negocio->id,
            'pais_id' => $pais->id,
            'datos_fiscales' => [
                'nif' => 'B12345678',
                'razon_social' => 'Negocio Test SL',
                'direccion' => 'Calle Falsa 123',
            ],
            'verificado' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@example.com',
            'telefono' => '600123456',
            'nif' => '12345678Z',
            'activo' => true,
        ]);

        $servicio = Servicio::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Corte de Pelo',
            'precio' => 20.00,
            'iva_porcentaje' => 21.00,
            'activo' => true,
            'type' => 'general',
        ]);

        $profesional = \App\Models\Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Profesional',
            'apellido' => 'Test',
            'activo' => true,
        ]);

        $cita = Cita::create([
            'negocio_id' => $negocio->id,
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'profesional_id' => $profesional->id,
            'fecha' => now(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'duracion_min' => 60,
            'estado' => 'completada',
            'precio_total' => 20.00,
            'moneda' => 'EUR',
            'type' => 'general',
            'codigo_referencia' => 'CITA-TEST-001',
        ]);

        // Ejecutar los listeners de manera secuencial para probar su comportamiento real
        $event = new AppointmentCompleted($cita);

        // 1. Generar Factura
        /** @var \App\Listeners\GenerateInvoice $generateListener */
        $generateListener = app(\App\Listeners\GenerateInvoice::class);
        $generateListener->handle($event);

        // Validar factura creada
        $invoice = Invoice::where('negocio_id', $negocio->id)
            ->whereJsonContains('metadata_adicional->cita_id', $cita->id)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals('B2B', $invoice->tipo_factura);
        $this->assertEquals(24.20, $invoice->total);

        // 2. Firmar con VeriFactu
        /** @var \App\Listeners\SignInvoiceWithVeriFactu $signListener */
        $signListener = app(\App\Listeners\SignInvoiceWithVeriFactu::class);
        $signListener->handle($event);

        $invoice->refresh();
        $this->assertTrue($invoice->enviado_aeat);
        $this->assertEquals('verificada_aeat', $invoice->estado);

        // 3. Enviar correo al cliente
        /** @var \App\Listeners\SendInvoiceToClient $sendListener */
        $sendListener = app(\App\Listeners\SendInvoiceToClient::class);
        $sendListener->handle($event);
    }

    public function test_process_expense_action_calculates_deductibility_and_investment()
    {
        $categoria = Categoria::create([
            'nombre' => 'Peluquería',
            'slug' => 'peluqueria-gasto',
        ]);

        $negocio = Negocio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Negocio Test SL',
            'slug' => 'negocio-test-sl-gasto',
            'activo' => true,
            'plan' => 'free',
        ]);

        $action = new ProcessExpenseAction();

        // Caso 1: Gasto ordinario <= 300€, afectación 100%
        $expense1 = $action->execute([
            'negocio_id' => $negocio->id,
            'concepto' => 'Gasto de papelería',
            'categoria' => 'Explotación',
            'subtotal' => 100.00,
            'iva_porcentaje' => 21.00,
            'afectacion_porcentaje' => 100.00,
        ]);

        $this->assertFalse($expense1->es_bien_inversion);
        $this->assertEquals(100.00, $expense1->importe_deducible);
        $this->assertEquals(21.00, $expense1->impuestos);
        $this->assertEquals(121.00, $expense1->total);

        // Caso 2: Gasto Home Office con deducibilidad del 30% sobre el porcentaje de afectación (ej. 15%)
        $expense2 = $action->execute([
            'negocio_id' => $negocio->id,
            'concepto' => 'Factura de Internet',
            'categoria' => 'Home Office',
            'subtotal' => 50.00,
            'iva_porcentaje' => 21.00,
            'afectacion_porcentaje' => 15.00, // 15% de la casa afecta al negocio
        ]);

        // Cálculo esperado: 50 * 0.15 (afectación) * 0.30 (regla deducibilidad) = 2.25
        $this->assertEquals(2.25, $expense2->importe_deducible);

        // Caso 3: Bien de inversión > 300€
        $expense3 = $action->execute([
            'negocio_id' => $negocio->id,
            'concepto' => 'Ordenador portátil',
            'categoria' => 'Inversión',
            'subtotal' => 1200.00,
            'iva_porcentaje' => 21.00,
            'afectacion_porcentaje' => 100.00,
        ]);

        $this->assertTrue($expense3->es_bien_inversion);
        $this->assertEquals(1200.00, $expense3->importe_deducible);
    }
}
