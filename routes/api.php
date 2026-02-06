<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']); // Optional public register

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Users Management
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus']);
    Route::patch('users/{user}/password', [UserController::class, 'changePassword']);

    // Accounting
    Route::apiResource('accounts', \App\Http\Controllers\Api\AccountController::class);

    Route::get('journal-entries', [\App\Http\Controllers\Api\JournalEntryController::class, 'index']);
    Route::get('journal-entries/{journalEntry}', [\App\Http\Controllers\Api\JournalEntryController::class, 'show']);
    Route::post('journal-entries/manual', [\App\Http\Controllers\Api\JournalEntryController::class, 'storeManual']);
    Route::post('journal-entries/{journalEntry}/post', [\App\Http\Controllers\Api\JournalEntryController::class, 'post']);
    Route::post('journal-entries/{journalEntry}/cancel', [\App\Http\Controllers\Api\JournalEntryController::class, 'cancel']);
});