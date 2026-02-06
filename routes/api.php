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

    // Master Data
    Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
    Route::apiResource('warehouses', \App\Http\Controllers\Api\WarehouseController::class);
    Route::apiResource('parties', \App\Http\Controllers\Api\PartyController::class)->only(['index', 'show']);

    // Accounting
    Route::apiResource('accounts', \App\Http\Controllers\Api\AccountController::class);

    Route::get('journal-entries', [\App\Http\Controllers\Api\JournalEntryController::class, 'index']);
    Route::get('journal-entries/{journalEntry}', [\App\Http\Controllers\Api\JournalEntryController::class, 'show']);
    Route::post('journal-entries/manual', [\App\Http\Controllers\Api\JournalEntryController::class, 'storeManual']);
    Route::post('journal-entries/{journalEntry}/post', [\App\Http\Controllers\Api\JournalEntryController::class, 'post']);
    Route::post('journal-entries/{journalEntry}/cancel', [\App\Http\Controllers\Api\JournalEntryController::class, 'cancel']);

    // Inventory & Products
    Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);
    Route::apiResource('units', \App\Http\Controllers\Api\UnitController::class);
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::post('products/{product}/suppliers', [\App\Http\Controllers\Api\ProductController::class, 'syncSuppliers']);

    Route::get('inventory/balances', [\App\Http\Controllers\Api\InventoryController::class, 'balances']);
    Route::get('inventory/transactions', [\App\Http\Controllers\Api\InventoryController::class, 'transactions']);
    Route::post('inventory/opening-balance', [\App\Http\Controllers\Api\InventoryController::class, 'openingBalance']);

    // Purchases
    Route::apiResource('purchase-invoices', \App\Http\Controllers\Api\PurchaseInvoiceController::class);
    Route::post('purchase-invoices/{purchaseInvoice}/approve', [\App\Http\Controllers\Api\PurchaseInvoiceController::class, 'approve']);
    Route::post('purchase-invoices/{purchaseInvoice}/post', [\App\Http\Controllers\Api\PurchaseInvoiceController::class, 'post']);

    Route::post('purchase-returns', [\App\Http\Controllers\Api\PurchaseReturnController::class, 'store']);
    Route::post('purchase-returns/{purchaseReturn}/post', [\App\Http\Controllers\Api\PurchaseReturnController::class, 'post']);

    // Pre-Stage 6 (Staff & Workflow)
    Route::apiResource('staff', \App\Http\Controllers\Api\StaffController::class);
    Route::get('customers/{customer}/addresses', [\App\Http\Controllers\Api\CustomerAddressController::class, 'index']);
    Route::post('customers/{customer}/addresses', [\App\Http\Controllers\Api\CustomerAddressController::class, 'store']);
    Route::put('customers/{customer}/addresses/{address}', [\App\Http\Controllers\Api\CustomerAddressController::class, 'update']);
    Route::delete('customers/{customer}/addresses/{address}', [\App\Http\Controllers\Api\CustomerAddressController::class, 'destroy']);

    // Sales
    Route::apiResource('sales-invoices', \App\Http\Controllers\Api\SalesInvoiceController::class);
    Route::post('sales-invoices/{invoice}/submit', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'submit']);
    Route::post('sales-invoices/{invoice}/approve', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'approve']);
    Route::post('sales-invoices/{invoice}/start-preparing', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'startPreparing']);
    Route::post('sales-invoices/{invoice}/mark-prepared', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'markPrepared']);
    Route::post('sales-invoices/{invoice}/assign-driver', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'assignDriver']);
    Route::post('sales-invoices/{invoice}/out-for-delivery', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'outForDelivery']);
    Route::post('sales-invoices/{invoice}/mark-delivered', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'markDelivered']);

    Route::post('sales-returns', [\App\Http\Controllers\Api\SalesReturnController::class, 'store']);
    Route::post('sales-returns/{return}/post', [\App\Http\Controllers\Api\SalesReturnController::class, 'post']);

    // Finance (Stage 7)
    Route::apiResource('cash-accounts', \App\Http\Controllers\Api\CashAccountController::class);
    Route::patch('cash-accounts/{cashAccount}/status', [\App\Http\Controllers\Api\CashAccountController::class, 'toggleStatus']);

    Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);
    Route::patch('suppliers/{supplier}/status', [\App\Http\Controllers\Api\SupplierController::class, 'toggleStatus']);

    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
    Route::patch('customers/{customer}/status', [\App\Http\Controllers\Api\CustomerController::class, 'toggleStatus']);

    Route::get('opening-balances/status', [\App\Http\Controllers\Api\OpeningBalanceController::class, 'status']);
    Route::post('opening-balances', [\App\Http\Controllers\Api\OpeningBalanceController::class, 'store']);

    Route::post('receipts', [\App\Http\Controllers\Api\ReceiptController::class, 'store']);
    Route::get('receipts/{receipt}', [\App\Http\Controllers\Api\ReceiptController::class, 'show']);
    Route::post('receipts/{receipt}/allocate', [\App\Http\Controllers\Api\ReceiptController::class, 'allocate']);
    Route::post('receipts/{receipt}/post', [\App\Http\Controllers\Api\ReceiptController::class, 'post']);

    Route::post('payments', [\App\Http\Controllers\Api\PaymentController::class, 'store']);
    Route::get('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'show']);
    Route::post('payments/{payment}/allocate', [\App\Http\Controllers\Api\PaymentController::class, 'allocate']);
    Route::post('payments/{payment}/post', [\App\Http\Controllers\Api\PaymentController::class, 'post']);

    // Payroll & Targets (Stage 8)
    Route::apiResource('attendance', \App\Http\Controllers\Api\AttendanceController::class)->only(['index', 'store']);
    Route::apiResource('payroll-adjustments', \App\Http\Controllers\Api\PayrollAdjustmentController::class)->only(['index', 'store']);
    Route::apiResource('agent-targets', \App\Http\Controllers\Api\AgentTargetController::class)->only(['index', 'store']);

    Route::post('payroll-runs/calculate', [\App\Http\Controllers\Api\PayrollRunController::class, 'calculate']);
    Route::post('payroll-runs/{run}/approve', [\App\Http\Controllers\Api\PayrollRunController::class, 'approve']);
    Route::post('payroll-runs/{run}/post', [\App\Http\Controllers\Api\PayrollRunController::class, 'post']);
    Route::get('payroll-runs', [\App\Http\Controllers\Api\PayrollRunController::class, 'index']);
    Route::get('payroll-runs/{run}', [\App\Http\Controllers\Api\PayrollRunController::class, 'show']);

    // Reports (Stage 9)
    Route::get('reports/customer-statement', [\App\Http\Controllers\Api\ReportController::class, 'customerStatement']);
    Route::get('reports/supplier-statement', [\App\Http\Controllers\Api\ReportController::class, 'supplierStatement']);
    Route::get('reports/profit-summary', [\App\Http\Controllers\Api\ReportController::class, 'profitSummary']);
    Route::get('reports/cash-movements', [\App\Http\Controllers\Api\ReportController::class, 'cashMovements']);
    Route::get('reports/product-movement', [\App\Http\Controllers\Api\ReportController::class, 'productMovement']);
    Route::get('reports/customer-purchases', [\App\Http\Controllers\Api\ReportController::class, 'customerPurchases']);
    Route::get('reports/debts-summary', [\App\Http\Controllers\Api\ReportController::class, 'debtsSummary']);
    Route::get('reports/product-profit', [\App\Http\Controllers\Api\ReportController::class, 'productProfit']);
    Route::get('reports/top-products', [\App\Http\Controllers\Api\ReportController::class, 'topProducts']);
    Route::get('reports/low-products', [\App\Http\Controllers\Api\ReportController::class, 'lowProducts']);
    Route::get('reports/staff-financials', [\App\Http\Controllers\Api\ReportController::class, 'staffFinancials']);
    Route::get('reports/agent-performance', [\App\Http\Controllers\Api\ReportController::class, 'agentPerformance']);

    // Additional Reports
    Route::get('reports/customer-profit', [\App\Http\Controllers\Api\ReportController::class, 'customerProfit']);
    Route::get('reports/top-profit-products', [\App\Http\Controllers\Api\ReportController::class, 'topProfitProducts']);
    Route::get('reports/low-profit-products', [\App\Http\Controllers\Api\ReportController::class, 'lowProfitProducts']);
    Route::get('reports/inventory-balances', [\App\Http\Controllers\Api\ReportController::class, 'inventoryBalances']);
    Route::get('reports/aging', [\App\Http\Controllers\Api\ReportController::class, 'aging']);
});