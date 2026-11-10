<?php
use App\Http\Controllers\api\AutenticacionController;
use App\Http\Controllers\api\BeneficiosController;
use App\Http\Controllers\api\CitaAfectadaController;
use App\Http\Controllers\api\CitasController;
use App\Http\Controllers\api\FacturaController;
use App\Http\Controllers\api\GestionCitasController;
use App\Http\Controllers\api\horarioController;
use App\Http\Controllers\api\IndisponibilidadController;
use App\Http\Controllers\api\NotificacionController;
use App\Http\Controllers\api\NotificacionWebController;
use App\Http\Controllers\api\PerfilController;
use App\Http\Controllers\api\ServicioController;
use App\Http\Controllers\api\usuarioController;
use App\Http\Controllers\api\ValoraController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AutenticacionController::class, 'login']);
Route::post('/registro', [AutenticacionController::class, 'registro']);
Route::post('/auth/mobile/social/register', [SocialAuthController::class, 'startRegistration'])
    ->middleware('throttle:10,1');
Route::post('/auth/mobile/exchange', [SocialAuthController::class, 'exchange'])
    ->middleware('throttle:10,1');

Route::post('/registro/send-code', [AutenticacionController::class, 'sendRegisterCode']);
Route::post('/registro/verify', [AutenticacionController::class, 'verifyRegisterCode']);
Route::post('/forgot-password', [AutenticacionController::class, 'forgotPassword']);
Route::post('/reset-password', [AutenticacionController::class, 'resetPassword']);
Route::post('/citas/confirmar-email', [NotificacionController::class, 'confirmarPorCorreo'])->middleware('throttle:10,1');

Route::post('/usuarios', [usuarioController::class, 'store']);
Route::get('/servicios', [ServicioController::class, 'index']);
Route::get('/barberos', [usuarioController::class, 'barberos']);
Route::get('/beneficios-vigentes', [BeneficiosController::class, 'vigentes']);



Route::get('/notificaciones', [NotificacionWebController::class, 'index']);
Route::post('/notificaciones/{id}/leida', [NotificacionWebController::class, 'marcarLeida']);

Route::post('/cita-afectada/{id}/cancelar', [CitaAfectadaController::class, 'cancelar']);
Route::get('/cita-afectada/{id}/horarios', [CitaAfectadaController::class, 'horarios']);
Route::post('/cita-afectada/{id}/reagendar', [CitaAfectadaController::class, 'reagendar']);
Route::get('/cita-afectada/{id}/barberos-alternativos', [CitaAfectadaController::class, 'barberosAlternativos']);
Route::post('/cita-afectada/{id}/otro-barbero', [CitaAfectadaController::class, 'otroBarbero']);

// Endpoint seguro para invocar el procesamiento de confirmaciones vía Webhook/Cron externo
Route::get('/cron/procesar-confirmaciones', function (Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $tokenEsperado = config('app.cron_token', env('CRON_TOKEN', 'bookcut_cron_secret_2026'));
    if ($token !== $tokenEsperado) {
        return response()->json(['error' => 'No autorizado'], 403);
    }
    Artisan::call('citas:procesar-confirmaciones');

    return response()->json([
        'status' => 'success',
        'mensaje' => 'Confirmaciones procesadas correctamente',
        'ejecutado_at' => now()->toDateTimeString(),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/disponibilidad', [GestionCitasController::class, 'disponibilidad']);
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::patch('/citas/{id}/reagendar', [GestionCitasController::class, 'reagendar']);
    Route::patch('/citas/{id}/confirmar-asistencia', [GestionCitasController::class, 'confirmarAsistencia']);
    Route::patch('/citas/{id}/finalizar', [GestionCitasController::class, 'finalizar'])->name('citas.finalizar');
    Route::get('/perfil', [PerfilController::class, 'show']);
    Route::patch('/perfil', [PerfilController::class, 'update']);
    Route::post('/perfil/foto', [PerfilController::class, 'actualizarFoto']);
    Route::post('/indisponibilidad', [IndisponibilidadController::class, 'store']);

    Route::apiResource('usuarios', usuarioController::class)->except(['store']);

    Route::post('/logout', [AutenticacionController::class, 'logout']);
    Route::get('/admin/estadisticas', [FacturaController::class, 'estadisticas']);
    Route::apiResource('citas', CitasController::class)->only(['index', 'show']);
    Route::post('/citas', [GestionCitasController::class, 'store'])->name('citas.store');
    Route::post('/citas/{id}/pago', [GestionCitasController::class, 'registrarPago'])->name('citas.pago');
    Route::match(['put', 'patch'], '/citas/{cita}', [GestionCitasController::class, 'update'])->name('citas.update');
    Route::delete('/citas/{cita}', [GestionCitasController::class, 'destroy'])->name('citas.destroy');
    Route::apiResource('servicios', ServicioController::class)->except(['index']);
    Route::apiResource('facturas', FacturaController::class);

    Route::post('/citas/{id}/valoracion', [ValoraController::class, 'guardarValoracionCita']);
    Route::get('/valoraciones', [ValoraController::class, 'listarvaloraciones']);
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
