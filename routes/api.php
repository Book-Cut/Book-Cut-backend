<?php

use App\Http\Controllers\api\AutenticacionController;
use App\Http\Controllers\api\BeneficiosController;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\FacturaController;
use App\Http\Controllers\api\GestionCitasController;
use App\Http\Controllers\api\NotificacionController;
use App\Http\Controllers\api\horarioController;
use App\Http\Controllers\api\PerfilController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\ValoraController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AutenticacionController::class, 'login']);
Route::post('/register', [AutenticacionController::class, 'register']);


Route::post('/register/send-code', [AutenticacionController::class, 'sendRegisterCode']);
Route::post('/register/verify', [AutenticacionController::class, 'verifyRegisterCode']);
Route::post('/forgot-password', [AutenticacionController::class, 'forgotPassword']);
Route::post('/reset-password', [AutenticacionController::class, 'resetPassword']);
Route::post('/Citas/confirmar-email', [NotificacionController::class, 'confirmarPorCorreo'])->middleware('throttle:10,1');

Route::post('/usuarios', [usuarioController::class, 'store']);
Route::get('/servicios', [ServicioController::class, 'index']);
Route::get('/barberos', [usuarioController::class, 'barberos']);
Route::get('/beneficios-vigentes', [BeneficiosController::class, 'vigentes']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/disponibilidad', [GestionCitasController::class, 'disponibilidad']);
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::patch('/Citas/{id}/reagendar', [GestionCitasController::class, 'reagendar']);
    Route::patch('/Citas/{id}/confirmar-asistencia', [GestionCitasController::class, 'confirmarAsistencia']);
    Route::patch('/Citas/{id}/finalizar', [GestionCitasController::class, 'finalizar'])->name('Citas.finalizar');
    Route::get('/perfil', [PerfilController::class, 'show']);
    Route::patch('/perfil', [PerfilController::class, 'update']);

    Route::apiResource('usuarios', usuarioController::class)->except(['store']);

    Route::post('/logout', [AutenticacionController::class, 'logout']);
    Route::get('/admin/estadisticas', [FacturaController::class, 'estadisticas']);
    Route::apiResource('Citas', CitasController::class)->only(['index', 'show']);
    Route::post('/Citas', [GestionCitasController::class, 'store'])->name('Citas.store');
    Route::post('/Citas/{id}/pago', [GestionCitasController::class, 'registrarPago'])->name('Citas.pago');
    Route::match(['put', 'patch'], '/Citas/{cita}', [GestionCitasController::class, 'update'])->name('Citas.update');
    Route::delete('/Citas/{cita}', [GestionCitasController::class, 'destroy'])->name('Citas.destroy');
    Route::apiResource('servicios', ServicioController::class)->except(['index']);
    Route::apiResource('facturas', FacturaController::class);

    Route::post('/Citas/{id}/valoracion', [ValoraController::class, 'guardarValoracionCita']);
    Route::get('/valoraciones', [ValoraController::class, 'listarValoraciones']);
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
