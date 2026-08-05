<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\perfilController;
use App\Http\Controllers\api\FacturaController;
use App\Http\Controllers\api\ValoraController;
use App\Http\Controllers\api\horarioController;
use App\Http\Controllers\api\BeneficiosController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);




Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('usuarios', usuarioController::class);
    Route::apiResource('citas', CitasController::class);
    Route::apiResource('servicios', ServicioController::class);
    Route::apiResource('facturas', FacturaController::class);
    Route::apiResource('perfil', perfilController::class);
    Route::apiResource('valora', ValoraController::class);
    Route::apiResource('horarios', horarioController::class);
    Route::apiResource('beneficios', BeneficiosController::class);
    Route::apiResource("horario", horarioController::class);
});


