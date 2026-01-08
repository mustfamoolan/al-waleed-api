<?php

use App\Http\Controllers\Api\PickerAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Picker API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for pickers.
| These routes are loaded separately for picker applications.
|
*/

// Picker Health Check
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    return response()->json([
        'status' => 'success',
        'api' => 'Picker API',
        'message' => 'API is running and healthy',
        'database' => $dbStatus,
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0',
    ]);
});

// Picker Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [PickerAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [PickerAuthController::class, 'logout']);
        Route::get('/me', [PickerAuthController::class, 'me']);
    });
});

// Add other picker routes here

