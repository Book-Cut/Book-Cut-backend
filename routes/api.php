<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\perfilController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('usuarios', usuarioController::class);
    Route::apiResource('citas', CitasController::class);
    Route::apiResource('servicios', ServicioController::class);
    Route::apiResource('facturas', FacturaController::class);
    Route::apiResource('perfil', perfilController::class);
});

Route::put('servicios/{id}', [ServicioController::class, 'update']);
Route::delete('servicios/{id}', [ServicioController::class, 'destroy']);

