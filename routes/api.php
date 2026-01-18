<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagerAuthController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RepresentativeController;
use App\Http\Controllers\Api\PickerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\SupplierPaymentController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductSaleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleInvoiceController;
use App\Http\Controllers\Api\CustomerPaymentController;
use Illuminate\Support\Facades\DB;
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

    // Representative Salaries
    Route::get('representatives/{representative}/salaries', [\App\Http\Controllers\Api\RepresentativeSalaryController::class, 'index']);
    Route::post('representatives/{representative}/salaries', [\App\Http\Controllers\Api\RepresentativeSalaryController::class, 'store']);
    Route::get('representatives/{representative}/salaries/{salary}', [\App\Http\Controllers\Api\RepresentativeSalaryController::class, 'show']);
    Route::put('representatives/{representative}/salaries/{salary}', [\App\Http\Controllers\Api\RepresentativeSalaryController::class, 'update']);
    Route::post('representatives/{representative}/salaries/calculate', [\App\Http\Controllers\Api\RepresentativeSalaryController::class, 'calculate']);

    // Representative Targets
    Route::get('representatives/{representative}/targets', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'index']);
    Route::post('representatives/{representative}/targets', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'store']);
    Route::get('representatives/{representative}/targets/{target}', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'show']);
    Route::put('representatives/{representative}/targets/{target}', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'update']);
    Route::delete('representatives/{representative}/targets/{target}', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'destroy']);
    Route::post('representatives/{representative}/targets/{target}/calculate', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'calculateProgress']);
    Route::post('representatives/{representative}/targets/{target}/complete', [\App\Http\Controllers\Api\RepresentativeTargetController::class, 'completeTarget']);

    // Representative Balance
    Route::get('representatives/{representative}/balance', [\App\Http\Controllers\Api\RepresentativeBalanceController::class, 'show']);
    Route::get('representatives/{representative}/balance/transactions', [\App\Http\Controllers\Api\RepresentativeBalanceController::class, 'transactions']);
    Route::post('representatives/{representative}/balance/withdraw', [\App\Http\Controllers\Api\RepresentativeBalanceController::class, 'withdraw']);
    Route::post('representatives/{representative}/balance/deposit', [\App\Http\Controllers\Api\RepresentativeBalanceController::class, 'deposit']);
    Route::post('representatives/{representative}/balance/adjust', [\App\Http\Controllers\Api\RepresentativeBalanceController::class, 'adjust']);

    Route::apiResource('pickers', PickerController::class);
    Route::post('pickers/{picker}/upload-image', [PickerController::class, 'uploadImage']);

    // Suppliers Management
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/upload-image', [SupplierController::class, 'uploadImage']);
    Route::get('suppliers/{supplier}/balance', [SupplierController::class, 'balance']);
    Route::get('suppliers/{supplier}/summary', [SupplierController::class, 'summary']);
    Route::get('suppliers/{supplier}/invoices', [PurchaseInvoiceController::class, 'index']);

    // Purchase Invoices
    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class);
    Route::post('purchase-invoices/{purchase_invoice}/duplicate', [PurchaseInvoiceController::class, 'duplicate']);
    Route::post('purchase-invoices/{purchase_invoice}/post', [PurchaseInvoiceController::class, 'post']);
    Route::get('purchase-invoices/{purchase_invoice}/payments', [SupplierPaymentController::class, 'index']);

    // Supplier Payments
    Route::apiResource('supplier-payments', SupplierPaymentController::class);
    Route::get('suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index']);

    // Purchase Returns
    Route::apiResource('purchase-returns', PurchaseReturnController::class);
    Route::post('purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post']);

    // Accounts (Chart of Accounts)
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/{account}/transactions', [AccountController::class, 'transactions']);
    Route::get('accounts/{account}/balance', [AccountController::class, 'balance']);

    // Journal Entries
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post']);
    Route::get('journal-entries/{journal_entry}/lines', [JournalEntryController::class, 'lines']);

    // Reports & Analytics
    Route::get('suppliers/{supplier}/profit', [ReportController::class, 'supplierProfit']);
    Route::get('suppliers/{supplier}/purchases-summary', [ReportController::class, 'purchasesSummary']);
    Route::get('suppliers/{supplier}/price-comparison', [ReportController::class, 'priceComparison']);
    Route::get('reports/financial-summary', [ReportController::class, 'financialSummary']);
    Route::get('reports/suppliers-report', [ReportController::class, 'suppliersReport']);

    // Categories Management
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);

    // Products Management
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/upload-image', [ProductController::class, 'uploadImage']);
    Route::get('products/{product}/stock', [ProductController::class, 'stock']);
    Route::get('products/{product}/movements', [ProductController::class, 'movements']);
    Route::get('products/{product}/sales', [ProductController::class, 'sales']);
    Route::get('products/{product}/profit', [ProductController::class, 'profit']);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('products/stock-report', [ProductController::class, 'stockReport']);
    Route::get('products/profit-report', [ProductController::class, 'profitReport']);
    Route::get('products/sales-report', [ProductController::class, 'salesReport']);

    // Inventory Movements
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);

    // Product Sales
    Route::apiResource('product-sales', ProductSaleController::class);
    Route::get('product-sales/profit-report', [ProductSaleController::class, 'profitReport']);

    // Customers Management
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/{customer}/balance', [CustomerController::class, 'balance']);
    Route::get('customers/{customer}/transactions', [CustomerController::class, 'transactions']);
    Route::get('customers/{customer}/invoices', [CustomerController::class, 'invoices']);
    Route::get('customers/{customer}/representatives', [CustomerController::class, 'representatives']);
    Route::post('customers/{customer}/representatives/{representative}', [CustomerController::class, 'assignRepresentative']);
    Route::delete('customers/{customer}/representatives/{representative}', [CustomerController::class, 'removeRepresentative']);

    // Sale Invoices (Smart)
    Route::apiResource('sale-invoices', SaleInvoiceController::class);
    Route::post('sale-invoices/{sale_invoice}/duplicate', [SaleInvoiceController::class, 'duplicate']);
    Route::post('sale-invoices/{sale_invoice}/post', [SaleInvoiceController::class, 'post']);
    Route::get('sale-invoices/{sale_invoice}/payments', [SaleInvoiceController::class, 'payments']);

    // Customer Payments
    Route::apiResource('customer-payments', CustomerPaymentController::class);
    Route::post('customer-payments/{payment}/apply-to-invoice/{invoice}', [CustomerPaymentController::class, 'applyToInvoice']);

    // Representative Sales (المبيعات من المندوب)
    Route::get('representatives/{representative}/sales', [SaleInvoiceController::class, 'index']);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Add your protected routes here
});

