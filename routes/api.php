<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\ServicioController;



use App\Http\Controllers\api\usuarioController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/citas', CitasController::class);
Route::apiResource('usuario', usuarioController::class);
Route::apiResource('usuarios', usuarioController::class);
Route::apiResource('servicios', ServicioController::class);
Route::put('servicios/{id}', [ServicioController::class, 'update']);
Route::delete('servicios/{id}', [ServicioController::class, 'destroy']);
