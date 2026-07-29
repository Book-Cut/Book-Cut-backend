<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\perfilController;
use App\Http\Controllers\api\FacturaController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('usuarios', usuarioController::class);
    Route::apiResource('citas', CitasController::class);
    Route::apiResource('servicios', ServicioController::class);
    Route::apiResource('facturas', FacturaController::class);
    Route::apiResource('perfil', perfilController::class);
    Route::apiResource('valora', ValoraController::class);
});

Route::put('servicios/{id}', [ServicioController::class, 'update']);
Route::delete('servicios/{id}', [ServicioController::class, 'destroy']);
Route::apiResource('horarios', horarioController::class);



