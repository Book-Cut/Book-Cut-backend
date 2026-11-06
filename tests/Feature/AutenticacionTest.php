<?php

use App\Models\usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('Usuario');
    Schema::create('Usuario', function (Blueprint $table) {
        $table->id('idUsuario');
        $table->string('Nombre');
        $table->string('correo')->unique();
        $table->string('telefono');
        $table->string('contrasenha');
        $table->unsignedBigInteger('Roles_IDRol')->default(3);
        $table->boolean('terminos_aceptados')->default(false);
        $table->timestamps();
    });
});

afterEach(function () {
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
