<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Negocio;
use App\Models\OtpCode;
use App\Models\Profesional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginAccountTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_login_contrasena_devuelve_contrato_completo_y_acceso_al_panel_del_dueno(): void
    {
        $cat = Categoria::create(['nombre' => 'General', 'slug' => 'general', 'activo' => true]);
        $negocio = Negocio::create(['nombre' => 'ExitoInfinito', 'slug' => 'exitoinfinito', 'categoria_id' => $cat->id, 'activo' => true]);
        $dueno = Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Jose',
            'apellido' => 'Font',
            'email' => 'dueno@example.com',
            'password' => Hash::make('secreta123'),
            'rol' => 'dueño',
            'type' => 'general',
            'doble_factor_activo' => false,
            'activo' => true,
        ]);

        $res = $this->postJson('/api/auth/login-contrasena', [
            'email' => $dueno->email,
            'password' => 'secreta123',
            'account_type' => 'negocio',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'role' => 'profesional',
                'requiere_2fa' => false,
                'user' => [
                    'id' => $dueno->id,
                    'negocio_id' => $negocio->id,
                    'rol' => 'dueño',
                    'type' => 'general',
                ],
            ])
            ->assertJsonStructure(['token']);

        // Verificar que con ese token /api/auth/me y /api/negocio identifican al dueño
        $token = $res->json('token');
        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson([
                'role' => 'profesional',
                'user' => ['id' => $dueno->id, 'rol' => 'dueño'],
            ]);

        $this->withToken($token)->getJson('/api/negocio')
            ->assertOk()
            ->assertJsonPath('negocio.id', $negocio->id);
    }

    public function test_mismo_correo_con_ambos_perfiles_accede_al_elegido_sin_mezclar(): void
    {
        $cat = Categoria::create(['nombre' => 'Salud', 'slug' => 'salud', 'activo' => true]);
        $negocio = Negocio::create(['nombre' => 'Clinica Dual', 'slug' => 'clinica-dual', 'categoria_id' => $cat->id, 'activo' => true]);
        $emailDual = 'dual@example.com';

        $dueno = Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Dual',
            'apellido' => 'Pro',
            'email' => $emailDual,
            'password' => Hash::make('secreta123'),
            'rol' => 'dueño',
            'doble_factor_activo' => true,
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Dual',
            'apellido' => 'Cliente',
            'telefono' => '+34688888888',
            'email' => $emailDual,
            'activo' => true,
        ]);

        // 1. Acceso como CLIENTE: emite token de cliente (sin privilegios de negocio)
        $this->postJson('/api/auth/otp/enviar', [
            'email' => $emailDual,
            'canal' => 'email',
            'account_type' => 'cliente',
        ])->assertOk();

        $otpCli = OtpCode::crearParaEmail($emailDual, 'login', '127.0.0.1');
        $resCli = $this->postJson('/api/auth/otp/verificar', [
            'email' => $emailDual,
            'codigo' => $otpCli->plain_codigo,
            'account_type' => 'cliente',
            'es_registro' => false,
        ]);
        $resCli->assertOk()->assertJson(['role' => 'cliente']);
        $this->withToken($resCli->json('token'))->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson(['role' => 'cliente', 'user' => ['id' => $cliente->id]]);
        $this->withToken($resCli->json('token'))->getJson('/api/negocio')
            ->assertStatus(403);

        // 2. Acceso como NEGOCIO con 2FA: emite token de profesional/dueño
        $resLoginNeg = $this->postJson('/api/auth/login-contrasena', [
            'email' => $emailDual,
            'password' => 'secreta123',
            'account_type' => 'negocio',
        ]);
        $resLoginNeg->assertOk()->assertJson(['requiere_2fa' => true, 'account_type' => 'negocio']);

        $challenge = $resLoginNeg->json('challenge_token');
        $otpNeg = OtpCode::crearParaEmail($emailDual, '2fa', '127.0.0.1', OtpCode::hashValor($challenge), $dueno->id);
        $resVerifNeg = $this->postJson('/api/auth/otp/verificar', [
            'email' => $emailDual,
            'codigo' => $otpNeg->plain_codigo,
            'challenge_token' => $challenge,
            'account_type' => 'negocio',
            'es_registro' => false,
        ]);
        $resVerifNeg->assertOk()->assertJson(['role' => 'profesional', 'user' => ['rol' => 'dueño']]);
    }

    public function test_elegir_perfil_inexistente_rechaza_con_mensaje_claro_y_no_concede_rol(): void
    {
        $cat = Categoria::create(['nombre' => 'General', 'slug' => 'gen', 'activo' => true]);
        $negocio = Negocio::create(['nombre' => 'Solo Negocio', 'slug' => 'solo-neg', 'categoria_id' => $cat->id, 'activo' => true]);
        Profesional::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Solo',
            'apellido' => 'Pro',
            'email' => 'solopro@example.com',
            'password' => Hash::make('secreta123'),
            'rol' => 'dueño',
            'activo' => true,
        ]);

        Cliente::create([
            'nombre' => 'Solo',
            'apellido' => 'Cli',
            'telefono' => '+34677777771',
            'email' => 'solocli@example.com',
            'activo' => true,
        ]);

        // Cuenta solo-cliente elige "negocio" -> 403 y nunca concede rol
        $this->postJson('/api/auth/login-contrasena', [
            'email' => 'solocli@example.com',
            'password' => 'cualquiera',
            'account_type' => 'negocio',
        ])->assertStatus(403);

        // Cuenta solo-negocio elige "cliente" -> 404 con explicación clara
        $this->postJson('/api/auth/otp/enviar', [
            'email' => 'solopro@example.com',
            'canal' => 'email',
            'account_type' => 'cliente',
        ])->assertStatus(404);
    }
}
