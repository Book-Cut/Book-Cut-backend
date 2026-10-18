<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\BeneficiosController;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\FacturaController;
use App\Http\Controllers\api\GestionCitasController;
use App\Http\Controllers\api\horarioController;
use App\Http\Controllers\api\PerfilController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\ValoraController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/usuarios', [usuarioController::class, 'store']);
Route::get('/servicios', [ServicioController::class, 'index']);
Route::get('/barberos', [usuarioController::class, 'barberos']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/disponibilidad', [GestionCitasController::class, 'disponibilidad']);
    Route::patch('/citas/{id}/reagendar', [GestionCitasController::class, 'reagendar']);
    Route::patch('/citas/{id}/finalizar', [GestionCitasController::class, 'finalizar'])->name('citas.finalizar');
    Route::get('/perfil', [PerfilController::class, 'show']);
    Route::patch('/perfil', [PerfilController::class, 'update']);

    Route::apiResource('usuarios', usuarioController::class)->except(['store']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/admin/estadisticas', [FacturaController::class, 'estadisticas']);
    Route::apiResource('citas', CitasController::class)->only(['index', 'show']);
    Route::post('/citas', [GestionCitasController::class, 'store'])->name('citas.store');
    Route::post('/citas/{id}/pago', [GestionCitasController::class, 'registrarPago'])->name('citas.pago');
    Route::match(['put', 'patch'], '/citas/{cita}', [GestionCitasController::class, 'update'])->name('citas.update');
    Route::delete('/citas/{cita}', [GestionCitasController::class, 'destroy'])->name('citas.destroy');
    Route::apiResource('servicios', ServicioController::class)->except(['index']);
    Route::apiResource('facturas', FacturaController::class);

    Route::apiResource('valora', ValoraController::class);
    Route::apiResource('beneficios', BeneficiosController::class);
    Route::apiResource('horario', horarioController::class);

    Route::middleware('role:1,2,3')->get('/beneficios', [BeneficiosController::class, 'index']);
    Route::middleware('role:1,2,3')->get('/beneficios/{id}', [BeneficiosController::class, 'show']);
    Route::middleware('role:1')->post('/beneficios', [BeneficiosController::class, 'store']);
    Route::middleware('role:1')->put('/beneficios/{id}', [BeneficiosController::class, 'update']);
    Route::middleware('role:1')->delete('/beneficios/{id}', [BeneficiosController::class, 'destroy']);

    Route::middleware('role:1,2,3')->get('/facturas', [FacturaController::class, 'index']);
    Route::middleware('role:1,2,3')->get('/facturas/{id}', [FacturaController::class, 'show']);
    Route::middleware('role:1')->post('/facturas', [FacturaController::class, 'store']);
    Route::middleware('role:1')->put('/facturas/{id}', [FacturaController::class, 'update']);
    Route::middleware('role:1')->delete('/facturas/{id}', [FacturaController::class, 'destroy']);

});
