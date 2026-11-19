<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\FichaClinica;
use App\Models\Negocio;
use App\Models\OtpCode;
use App\Models\Portafolio;
use App\Models\Profesional;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityAuthAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_profesional_2fa_requiere_challenge_y_flujos_legitimos_con_y_sin_2fa(): void
    {
        $cat = Categoria::create(['nombre' => 'General', 'slug' => 'general', 'activo' => true]);
        $negocio = Negocio::create(['nombre' => 'Clinica A', 'slug' => 'clinica-a', 'categoria_id' => $cat->id, 'activo' => true]);

        // A) Acceso legítimo SIN 2FA
        $profSin2fa = Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Luis',
            'apellido' => 'Sin2FA',
            'email' => 'sin2fa@example.com',
            'password' => Hash::make('secreta123'),
            'telefono' => '+34611111110',
            'rol' => 'dueño',
            'doble_factor_activo' => false,
            'activo' => true,
        ]);

        $resSin2fa = $this->postJson('/api/auth/login-contrasena', [
            'email' => $profSin2fa->email,
            'password' => 'secreta123',
        ]);
        $resSin2fa->assertOk()->assertJsonStructure(['token']);
        $this->assertFalse((bool) ($resSin2fa->json('requiere_2fa') ?? false));

        // B) Profesional CON 2FA
        $prof = Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Ana',
            'apellido' => 'Con2FA',
            'email' => 'ana2fa@example.com',
            'password' => Hash::make('secreta123'),
            'telefono' => '+34611111111',
            'rol' => 'dueño',
            'doble_factor_activo' => true,
            'canal_preferido_2fa' => 'email',
            'activo' => true,
        ]);

        // 1. Pedir OTP directo sin contraseña debe ser rechazado (403)
        $resEnviar = $this->postJson('/api/auth/otp/enviar', [
            'email' => $prof->email,
            'canal' => 'email',
        ]);
        $this->assertSame(403, $resEnviar->status());

        // 2. OTP sin challenge_token no permite iniciar sesión (403)
        $otpSinChallenge = OtpCode::crearParaEmail($prof->email, '2fa', '127.0.0.1');
        $resSinChallenge = $this->postJson('/api/auth/otp/verificar', [
            'email' => $prof->email,
            'codigo' => $otpSinChallenge->plain_codigo,
        ]);
        $this->assertSame(403, $resSinChallenge->status());
        $this->assertArrayNotHasKey('token', $resSinChallenge->json());

        // 3. Flujo legítimo: login-contrasena emite challenge_token único asociado al usuario
        $resLogin = $this->postJson('/api/auth/login-contrasena', [
            'email' => $prof->email,
            'password' => 'secreta123',
        ]);
        $resLogin->assertOk()->assertJson(['requiere_2fa' => true])->assertJsonStructure(['challenge_token']);
        $challengeToken = $resLogin->json('challenge_token');

        $otp2fa = OtpCode::where('email', $prof->email)->where('usado', false)->latest('id')->first();
        $this->assertNotNull($otp2fa);
        $this->assertSame('2fa', $otp2fa->tipo);
        $this->assertSame($prof->id, $otp2fa->profesional_id);

        // Obtener plain_codigo desde Mail::fake() no es necesario si probamos un OTP creado con challenge conocido o capturamos el último;
        // verificamos primero rechazo con challenge incorrecto
        $otpTest = OtpCode::crearParaEmail(
            email: $prof->email,
            tipo: '2fa',
            ip: '127.0.0.1',
            challengeHash: OtpCode::hashValor($challengeToken),
            profesionalId: $prof->id
        );
        $resWrongChallenge = $this->postJson('/api/auth/otp/verificar', [
            'email' => $prof->email,
            'codigo' => $otpTest->plain_codigo,
            'challenge_token' => 'challenge-ajeno-invalido',
        ]);
        $this->assertSame(403, $resWrongChallenge->status());

        // 4. Con challenge_token válido sí emite token y consume el desafío (un solo uso)
        $resVerif2fa = $this->postJson('/api/auth/otp/verificar', [
            'email' => $prof->email,
            'codigo' => $otpTest->plain_codigo,
            'challenge_token' => $challengeToken,
        ]);
        $resVerif2fa->assertOk()->assertJsonStructure(['token']);

        // 5. Reutilizar el mismo OTP + challenge_token falla (422)
        $resReuse = $this->postJson('/api/auth/otp/verificar', [
            'email' => $prof->email,
            'codigo' => $otpTest->plain_codigo,
            'challenge_token' => $challengeToken,
        ]);
        $this->assertSame(422, $resReuse->status());

        // 6. Reenvío / renovación del desafío 2FA: nueva llamada a login-contrasena emite un challenge_token distinto
        $resLogin2 = $this->postJson('/api/auth/login-contrasena', [
            'email' => $prof->email,
            'password' => 'secreta123',
        ]);
        $resLogin2->assertOk()->assertJson(['requiere_2fa' => true])->assertJsonStructure(['challenge_token']);
        $challengeToken2 = $resLogin2->json('challenge_token');
        $this->assertNotSame($challengeToken, $challengeToken2);
    }

    public function test_ruta_reset_demo_y_backdoor_demo_estan_deshabilitados(): void
    {
        $resReset = $this->getJson('/api/reset-demo');
        $this->assertSame(404, $resReset->status());
        $this->assertDatabaseMissing('profesionales', ['email' => 'demo@citaspro.com']);

        $resBackdoor = $this->postJson('/api/auth/otp/verificar', [
            'telefono' => '+34600111222',
            'codigo' => '111111',
        ]);
        $this->assertSame(422, $resBackdoor->status());
        $this->assertDatabaseMissing('profesionales', ['email' => 'demo@citaspro.com']);
    }

    public function test_dashboard_no_auto_crea_demo_ni_acepta_profesional_id_sin_autenticacion(): void
    {
        $res = $this->getJson('/api/dashboard/metricas?profesional_id=1');
        $this->assertSame(401, $res->status());
        $this->assertDatabaseMissing('profesionales', ['email' => 'demo@citaspro.com']);
    }

    public function test_aislamiento_negativo_y_positivo_entre_dos_negocios(): void
    {
        $cat = Categoria::create(['nombre' => 'Salud', 'slug' => 'salud', 'activo' => true]);
        $negocioA = Negocio::create(['nombre' => 'Negocio A', 'slug' => 'neg-a', 'categoria_id' => $cat->id, 'activo' => true]);
        $negocioB = Negocio::create(['nombre' => 'Negocio B', 'slug' => 'neg-b', 'categoria_id' => $cat->id, 'activo' => true]);

        $duenoA = Profesional::create([
            'negocio_id' => $negocioA->id,
            'nombre' => 'Dueño A',
            'apellido' => 'Uno',
            'email' => 'a@example.com',
            'password' => Hash::make('secret123'),
            'rol' => 'dueño',
            'activo' => true,
        ]);
        $duenoB = Profesional::create([
            'negocio_id' => $negocioB->id,
            'nombre' => 'Dueño B',
            'apellido' => 'Dos',
            'email' => 'b@example.com',
            'password' => Hash::make('secret123'),
            'rol' => 'dueño',
            'activo' => true,
        ]);

        $servicioA = Servicio::create([
            'negocio_id' => $negocioA->id,
            'nombre' => 'Servicio A',
            'duracion_minutos' => 30,
            'precio' => 30,
            'activo' => true,
        ]);
        $servicioB = Servicio::create([
            'negocio_id' => $negocioB->id,
            'nombre' => 'Servicio B',
            'duracion_minutos' => 30,
            'precio' => 50,
            'activo' => true,
        ]);

        $clienteB = Cliente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Privado',
            'telefono' => '+34622222222',
            'email' => 'pacienteb@example.com',
        ]);
        $negocioB->clientes()->attach($clienteB->id, ['activo' => true]);

        $citaB = Cita::create([
            'negocio_id' => $negocioB->id,
            'profesional_id' => $duenoB->id,
            'cliente_id' => $clienteB->id,
            'servicio_id' => $servicioB->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '10:30:00',
            'duracion_min' => 30,
            'estado' => 'confirmada',
            'precio_total' => 50,
        ]);

        $fichaB = FichaClinica::create([
            'cliente_id' => $clienteB->id,
            'profesional_id' => $duenoB->id,
            'cita_id' => $citaB->id,
            'motivo_consulta' => 'Confidencial B',
            'diagnostico' => 'Diagnostico B',
        ]);

        $portafolioB = Portafolio::create([
            'negocio_id' => $negocioB->id,
            'profesional_id' => $duenoB->id,
            'servicio_id' => $servicioB->id,
            'titulo' => 'Trabajo B',
            'imagen' => 'portafolio/test-b.jpg',
            'publico' => true,
        ]);

        // ── NEGATIVAS: Dueño A contra recursos de B ────────────
        $this->actingAs($duenoA, 'sanctum');

        $this->getJson("/api/clientes/{$clienteB->id}")->assertStatus(404);
        $this->patchJson("/api/clientes/{$clienteB->id}", ['notas_internas' => 'Hack'])->assertStatus(404);

        $this->postJson('/api/citas', [
            'servicio_id' => $servicioB->id,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora' => '11:00',
            'cliente' => ['nombre' => 'Nuevo', 'telefono' => '+34633333333'],
        ])->assertStatus(404);

        $this->postJson('/api/citas', [
            'servicio_id' => $servicioA->id,
            'cliente_id' => $clienteB->id,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora' => '11:30',
        ])->assertStatus(404);

        $this->postJson('/api/citas', [
            'servicio_id' => $servicioA->id,
            'profesional_id' => $duenoB->id,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora' => '12:00',
            'cliente' => ['nombre' => 'Nuevo', 'telefono' => '+34633333334'],
        ])->assertStatus(404);

        $this->patchJson("/api/citas/{$citaB->id}", ['estado' => 'cancelada'])->assertStatus(404);
        $this->deleteJson("/api/citas/{$citaB->id}")->assertStatus(404);

        $this->getJson("/api/medico/fichas-clinicas/{$fichaB->id}")->assertStatus(403);

        $this->patchJson("/api/portafolio/{$portafolioB->id}", ['titulo' => 'Editado por A'])->assertStatus(403);
        $this->deleteJson("/api/portafolio/{$portafolioB->id}")->assertStatus(403);

        // Empleado sin rol dueño/admin en A no puede suplantar a dueño A
        $profA2 = Profesional::create([
            'negocio_id' => $negocioA->id,
            'nombre' => 'Empleado A',
            'apellido' => 'Tres',
            'email' => 'a2@example.com',
            'password' => Hash::make('secret123'),
            'rol' => 'profesional',
            'activo' => true,
        ]);
        $this->actingAs($profA2, 'sanctum');
        $this->postJson('/api/citas', [
            'servicio_id' => $servicioA->id,
            'profesional_id' => $duenoA->id,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora' => '13:00',
            'cliente' => ['nombre' => 'Nuevo2', 'telefono' => '+34633333335'],
        ])->assertStatus(403);

        // ── POSITIVAS: Dueño B sobre sus propios recursos ──────
        $this->actingAs($duenoB, 'sanctum');
        $this->getJson("/api/clientes/{$clienteB->id}")->assertOk();
        $this->patchJson("/api/clientes/{$clienteB->id}", ['notas_internas' => 'Nota B'])->assertOk();
        $this->getJson("/api/medico/fichas-clinicas/{$fichaB->id}")->assertOk();

        $resCrearB = $this->postJson('/api/citas', [
            'servicio_id' => $servicioB->id,
            'cliente_id' => $clienteB->id,
            'fecha' => now()->addDays(3)->toDateString(),
            'hora' => '10:00',
        ]);
        $this->assertContains($resCrearB->status(), [200, 201]);

        $this->patchJson("/api/citas/{$citaB->id}", ['notas_profesional' => 'Confirmada B'])->assertOk();
        $this->deleteJson("/api/citas/{$citaB->id}")->assertOk();

        $this->patchJson("/api/portafolio/{$portafolioB->id}", ['titulo' => 'Trabajo B Editado'])->assertOk();
        $this->deleteJson("/api/portafolio/{$portafolioB->id}")->assertOk();
    }

    public function test_mismo_cliente_en_negocios_a_y_b_mantiene_citas_y_fichas_aisladas_salvo_comparticion(): void
    {
        $cat = Categoria::create(['nombre' => 'Medicina', 'slug' => 'medicina', 'activo' => true]);
        $negocioA = Negocio::create(['nombre' => 'Clínica A', 'slug' => 'clin-a', 'categoria_id' => $cat->id, 'activo' => true]);
        $negocioB = Negocio::create(['nombre' => 'Clínica B', 'slug' => 'clin-b', 'categoria_id' => $cat->id, 'activo' => true]);

        $duenoA = Profesional::create([
            'negocio_id' => $negocioA->id,
            'nombre' => 'Dra A',
            'apellido' => 'Uno',
            'email' => 'dra-a@example.com',
            'password' => Hash::make('secret123'),
            'rol' => 'dueño',
            'activo' => true,
        ]);
        $duenoB = Profesional::create([
            'negocio_id' => $negocioB->id,
            'nombre' => 'Dr B',
            'apellido' => 'Dos',
            'email' => 'dr-b@example.com',
            'password' => Hash::make('secret123'),
            'rol' => 'dueño',
            'activo' => true,
        ]);

        $servA = Servicio::create(['negocio_id' => $negocioA->id, 'nombre' => 'Consulta A', 'duracion_minutos' => 30, 'precio' => 40, 'activo' => true]);
        $servB = Servicio::create(['negocio_id' => $negocioB->id, 'nombre' => 'Consulta B', 'duracion_minutos' => 30, 'precio' => 60, 'activo' => true]);

        // Mismo cliente vinculado a AMBOS negocios A y B
        $clienteCompartido = Cliente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Común',
            'telefono' => '+34677777777',
            'email' => 'comun@example.com',
        ]);
        $negocioA->clientes()->attach($clienteCompartido->id, ['activo' => true]);
        $negocioB->clientes()->attach($clienteCompartido->id, ['activo' => true]);

        $citaA = Cita::create([
            'negocio_id' => $negocioA->id,
            'profesional_id' => $duenoA->id,
            'cliente_id' => $clienteCompartido->id,
            'servicio_id' => $servA->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '09:30:00',
            'duracion_min' => 30,
            'estado' => 'confirmada',
            'precio_total' => 40,
        ]);
        $citaB = Cita::create([
            'negocio_id' => $negocioB->id,
            'profesional_id' => $duenoB->id,
            'cliente_id' => $clienteCompartido->id,
            'servicio_id' => $servB->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '12:00:00',
            'hora_fin' => '12:30:00',
            'duracion_min' => 30,
            'estado' => 'confirmada',
            'precio_total' => 60,
        ]);

        $fichaA = FichaClinica::create([
            'cliente_id' => $clienteCompartido->id,
            'profesional_id' => $duenoA->id,
            'cita_id' => $citaA->id,
            'motivo_consulta' => 'Motivo privado A',
            'diagnostico' => 'Dx privado A',
        ]);
        $fichaB = FichaClinica::create([
            'cliente_id' => $clienteCompartido->id,
            'profesional_id' => $duenoB->id,
            'cita_id' => $citaB->id,
            'motivo_consulta' => 'Motivo privado B',
            'diagnostico' => 'Dx privado B',
        ]);

        // Dueño A ve al cliente, pero SOLO sus propias citas y fichas (no las de B)
        $this->actingAs($duenoA, 'sanctum');
        $resCliA = $this->getJson("/api/clientes/{$clienteCompartido->id}")->assertOk();
        $this->assertCount(1, $resCliA->json('historial_citas'));
        $this->assertSame($citaA->id, $resCliA->json('historial_citas.0.id'));

        $resIdxA = $this->getJson("/api/medico/fichas-clinicas/paciente/{$clienteCompartido->id}")->assertOk();
        $this->assertCount(1, $resIdxA->json('historial'));
        $this->assertSame($fichaA->id, $resIdxA->json('historial.0.id'));

        $this->getJson("/api/medico/fichas-clinicas/{$fichaB->id}")->assertStatus(403);

        // Viceversa: Dueño B solo ve su cita y su ficha, no la de A
        $this->actingAs($duenoB, 'sanctum');
        $resCliB = $this->getJson("/api/clientes/{$clienteCompartido->id}")->assertOk();
        $this->assertCount(1, $resCliB->json('historial_citas'));
        $this->assertSame($citaB->id, $resCliB->json('historial_citas.0.id'));

        $resIdxB = $this->getJson("/api/medico/fichas-clinicas/paciente/{$clienteCompartido->id}")->assertOk();
        $this->assertCount(1, $resIdxB->json('historial'));
        $this->assertSame($fichaB->id, $resIdxB->json('historial.0.id'));

        $this->getJson("/api/medico/fichas-clinicas/{$fichaA->id}")->assertStatus(403);

        // Con permiso explícito de compartición de B hacia A: A ya puede ver la ficha de B
        $this->postJson('/api/medico/fichas-clinicas/compartir', [
            'cliente_id' => $clienteCompartido->id,
            'profesional_id' => $duenoA->id,
        ])->assertOk();

        $this->actingAs($duenoA, 'sanctum');
        $this->getJson("/api/medico/fichas-clinicas/{$fichaB->id}")->assertOk();
        $resIdxAComp = $this->getJson("/api/medico/fichas-clinicas/paciente/{$clienteCompartido->id}")->assertOk();
        $this->assertCount(2, $resIdxAComp->json('historial'));
    }

    public function test_otp_protegido_en_bd_consumo_unico_y_bloqueo_por_intentos(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cli',
            'apellido' => 'Test',
            'telefono' => '+34644444444',
            'email' => 'cli-otp@example.com',
            'activo' => true,
        ]);

        $otp = OtpCode::crearParaEmail($cliente->email, 'login', '127.0.0.1');

        // En BD está almacenado el hash HMAC-SHA256 (64 hex), nunca el PIN en claro
        $this->assertNotSame($otp->plain_codigo, $otp->codigo);
        $this->assertSame(64, strlen($otp->codigo));
        $this->assertTrue(hash_equals($otp->codigo, OtpCode::hashValor($otp->plain_codigo)));
        $this->assertArrayNotHasKey('codigo', $otp->toArray());

        // 3 intentos fallidos invalidan el OTP definitivamente
        for ($i = 0; $i < 3; $i++) {
            $resFail = $this->postJson('/api/auth/otp/verificar', [
                'email' => $cliente->email,
                'codigo' => '000000',
            ]);
            $this->assertSame(422, $resFail->status());
        }
        $this->assertTrue((bool) $otp->fresh()->usado);

        // Un nuevo OTP válido solo puede consumirse una vez
        $otp2 = OtpCode::crearParaEmail($cliente->email, 'login', '127.0.0.1');
        $res1 = $this->postJson('/api/auth/otp/verificar', [
            'email' => $cliente->email,
            'codigo' => $otp2->plain_codigo,
        ]);
        $res1->assertOk();

        $res2 = $this->postJson('/api/auth/otp/verificar', [
            'email' => $cliente->email,
            'codigo' => $otp2->plain_codigo,
        ]);
        $this->assertSame(422, $res2->status());
    }

    public function test_registro_por_correo_atomico_sin_huerfanos_y_permite_reintento(): void
    {
        $cat = Categoria::create(['nombre' => 'Belleza', 'slug' => 'belleza', 'activo' => true]);
        $email = 'nuevo-negocio@example.com';
        $telefono = '+34655555555';

        $resEnviar = $this->postJson('/api/auth/otp/enviar', [
            'email' => $email,
            'telefono' => $telefono,
            'password' => 'clave1234',
            'canal' => 'email',
        ]);
        $resEnviar->assertOk();

        // Obtenemos un OTP conocido para el registro
        $otp = OtpCode::crearParaEmail($email, 'login', '127.0.0.1');

        // 1. Simular fallo al crear Profesional: debe hacer rollback completo (sin Negocio huérfano)
        $fallar = true;
        Profesional::creating(function () use (&$fallar) {
            if ($fallar) {
                throw new \RuntimeException('Fallo simulado al crear profesional');
            }
        });

        $payload = [
            'es_registro' => true,
            'email' => $email,
            'telefono' => $telefono,
            'password' => 'clave1234',
            'nombre' => 'Carlos',
            'apellido' => 'Rojas',
            'nombre_negocio' => 'Estudio Carlos',
            'categoria_id' => $cat->id,
            'codigo' => $otp->plain_codigo,
        ];

        $resFail = $this->postJson('/api/auth/otp/verificar', $payload);
        $this->assertSame(500, $resFail->status());
        $this->assertDatabaseMissing('negocios', ['nombre' => 'Estudio Carlos']);
        $this->assertDatabaseMissing('profesionales', ['email' => $email]);
        $this->assertFalse((bool) $otp->fresh()->usado);

        // 2. Reintentar sin el fallo: el registro se completa correctamente y conserva teléfono como contacto
        $fallar = false;
        $resOk = $this->postJson('/api/auth/otp/verificar', $payload);
        $resOk->assertOk()->assertJsonStructure(['token']);

        $prof = Profesional::where('email', $email)->first();
        $this->assertNotNull($prof);
        $this->assertSame('dueño', $prof->rol);
        $this->assertSame($telefono, $prof->telefono);

        $negocio = Negocio::find($prof->negocio_id);
        $this->assertNotNull($negocio);
        $this->assertSame('Estudio Carlos', $negocio->nombre);
        $this->assertSame($telefono, $negocio->telefono);
    }
}
