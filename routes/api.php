<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenDePagoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\SeedController;
use App\Http\Middleware\CheckAdminRole;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\LogController;

// Rutas públicas
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);

// Rutas para recuperación de contraseña
Route::post('/forgot-password/check-email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'checkUserEmail']);
Route::post('/forgot-password/generate-token', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'generateResetToken']);
Route::post('/forgot-password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'resetPassword']);

// Rutas para colegios - Acceso público solo para listar y ver detalles
Route::get('/colegios', [ColegioController::class, 'index']);
Route::get('/colegios/{id}', [ColegioController::class, 'show']);

// Rutas públicas para obtener información
Route::get('/areas', [AreaController::class, 'index']);
Route::get('/convocatorias-abiertas', [ConvocatoriaController::class, 'convocatoriasAbiertas']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Rutas de usuarios
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Rutas de estudiantes
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::get('/students/{id}/areas', [StudentController::class, 'getAreas']);
    
    // Ruta para obtener el perfil del estudiante actual
    Route::get('/student/profile', [StudentController::class, 'getCurrentProfile']);
    Route::get('/students/{id}/available-areas', [StudentController::class, 'getAvailableAreas']);
    Route::post('/students/{id}/enroll-areas', [StudentController::class, 'enrollInAreas']);

    // Rutas de colegios (protegidas)
    Route::post('/colleges', [CollegeController::class, 'store']);
    Route::put('/colleges/{id}', [CollegeController::class, 'update']);
    Route::delete('/colleges/{id}', [CollegeController::class, 'destroy']);

    // Rutas de áreas (protegidas)
    Route::get('/areas/active', [AreaController::class, 'getActiveAreas']);
    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{id}', [AreaController::class, 'update']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

    // Rutas para órdenes de pago
    Route::post('/ordenes', [OrdenDePagoController::class, 'generarOrden']);
    Route::get('/ordenes/{id}/pdf', [OrdenDePagoController::class, 'generarPDF']);
    Route::get('/ordenes', [OrdenDePagoController::class, 'listarOrdenes']);
    Route::patch('/ordenes/{id}/aprobar', [OrdenDePagoController::class, 'aprobarPago']);
    Route::patch('/ordenes/{id}/rechazar', [OrdenDePagoController::class, 'rechazarPago']);

    // Rutas para logs
    Route::post('/logs', [LogController::class, 'store']);
    Route::get('/logs', [LogController::class, 'index'])->middleware('auth');
});

// Rutas solo para administradores
Route::middleware(['auth:sanctum', CheckAdminRole::class])->group(function () {
    // Rutas para convocatorias
    Route::apiResource('/convocatorias', ConvocatoriaController::class);
    Route::patch('/convocatorias/{id}/areas', [ConvocatoriaController::class, 'actualizarAreas']);
});

// Ruta temporal para seeder de colegios
Route::get('/seed/colegios', [SeedController::class, 'seedColegios']);

// Ruta de prueba
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
            'message' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});
