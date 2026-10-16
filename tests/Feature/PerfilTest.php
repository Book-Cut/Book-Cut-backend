<?php

use App\Models\usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Schema::dropIfExists('usuario');
    Schema::create('usuario', function (Blueprint $table) {
        $table->id('idUsuario');
        $table->string('Nombre');
        $table->string('correo')->unique();
        $table->string('telefono');
        $table->string('contrasenha');
        $table->string('foto_perfil')->nullable();
        $table->unsignedBigInteger('Roles_IDRol')->default(3);
    });
});

afterEach(function () {
    Schema::dropIfExists('usuario');
});

test('el perfil requiere una sesión', function () {
    $this->getJson('/api/perfil')->assertUnauthorized();
});

test('permite consultar y actualizar únicamente el perfil autenticado', function () {
    $usuario = new usuario;
    $usuario->Nombre = 'Ana';
    $usuario->correo = 'ana@example.com';
    $usuario->telefono = '5551234567';
    $usuario->contrasenha = Hash::make('anterior');
    $usuario->Roles_IDRol = 3;
    $usuario->save();
    Sanctum::actingAs($usuario);

    $this->getJson('/api/perfil')
        ->assertOk()
        ->assertJsonPath('data.Nombre', 'Ana')
        ->assertJsonPath('data.correo', 'ana@example.com');

    $this->patchJson('/api/perfil', [
        'Nombre' => 'Ana López',
        'correo' => 'ana.lopez@example.com',
        'telefono' => '5559876543',
        'contrasenha' => 'nueva123',
        'Roles_IDRol' => 1,
    ])
        ->assertOk()
        ->assertJsonPath('data.Nombre', 'Ana López')
        ->assertJsonPath('data.correo', 'ana.lopez@example.com');

    expect($usuario->fresh()->Roles_IDRol)->toBe(3)
        ->and($usuario->fresh()->telefono)->toBe('5559876543')
        ->and(Hash::check('nueva123', $usuario->fresh()->contrasenha))->toBeTrue();
});
