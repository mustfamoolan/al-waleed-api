<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagerAuthController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RepresentativeController;
use App\Http\Controllers\Api\PickerController;
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

// Manager and Employee Health Check
Route::get('/manager-employee/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    return response()->json([
        'status' => 'success',
        'api' => 'Manager & Employee API',
        'message' => 'API is running and healthy',
        'database' => $dbStatus,
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0',
    ]);
});

// Manager and Employee Authentication routes
Route::prefix('manager-auth')->group(function () {
    Route::post('/login', [ManagerAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [ManagerAuthController::class, 'logout']);
        Route::get('/me', [ManagerAuthController::class, 'me']);
    });
});

// Manager and Employee Management routes (Manager only)
Route::middleware(['auth:sanctum', 'manager.only'])->group(function () {
    Route::apiResource('managers', ManagerController::class);
    Route::post('managers/{manager}/upload-image', [ManagerController::class, 'uploadImage']);
    
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/{employee}/upload-image', [EmployeeController::class, 'uploadImage']);
    
    Route::apiResource('representatives', RepresentativeController::class);
    Route::post('representatives/{representative}/upload-image', [RepresentativeController::class, 'uploadImage']);
    
    Route::apiResource('pickers', PickerController::class);
    Route::post('pickers/{picker}/upload-image', [PickerController::class, 'uploadImage']);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Add your protected routes here
});

