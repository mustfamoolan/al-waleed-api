<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RepresentativeApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Representative API Routes
|--------------------------------------------------------------------------
*/

// Public Auth
Route::post('auth/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth info
    Route::get('auth/me', [RepresentativeApiController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Dashboard & Stats
    Route::get('my/dashboard', [RepresentativeApiController::class, 'dashboard']);

    // My Managed Records
    Route::get('my/customers', [RepresentativeApiController::class, 'customers']);
    Route::get('my/sales', [RepresentativeApiController::class, 'sales']);
    Route::post('my/sales', [RepresentativeApiController::class, 'storeInvoice']);
    Route::post('my/sales/{invoice}/submit', [RepresentativeApiController::class, 'submitInvoice']);

    // Targets
    Route::get('my/targets', [RepresentativeApiController::class, 'targets']);
});
