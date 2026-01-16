<?php

use App\Http\Controllers\Api\RepresentativeAuthController;
use App\Http\Controllers\Api\RepresentativeBalanceController;
use App\Http\Controllers\Api\RepresentativeSalaryController;
use App\Http\Controllers\Api\RepresentativeTargetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Representative API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for representatives.
| These routes are loaded separately for representative applications.
|
*/

// Representative Health Check
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    return response()->json([
        'status' => 'success',
        'api' => 'Representative API',
        'message' => 'API is running and healthy',
        'database' => $dbStatus,
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0',
    ]);
});

// Representative Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [RepresentativeAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [RepresentativeAuthController::class, 'logout']);
        Route::get('/me', [RepresentativeAuthController::class, 'me']);
    });
});

// Representative routes (Authenticated)
Route::middleware('auth:sanctum')->group(function () {
    // My Targets
    Route::get('/my/targets', [RepresentativeTargetController::class, 'myTargets']);
    Route::get('/my/targets/{target}', [RepresentativeTargetController::class, 'showMyTarget']);
    
    // My Balance
    Route::get('/my/balance', [RepresentativeBalanceController::class, 'myBalance']);
    Route::get('/my/balance/transactions', [RepresentativeBalanceController::class, 'myTransactions']);
    
    // My Salary
    Route::get('/my/salary/{month?}', [RepresentativeSalaryController::class, 'mySalary']);
});

