<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider (or application bootstrapper)
| and all of them will be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Rutas públicas que requieren contexto de Tenant
    Route::middleware(['tenant'])->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Ruta de refresco de token (resuelve contexto de Tenant internamente a partir del token)
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Rutas protegidas que requieren autenticación JWT
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });
});
