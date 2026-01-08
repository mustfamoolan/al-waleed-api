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

// Picker Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [PickerAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [PickerAuthController::class, 'logout']);
        Route::get('/me', [PickerAuthController::class, 'me']);
    });
});

// Add other picker routes here

