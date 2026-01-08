<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is running',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Test deployment route
Route::get('/test-deployment', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Automatic deployment is working! 🚀',
        'deployment_test' => true,
        'timestamp' => now()->toDateTimeString(),
        'server_time' => now()->format('Y-m-d H:i:s'),
        'app_env' => env('APP_ENV', 'unknown'),
        'app_name' => env('APP_NAME', 'Al-Waleed API'),
        'version' => '1.0.0',
        'features' => [
            'automatic_deployment' => true,
            'database_migrations' => true,
            'storage_link' => true,
            'docker_compose' => true,
        ],
    ]);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Add your protected routes here
});

