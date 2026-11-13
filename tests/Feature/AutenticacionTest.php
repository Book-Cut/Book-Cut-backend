<?php

use App\Models\usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('Usuario');
    Schema::create('Usuario', function (Blueprint $table) {
        $table->id('idUsuario');
        $table->string('Nombre');
        $table->string('correo')->unique();
        $table->string('telefono')->nullable();
        $table->string('contrasenha');
        $table->unsignedBigInteger('Roles_IDRol')->default(3);
        $table->boolean('terminos_aceptados')->default(false);
        $table->timestamps();
    });

    Schema::dropIfExists('personal_access_tokens');
    Schema::create('personal_access_tokens', function (Blueprint $table) {
        $table->id();
        $table->morphs('tokenable');
        $table->text('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable()->index();
        $table->timestamps();
    });

    Schema::dropIfExists('auth_temporary_tokens');
    Schema::create('auth_temporary_tokens', function (Blueprint $table) {
        $table->string('token_hash', 64)->primary();
        $table->string('purpose', 32);
        $table->json('payload');
        $table->unsignedTinyInteger('attempts')->default(0);
        $table->timestamp('expires_at')->index();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('auth_temporary_tokens');
    Schema::dropIfExists('personal_access_tokens');
    Schema::dropIfExists('Usuario');
});

test('permite registrar usuarios con un dominio permitido y acepta términos', function () {
    $response = $this->postJson('/api/registro', [
        'Nombre' => 'Mauricio',
        'correo' => 'mauricio@gmail.com',
        'telefono' => '3214567890',
        'contrasenha' => 'claveSegura123',
        'terminos_aceptados' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('result', 'ok')
        ->assertJsonPath('user.correo', 'mauricio@gmail.com');

    $this->assertDatabaseHas('Usuario', [
        'correo' => 'mauricio@gmail.com',
        'terminos_aceptados' => true,
    ]);
});

test('rechaza correos con dominios no permitidos', function () {
    $response = $this->postJson('/api/registro', [
        'Nombre' => 'Mauricio',
        'correo' => 'mauricio@yahoo.com',
        'telefono' => '3214567890',
        'contrasenha' => 'claveSegura123',
        'terminos_aceptados' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.correo.0', 'Solo se permite correos de los siguientes dominios: @gmail.com, @hotmail.com, @outlook.com, @sena.edu.co.');
});

test('requiere aceptar los términos y condiciones', function () {
    $response = $this->postJson('/api/registro', [
        'Nombre' => 'Mauricio',
        'correo' => 'mauricio@hotmail.com',
        'telefono' => '3214567890',
        'contrasenha' => 'claveSegura123',
        'terminos_aceptados' => false,
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.terminos_aceptados.0', 'Debes aceptar los términos y condiciones.');
});

test('registra la cuenta y consume el código de verificación de un solo uso', function () {
    $correo = 'registro@gmail.com';
    $tokenHash = hash('sha256', 'registro:' . $correo);

    DB::table('auth_temporary_tokens')->insert([
        'token_hash' => $tokenHash,
        'purpose' => 'registro',
        'payload' => json_encode([
            'codigo' => Hash::make('123456'),
            'datos' => [
                'Nombre' => 'Mauricio',
                'correo' => $correo,
                'telefono' => '3214567890',
                'contrasenha' => Hash::make('claveSegura123'),
                'Roles_IDRol' => 3,
                'terminos_aceptados' => true,
            ],
        ], JSON_THROW_ON_ERROR),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/registro/verify', [
        'correo' => $correo,
        'codigo' => '123456',
    ])->assertCreated()
        ->assertJsonPath('result', 'ok')
        ->assertJsonPath('user.correo', $correo);

    $this->assertDatabaseHas('Usuario', ['correo' => $correo]);
    $this->assertDatabaseMissing('auth_temporary_tokens', ['token_hash' => $tokenHash]);

    $this->postJson('/api/registro/verify', [
        'correo' => $correo,
        'codigo' => '123456',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'el codigo expiro, solicite uno nuevo');
});

test('rechaza un código de inicio social vencido o inválido', function () {
    $this->postJson('/api/auth/mobile/exchange', [
        'code' => str_repeat('x', 64),
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'El inicio de sesión expiró. Inténtalo de nuevo.');
});

test('canjea un código social de un solo uso por una sesión', function () {
    $user = usuario::create([
        'Nombre' => 'Andrés',
        'correo' => 'andres@gmail.com',
        'telefono' => '3001234567',
        'contrasenha' => Hash::make('claveSegura123'),
        'Roles_IDRol' => 3,
        'terminos_aceptados' => true,
    ]);
    $code = str_repeat('a', 64);
    $tokenHash = hash('sha256', 'social_login:' . $code);
    DB::table('auth_temporary_tokens')->insert([
        'token_hash' => $tokenHash,
        'purpose' => 'social_login',
        'payload' => json_encode(['user_id' => $user->getKey()], JSON_THROW_ON_ERROR),
        'attempts' => 0,
        'expires_at' => now()->addMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/auth/mobile/exchange', ['code' => $code])
        ->assertOk()
        ->assertJsonPath('result', 'ok')
        ->assertJsonPath('user.correo', 'andres@gmail.com')
        ->assertJsonStructure(['token']);

    $this->assertDatabaseMissing('auth_temporary_tokens', ['token_hash' => $tokenHash]);
});

test('prepara un registro social temporal con teléfono y términos aceptados', function () {
    $response = $this->postJson('/api/auth/mobile/social/register', [
        'telefono' => '3001234567',
        'terminos_aceptados' => true,
    ])->assertOk()
        ->assertJsonPath('result', 'ok');

    $registration = $response->json('registration');
    expect($registration)->toBeString();
    $this->assertDatabaseHas('auth_temporary_tokens', [
        'token_hash' => hash('sha256', 'social_registration:' . $registration),
        'purpose' => 'social_registration',
        'payload' => json_encode(['telefono' => '3001234567'], JSON_THROW_ON_ERROR),
    ]);
});

test('prepara un registro social sin teléfono si acepta los términos', function () {
    $response = $this->postJson('/api/auth/mobile/social/register', [
        'terminos_aceptados' => true,
    ])->assertOk()
        ->assertJsonPath('result', 'ok');

    $registration = $response->json('registration');
    $this->assertDatabaseHas('auth_temporary_tokens', [
        'token_hash' => hash('sha256', 'social_registration:' . $registration),
        'purpose' => 'social_registration',
        'payload' => json_encode(['telefono' => null], JSON_THROW_ON_ERROR),
    ]);
});

test('no prepara un registro social si no acepta los términos', function () {
    $this->postJson('/api/auth/mobile/social/register', [
        'terminos_aceptados' => false,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('terminos_aceptados');
});

test('crea una cuenta Google solo después de aceptar términos y permite dejar teléfono pendiente', function () {
    $signupToken = str_repeat('g', 64);
    DB::table('auth_temporary_tokens')->insert([
        'token_hash' => hash('sha256', 'google_signup:' . $signupToken),
        'purpose' => 'google_signup',
        'payload' => json_encode([
            'correo' => 'nuevo@gmail.com',
            'Nombre' => 'Nueva Cuenta',
        ], JSON_THROW_ON_ERROR),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/google/complete-registration', [
        'signup_token' => $signupToken,
        'terminos_aceptados' => true,
    ])->assertOk()
        ->assertJsonPath('result', 'ok')
        ->assertJsonStructure(['code']);

    $this->assertDatabaseHas('Usuario', [
        'correo' => 'nuevo@gmail.com',
        'telefono' => null,
        'terminos_aceptados' => true,
    ]);
    $this->assertDatabaseMissing('auth_temporary_tokens', [
        'token_hash' => hash('sha256', 'google_signup:' . $signupToken),
    ]);
});

test('no completa un registro Google si no acepta los términos', function () {
    $this->postJson('/api/auth/google/complete-registration', [
        'signup_token' => str_repeat('g', 64),
        'terminos_aceptados' => false,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('terminos_aceptados');
});

test('devuelve al móvil si el proveedor social no está configurado', function () {
    config([
        'services.google.client_id' => null,
        'services.google.client_secret' => null,
    ]);

    $this->get('/api/auth/google/redirect')
        ->assertRedirect('bookcut://oauth/callback?error=provider_not_configured');
});

test('redirige al frontend web publicado si la configuración apunta al host de la API', function () {
    config([
        'services.frontend_url' => 'https://bookcut.api.adsocidm.com/public',
        'services.frontend_path' => '/DIST-FRONT',
    ]);

    $this->withSession(['social_auth.client' => 'web'])
        ->get('/api/auth/google/callback?error=access_denied')
        ->assertRedirect('https://book-cut.github.io/DIST-FRONT/#/auth/google/callback?error=cancelled');
});
