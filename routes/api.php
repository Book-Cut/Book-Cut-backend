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
    Route::apiResource('beneficios', BeneficiosController::class);
    Route::apiResource("horario", horarioController::class);

    Route::middleware('role:1,2,3')->get('/perfil', [perfilController::class, 'index']);
    Route::middleware('role:1,2,3')->get('/perfil/{id}', [perfilController::class, 'show']);
    Route::middleware('role:1')->post('/perfil', [perfilController::class, 'store']);
    Route::middleware('role:1')->put('/perfil/{id}', [perfilController::class, 'update']);
    Route::middleware('role:1')->delete('/perfil/{id}', [perfilController::class, 'destroy']);

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



