<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\FacturaController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\perfilController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('usuarios', usuarioController::class);
    Route::apiResource('citas', CitasController::class);
    Route::apiResource('servicios', ServicioController::class);
    Route::apiResource('facturas', FacturaController::class);
    Route::apiResource('perfil', perfilController::class);
});


