Route::prefix('auth')->group(function () {
Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
Route::apiResource('users', App\Http\Controllers\Api\UserController::class);

// Accounts
Route::prefix('accounts')->group(function () {
Route::get('/', [App\Http\Controllers\Api\AccountController::class, 'index']); // Tree
Route::get('/list', [App\Http\Controllers\Api\AccountController::class, 'list']); // List
Route::post('/', [App\Http\Controllers\Api\AccountController::class, 'store']); // Create
});
});