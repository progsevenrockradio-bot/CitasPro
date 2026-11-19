<?php

namespace Tests\Feature;

use App\Mail\CitaConfirmacionMail;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Negocio;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ReservaRecorridoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function crearEntornoReserva(): array
    {
        $cat = Categoria::create(['nombre' => 'Bienestar', 'slug' => 'bienestar', 'activo' => true]);
        $negocio = Negocio::create([
            'categoria_id'       => $cat->id,
            'nombre'             => 'Estudio Madrid Demo',
            'slug'               => 'estudio-madrid-demo',
            'email'              => 'contacto@estudiomadrid.local',
            'telefono'           => '+34910000000',
            'booking_activo'     => true,
            'activo'             => true,
            'duracion_turno_min' => 30,
            'timezone'           => 'Europe/Madrid',
            'horario_apertura'   => [
                'lun' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => false, 'descansos' => [['inicio' => '14:00', 'fin' => '15:00']]],
                'mar' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => false, 'descansos' => [['inicio' => '14:00', 'fin' => '15:00']]],
                'mié' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => false, 'descansos' => [['inicio' => '14:00', 'fin' => '15:00']]],
                'jue' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => false, 'descansos' => [['inicio' => '14:00', 'fin' => '15:00']]],
                'vie' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => false, 'descansos' => [['inicio' => '14:00', 'fin' => '15:00']]],
                'sáb' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => true],
                'dom' => ['inicio' => '09:00', 'fin' => '18:00', 'cerrado' => true],
            ],
        ]);

        $dueno = Profesional::create([
            'negocio_id'     => $negocio->id,
            'nombre'         => 'Laura',
            'apellido'       => 'Gómez',
            'email'          => 'laura.demo@ejemplo.local',
            'password'       => Hash::make('secreta123'),
            'rol'            => 'dueño',
            'type'           => 'general',
            'activo'         => true,
            'aceptar_online' => true,
        ]);

        $servicio = Servicio::create([
            'negocio_id'                => $negocio->id,
            'nombre'                    => 'Sesión Integral',
            'duracion_min'              => 60,
            'precio'                    => 45.00,
            'moneda'                    => 'EUR',
            'max_clientes_simultaneous' => 1,
            'activo'                    => true,
        ]);

        return [$negocio, $dueno, $servicio];
    }

    public function test_disponibilidad_y_validacion_respetan_horario_descansos_cierre_y_timezone(): void
    {
        [$negocio, $dueno, $servicio] = $this->crearEntornoReserva();

        $viernes = Carbon::now('Europe/Madrid')->next(Carbon::FRIDAY)->toDateString();
        $domingo = Carbon::now('Europe/Madrid')->next(Carbon::SUNDAY)->toDateString();

        // Domingo cerrado -> []
        $resDom = $this->getJson("/api/public/{$negocio->slug}/disponibilidad?fecha={$domingo}&profesional_id={$dueno->id}&servicio_id={$servicio->id}");
        $resDom->assertOk()
            ->assertJson(['timezone' => 'Europe/Madrid', 'disponibles' => []]);

        // Viernes con servicio 60 min y descanso 14:00-15:00 y cierre 18:00
        $resVie = $this->getJson("/api/public/{$negocio->slug}/disponibilidad?fecha={$viernes}&profesional_id={$dueno->id}&servicio_id={$servicio->id}");
        $resVie->assertOk()->assertJson([
            'timezone'          => 'Europe/Madrid',
            'duracion_slot'     => 30,
            'duracion_servicio' => 60,
        ]);

        $slots = $resVie->json('disponibles');
        $this->assertContains('09:00', $slots);
        $this->assertContains('13:00', $slots); // 13:00-14:00 justo antes del descanso
        $this->assertNotContains('13:30', $slots); // 13:30-14:30 solapa descanso
        $this->assertNotContains('14:00', $slots); // descanso
        $this->assertNotContains('14:30', $slots); // descanso
        $this->assertContains('15:00', $slots); // justo tras descanso
        $this->assertContains('17:00', $slots); // 17:00-18:00 justo antes del cierre
        $this->assertNotContains('17:30', $slots); // 17:30-18:30 supera cierre 18:00

        // Horarios inválidos rechazados con 422
        $invalidos = [
            [$viernes, '08:00', '10.1.0.1'], // antes de apertura
            [$viernes, '17:30', '10.1.0.2'], // excede cierre
            [$viernes, '13:30', '10.1.0.3'], // solapa descanso
            [$viernes, '14:00', '10.1.0.4'], // dentro descanso
            [$domingo, '10:00', '10.1.0.5'], // día cerrado
        ];

        foreach ($invalidos as $idx => [$fecha, $hora, $ip]) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson("/api/public/{$negocio->slug}/reservar", [
                    'servicio_id'      => $servicio->id,
                    'profesional_id'   => $dueno->id,
                    'fecha'            => $fecha,
                    'hora'             => $hora,
                    'cliente_nombre'   => 'Test',
                    'cliente_apellido' => 'Rechazo',
                    'cliente_telefono' => "+3461000010{$idx}",
                    'cliente_email'    => "rechazo{$idx}@ejemplo.local",
                ])
                ->assertStatus(422);
        }

        $this->assertSame(0, Cita::count());
    }

    public function test_reserva_valida_correo_confirmacion_y_bloqueo_solapamientos_parciales(): void
    {
        [$negocio, $dueno, $servicio] = $this->crearEntornoReserva();
        $viernes = Carbon::now('Europe/Madrid')->next(Carbon::FRIDAY)->toDateString();

        // Reservar a las 10:00 (60 min -> 10:00-11:00)
        $resOk = $this->withServerVariables(['REMOTE_ADDR' => '10.2.0.1'])
            ->postJson("/api/public/{$negocio->slug}/reservar", [
                'servicio_id'      => $servicio->id,
                'profesional_id'   => $dueno->id,
                'fecha'            => $viernes,
                'hora'             => '10:00',
                'cliente_nombre'   => 'Carlos',
                'cliente_apellido' => 'Martín',
                'cliente_telefono' => '+34600111222',
                'cliente_email'    => 'carlos.demo@ejemplo.local',
                'metodo_pago'      => 'efectivo',
            ]);

        $resOk->assertStatus(201)
            ->assertJson([
                'success' => true,
                'cita'    => [
                    'hora'        => '10:00',
                    'timezone'    => 'Europe/Madrid',
                    'servicio'    => 'Sesión Integral',
                    'profesional' => 'Laura Gómez',
                    'negocio'     => 'Estudio Madrid Demo',
                    'duracion_min'=> 60,
                    'precio'      => '45.00',
                    'moneda'      => 'EUR',
                ],
            ]);

        // Correo generado al cliente correcto con negocio, fecha, hora, precio y Europe/Madrid
        Mail::assertSent(CitaConfirmacionMail::class, function (CitaConfirmacionMail $mail) use ($viernes) {
            if (!$mail->hasTo('carlos.demo@ejemplo.local') || $mail->rol !== 'paciente') {
                return false;
            }
            $html = $mail->render();
            return str_contains($html, 'Estudio Madrid Demo')
                && str_contains($html, Carbon::parse($viernes)->format('d/m/Y'))
                && str_contains($html, '10:00')
                && str_contains($html, 'Europe/Madrid')
                && str_contains($html, '45.00 EUR')
                && str_contains($html, 'Sesión Integral');
        });

        // Disponibilidad excluye 09:30, 10:00, 10:30 y mantiene 09:00 y 11:00
        $slots = $this->getJson("/api/public/{$negocio->slug}/disponibilidad?fecha={$viernes}&profesional_id={$dueno->id}&servicio_id={$servicio->id}")
            ->assertOk()
            ->json('disponibles');

        $this->assertContains('09:00', $slots);
        $this->assertNotContains('09:30', $slots);
        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('10:30', $slots);
        $this->assertContains('11:00', $slots);

        // Solapamientos exacto/parciales -> 409 Conflict
        foreach (['10:00' => '10.2.0.2', '09:30' => '10.2.0.3', '10:30' => '10.2.0.4'] as $hora => $ip) {
            $horaLimpia = str_replace(':', '', $hora);
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson("/api/public/{$negocio->slug}/reservar", [
                    'servicio_id'      => $servicio->id,
                    'profesional_id'   => $dueno->id,
                    'fecha'            => $viernes,
                    'hora'             => $hora,
                    'cliente_nombre'   => 'Otro',
                    'cliente_telefono' => '+34600999' . $horaLimpia,
                    'cliente_email'    => "solap_{$horaLimpia}@ejemplo.local",
                ])
                ->assertStatus(409);
        }

        // Contigua 11:00 -> 201 OK
        $this->withServerVariables(['REMOTE_ADDR' => '10.2.0.5'])
            ->postJson("/api/public/{$negocio->slug}/reservar", [
                'servicio_id'      => $servicio->id,
                'profesional_id'   => $dueno->id,
                'fecha'            => $viernes,
                'hora'             => '11:00',
                'cliente_nombre'   => 'Lucía',
                'cliente_telefono' => '+34600333444',
                'cliente_email'    => 'lucia@ejemplo.local',
            ])
            ->assertStatus(201);
    }

    public function test_cancelacion_libera_horario_y_aisla_cliente_y_otro_negocio(): void
    {
        [$negocioA, $duenoA, $servicioA] = $this->crearEntornoReserva();

        $cat = Categoria::first();
        $negocioB = Negocio::create([
            'categoria_id' => $cat->id,
            'nombre'       => 'Otro Negocio',
            'slug'         => 'otro-negocio',
            'activo'       => true,
        ]);
        $duenoB = Profesional::create([
            'negocio_id' => $negocioB->id,
            'nombre'     => 'Pedro',
            'apellido'   => 'B',
            'email'      => 'pedro.b@ejemplo.local',
            'password'   => Hash::make('secreta123'),
            'rol'        => 'dueño',
            'activo'     => true,
        ]);

        $fecha = Carbon::now('Europe/Madrid')->next(Carbon::FRIDAY)->toDateString();

        $this->withServerVariables(['REMOTE_ADDR' => '10.3.0.1'])
            ->postJson("/api/public/{$negocioA->slug}/reservar", [
                'servicio_id'      => $servicioA->id,
                'profesional_id'   => $duenoA->id,
                'fecha'            => $fecha,
                'hora'             => '10:00',
                'cliente_nombre'   => 'Carlos',
                'cliente_apellido' => 'Martín',
                'cliente_telefono' => '+34600111222',
                'cliente_email'    => 'carlos.demo@ejemplo.local',
            ])
            ->assertStatus(201);

        $cita = Cita::firstOrFail();
        $cliente = Cliente::findOrFail($cita->cliente_id);

        // Cliente ve solo sus datos en /api/auth/me y no puede acceder a panel ni cancelar citas
        $tokenCliente = $cliente->createToken('test-cliente')->plainTextToken;
        $this->withToken($tokenCliente)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson([
                'role' => 'cliente',
                'user' => [
                    'email'       => 'carlos.demo@ejemplo.local',
                    'total_citas' => 1,
                ],
            ]);
        $this->withToken($tokenCliente)->getJson('/api/clientes')->assertStatus(403);
        $this->withToken($tokenCliente)->getJson('/api/dashboard/agenda')->assertStatus(403);
        $this->withToken($tokenCliente)->getJson('/api/negocio')->assertStatus(403);
        $this->withToken($tokenCliente)->deleteJson("/api/citas/{$cita->id}")->assertStatus(403);
        $this->withToken($tokenCliente)->patchJson("/api/dashboard/citas/{$cita->id}/estado", ['estado' => 'cancelada'])->assertStatus(403);

        // Otro negocio no puede ver ni cancelar esa cita
        $this->app['auth']->forgetGuards();
        $tokenDuenoB = $duenoB->createToken('test-dueno-b')->plainTextToken;
        $this->withToken($tokenDuenoB)->getJson("/api/clientes/{$cliente->id}")->assertStatus(404);
        $this->withToken($tokenDuenoB)->deleteJson("/api/citas/{$cita->id}")->assertStatus(404);

        // Dueño A ve la cita en agenda y la cancela
        $this->app['auth']->forgetGuards();
        $tokenDuenoA = $duenoA->createToken('test-dueno-a')->plainTextToken;
        $agendaRes = $this->withToken($tokenDuenoA)->getJson('/api/dashboard/agenda')->assertOk();
        $this->assertStringContainsString('Carlos Martín', json_encode($agendaRes->json(), JSON_UNESCAPED_UNICODE));

        $this->withToken($tokenDuenoA)->deleteJson("/api/citas/{$cita->id}")->assertOk();

        // El hueco 10:00 vuelve a estar disponible y reservable
        $slotsAfter = $this->getJson("/api/public/{$negocioA->slug}/disponibilidad?fecha={$fecha}&profesional_id={$duenoA->id}&servicio_id={$servicioA->id}")
            ->assertOk()
            ->json('disponibles');
        $this->assertContains('10:00', $slotsAfter);

        $this->withServerVariables(['REMOTE_ADDR' => '10.3.0.2'])
            ->postJson("/api/public/{$negocioA->slug}/reservar", [
                'servicio_id'      => $servicioA->id,
                'profesional_id'   => $duenoA->id,
                'fecha'            => $fecha,
                'hora'             => '10:00',
                'cliente_nombre'   => 'Elena',
                'cliente_telefono' => '+34600555666',
                'cliente_email'    => 'elena@ejemplo.local',
            ])
            ->assertStatus(201);

        // También probar cancelación vía PATCH cambiando estado a cancelada libera el slot
        $cita2 = Cita::latest('id')->firstOrFail();
        $this->withToken($tokenDuenoA)
            ->patchJson("/api/citas/{$cita2->id}", ['estado' => 'cancelada'])
            ->assertOk();

        $slotsAfterPatch = $this->getJson("/api/public/{$negocioA->slug}/disponibilidad?fecha={$fecha}&profesional_id={$duenoA->id}&servicio_id={$servicioA->id}")
            ->assertOk()
            ->json('disponibles');
        $this->assertContains('10:00', $slotsAfterPatch);
    }

    public function test_recordatorios_reloj_adelantado_europe_madrid_no_duplica_ni_canceladas(): void
    {
        [$negocio, $dueno, $servicio] = $this->crearEntornoReserva();

        $clienteAct = Cliente::create([
            'nombre'   => 'Activa',
            'apellido' => 'Madrid',
            'telefono' => '+34600700001',
            'email'    => 'activa@ejemplo.local',
        ]);
        $clienteCan = Cliente::create([
            'nombre'   => 'Cancelada',
            'apellido' => 'Madrid',
            'telefono' => '+34600700002',
            'email'    => 'cancelada@ejemplo.local',
        ]);

        // Forzar app.timezone=UTC para demostrar que el cálculo usa Europe/Madrid del negocio
        config(['app.timezone' => 'UTC']);

        $fechaCita = '2026-11-21';
        $citaActiva = Cita::create([
            'negocio_id'           => $negocio->id,
            'cliente_id'           => $clienteAct->id,
            'profesional_id'       => $dueno->id,
            'servicio_id'          => $servicio->id,
            'fecha'                => $fechaCita,
            'hora_inicio'          => '10:00:00', // 10:00 Europe/Madrid
            'hora_fin'             => '11:00:00',
            'duracion_min'         => 60,
            'estado'               => 'confirmada',
            'precio_total'         => 45.00,
            'recordatorio_enviado' => false,
        ]);

        $citaCancelada = Cita::create([
            'negocio_id'           => $negocio->id,
            'cliente_id'           => $clienteCan->id,
            'profesional_id'       => $dueno->id,
            'servicio_id'          => $servicio->id,
            'fecha'                => $fechaCita,
            'hora_inicio'          => '10:00:00',
            'hora_fin'             => '11:00:00',
            'duracion_min'         => 60,
            'estado'               => 'cancelada',
            'precio_total'         => 45.00,
            'recordatorio_enviado' => false,
        ]);

        try {
            // Reloj adelantado exactamente a 24h antes en Europe/Madrid (2026-11-20 10:00 Europe/Madrid = 09:00 UTC)
            Carbon::setTestNow(Carbon::create(2026, 11, 20, 10, 0, 0, 'Europe/Madrid'));

            $wa = Mockery::mock(WhatsAppService::class);
            $wa->shouldReceive('enviarRecordatorio')
                ->once()
                ->with(Mockery::on(fn (Cita $c) => $c->id === $citaActiva->id))
                ->andReturn(true);

            $sms = Mockery::mock(SmsService::class);
            $sms->shouldNotReceive('enviarRecordatorio');

            $this->app->instance(WhatsAppService::class, $wa);
            $this->app->instance(SmsService::class, $sms);

            // Primera ejecución: envía 1 vez solo a la cita activa
            Artisan::call('citas:recordatorios');

            $this->assertTrue((bool) $citaActiva->fresh()->recordatorio_enviado);
            $this->assertNotNull($citaActiva->fresh()->recordatorio_enviado_en);
            $this->assertFalse((bool) $citaCancelada->fresh()->recordatorio_enviado);

            // Segunda ejecución: al esperar ->once(), garantiza que no se envía dos veces
            Artisan::call('citas:recordatorios');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_recordatorios_fallo_de_envio_no_marca_como_enviado(): void
    {
        [$negocio, $dueno, $servicio] = $this->crearEntornoReserva();

        $clienteFail = Cliente::create([
            'nombre'   => 'Fallo',
            'apellido' => 'Canal',
            'telefono' => '+34600700003',
            'email'    => 'fallo@ejemplo.local',
        ]);

        config(['app.timezone' => 'UTC']);

        $citaFallo = Cita::create([
            'negocio_id'           => $negocio->id,
            'cliente_id'           => $clienteFail->id,
            'profesional_id'       => $dueno->id,
            'servicio_id'          => $servicio->id,
            'fecha'                => '2026-11-21',
            'hora_inicio'          => '10:00:00',
            'hora_fin'             => '11:00:00',
            'duracion_min'         => 60,
            'estado'               => 'confirmada',
            'precio_total'         => 45.00,
            'recordatorio_enviado' => false,
        ]);

        try {
            Carbon::setTestNow(Carbon::create(2026, 11, 20, 10, 0, 0, 'Europe/Madrid'));

            $waFail = Mockery::mock(WhatsAppService::class);
            $waFail->shouldReceive('enviarRecordatorio')
                ->once()
                ->with(Mockery::on(fn (Cita $c) => $c->id === $citaFallo->id))
                ->andReturn(false);

            $smsFail = Mockery::mock(SmsService::class);
            $smsFail->shouldReceive('enviarRecordatorio')
                ->once()
                ->with(Mockery::on(fn (Cita $c) => $c->id === $citaFallo->id))
                ->andReturn(false);

            $this->app->instance(WhatsAppService::class, $waFail);
            $this->app->instance(SmsService::class, $smsFail);

            Artisan::call('citas:recordatorios');

            $citaFallo->refresh();
            $this->assertFalse((bool) $citaFallo->recordatorio_enviado);
            $this->assertNull($citaFallo->recordatorio_enviado_en);
        } finally {
            Carbon::setTestNow();
        }
    }
}
