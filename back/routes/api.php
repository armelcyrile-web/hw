

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
// Authentification
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Routes nécessitant une authentification par token Bearer
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notifications/non-lues-count', [NotificationController::class, 'countNonLues']);
Route::post('/notifications/{id}/lue', [NotificationController::class, 'marquerCommeLue']);
Route::post('/notifications/toutes-lues', [NotificationController::class, 'marquerToutesCommeLues']);

    // Ressource sites (accessible à tous les rôles, restrictions dans les policies)
    Route::apiResource('sites', SiteController::class);

    // Tickets : consultation et création
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);

    // Routes pour techniciens et administrateurs (staff)
    Route::middleware('role:technicien,administrateur')->prefix('staff')->group(function (): void {
        Route::post('tickets/{ticket}/prendre-en-charge', [TicketController::class, 'prendreEnCharge']);
        Route::post('tickets/{ticket}/resoudre', [TicketController::class, 'resoudre']);
        Route::post('tickets/{ticket}/liberer', [TicketController::class, 'liberer']);
    });

    // Routes réservées à l'administrateur
    Route::middleware('role:administrateur')->prefix('admin')->group(function (): void {
    Route::post('tickets/{ticket}/assigner', [TicketController::class, 'assigner']);
    Route::get('stats', [StatsController::class, 'index']);
    Route::apiResource('users', UserController::class);
});
});

