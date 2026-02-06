# دليل الطرفيات الشامل (Full API Endpoints List)

هذا المستند يحتوي على كافة مسارات الـ API المبرمجة في النظام حتى الآن، مقسمة حسب الموديولات، لاستخدامها في تطبيق الـ .NET أو Flutter.

**Base URL**: `https://maktabalwaleed.com/api`

---

## 1. الحسابات والدخول (Auth & Users)
- `POST /login`: تسجيل الدخول (يرسل `phone` و `password`).
- `POST /logout`: تسجيل الخروج.
- `GET /me`: بيانات المستخدم الحالي.

---

## 2. البيانات الأساسية (Master Data)
- `GET /suppliers`: قائمة الموردين.
- `GET /customers`: قائمة الزبائن.
- `GET /warehouses`: قائمة المخازن.
- `GET /staff`: قائمة الموظفين (الموحدة).
- `GET /parties`: قائمة الجهات (Polymorphic Entities).

---

## 3. المنتجات والمخزون (Products & Inventory)
- `GET /products`: قائمة المنتجات مع الأسعار.
- `GET /categories`: أصناف المنتجات.
- `GET /units`: الوحدات (كارتون، قطعة، إلخ).
- `GET /inventory-balances`: جرد المخازن الحالي.

---

## 4. دورة المبيعات (Sales Cycle)
- `GET /sales-invoices`: عرض الفواتير.
- `POST /sales-invoices`: إنشاء فاتورة جديدة (الحالة الافتراضية: `draft`).
- `POST /sales-invoices/{id}/submit`: إرسال للمراجعة (`pending_approval`).
- `POST /sales-invoices/{id}/approve`: الموافقة على الطلب.
- `POST /sales-invoices/{id}/prepare`: تجهيز البضاعة (تخصم من المخزن).
- `POST /sales-invoices/{id}/deliver`: تأكيد التسليم (تنشئ القيد المحاسبي).
- `POST /sales-returns`: إنشاء مرتجع مبيعات.

---

## 5. دورة المشتريات (Purchase Cycle)
- `GET /purchase-invoices`: عرض فواتير الشراء.
- `POST /purchase-invoices`: تسجيل فاتورة شراء (تزيد المخزن وتنشئ قيد دائن للمورد).
- `POST /purchase-returns`: إنشاء مرتجع مشتريات.

---

## 6. المالية والتحصيل (Finance & Cash)
- `GET /cash-accounts`: حسابات الصندوق والبنك.
- `POST /receipts`: إنشاء سند قبض (من زبون).
- `POST /receipts/{id}/post`: ترحيل سند القبض (يغلق جزء من حساب الزبون).
- `POST /payments`: إنشاء سند صرف (لمورد أو مصروف).
- `POST /payments/{id}/post`: ترحيل سند الصرف.

---

## 7. الرواتب والأداء (Payroll & HR)
- `POST /attendance`: تسجيل الحضور والانصراف.
- `POST /payroll-adjustments`: إضافة (سلفة، مكافأة، خصم).
- `GET /agent-targets`: عرض الأهداف البيعية للمندوب.
- `POST /payroll-runs/calculate`: احتساب الرواتب لشهر معين.
- `POST /payroll-runs/{id}/post`: اعتماد وصرف الرواتب.

---

## 8. التقارير والكشوفات (Reports - Stage 9)
كل هذه الروابط تدعم فلاتر `date_from` و `date_to`.

- `GET /reports/customer-statement`: كشف حساب زبون تفصيلي.
- `GET /reports/supplier-statement`: كشف حساب مورد.
- `GET /reports/customer-purchases`: ماذا اشترى الزبون؟ (تفصيلي).
- `GET /reports/staff-financials`: كشف مالي للموظف (سلف ورواتب).
- `GET /reports/debts-summary`: ملخص الديون (ما لنا وما علينا).
- `GET /reports/profit-summary`: ملخص الأرباح (صافي وإجمالي).
- `GET /reports/cash-movements`: حركة الصندوق.
- `GET /reports/product-movement`: حركة مادة معينة.
- `GET /reports/product-profit`: أرباح كل منتج.
- `GET /reports/customer-profit`: أرباح كل زبون.
- `GET /reports/agent-performance`: أداء المندوب (مبيعات + عمولات).
- `GET /reports/top-products`: المنتجات الأكثر مبيعاً.
- `GET /reports/low-products`: المنتجات الراكدة.
- `GET /reports/top-profit-products`: المنتجات الأكثر ربحية.
- `GET /reports/inventory-balances`: جرد المخازن.
- `GET /reports/aging`: تعمير الذمم (الديون المتأخرة).

---

> [!TIP]
> جميع الطلبات التي تعدل بيانات (POST/PUT) يجب أن تحتوي على `Bearer Token` في الهيدر بعد تسجيل الدخول.



PS D:\flutter\alwlid\al-waleed-api> php artisan route:list --path=api

  GET|HEAD        api/accounts ............................................ accounts.index › Api\AccountController@index
  POST            api/accounts ............................................ accounts.store › Api\AccountController@store
  GET|HEAD        api/accounts/{account} .................................... accounts.show › Api\AccountController@show
  PUT|PATCH       api/accounts/{account} ................................ accounts.update › Api\AccountController@update
  DELETE          api/accounts/{account} .............................. accounts.destroy › Api\AccountController@destroy
  GET|HEAD        api/agent-targets .............................. agent-targets.index › Api\AgentTargetController@index
  POST            api/agent-targets .............................. agent-targets.store › Api\AgentTargetController@store
  GET|HEAD        api/attendance ..................................... attendance.index › Api\AttendanceController@index
  POST            api/attendance ..................................... attendance.store › Api\AttendanceController@store
  POST            api/auth/login .............................................................. Api\AuthController@login
  POST            api/auth/logout ............................................................ Api\AuthController@logout
  GET|HEAD        api/auth/me .................................................................... Api\AuthController@me
  POST            api/auth/register ........................................................ Api\AuthController@register  
  GET|HEAD        api/cash-accounts .............................. cash-accounts.index › Api\CashAccountController@index  
  POST            api/cash-accounts .............................. cash-accounts.store › Api\CashAccountController@store  
  GET|HEAD        api/cash-accounts/{cash_account} ................. cash-accounts.show › Api\CashAccountController@show  
  PUT|PATCH       api/cash-accounts/{cash_account} ............. cash-accounts.update › Api\CashAccountController@update  
  DELETE          api/cash-accounts/{cash_account} ........... cash-accounts.destroy › Api\CashAccountController@destroy  
  GET|HEAD        api/categories ....................................... categories.index › Api\CategoryController@index  
  POST            api/categories ....................................... categories.store › Api\CategoryController@store  
  GET|HEAD        api/categories/{category} .............................. categories.show › Api\CategoryController@show  
  PUT|PATCH       api/categories/{category} .......................... categories.update › Api\CategoryController@update  
  DELETE          api/categories/{category} ........................ categories.destroy › Api\CategoryController@destroy  
  GET|HEAD        api/customers ......................................... customers.index › Api\CustomerController@index  
  POST            api/customers ......................................... customers.store › Api\CustomerController@store  
  GET|HEAD        api/customers/{customer} ................................ customers.show › Api\CustomerController@show  
  PUT|PATCH       api/customers/{customer} ............................ customers.update › Api\CustomerController@update  
  DELETE          api/customers/{customer} .......................... customers.destroy › Api\CustomerController@destroy  
  GET|HEAD        api/customers/{customer}/addresses ............................... Api\CustomerAddressController@index  
  POST            api/customers/{customer}/addresses ............................... Api\CustomerAddressController@store  
  PUT             api/customers/{customer}/addresses/{address} .................... Api\CustomerAddressController@update  
  DELETE          api/customers/{customer}/addresses/{address} ................... Api\CustomerAddressController@destroy  
  GET|HEAD        api/inventory/balances .............................................. Api\InventoryController@balances  
  POST            api/inventory/opening-balance ................................. Api\InventoryController@openingBalance  
  GET|HEAD        api/inventory/transactions ...................................... Api\InventoryController@transactions  
  GET|HEAD        api/journal-entries ................................................. Api\JournalEntryController@index  
  POST            api/journal-entries/manual .................................... Api\JournalEntryController@storeManual  
  GET|HEAD        api/journal-entries/{journalEntry} ................................... Api\JournalEntryController@show  
  POST            api/journal-entries/{journalEntry}/cancel .......................... Api\JournalEntryController@cancel  
  POST            api/journal-entries/{journalEntry}/post .............................. Api\JournalEntryController@post  
  GET|HEAD        api/parties ................................................ parties.index › Api\PartyController@index  
  GET|HEAD        api/parties/{party} .......................................... parties.show › Api\PartyController@show  
  POST            api/payments ............................................................. Api\PaymentController@store  
  GET|HEAD        api/payments/{payment} .................................................... Api\PaymentController@show  
  POST            api/payments/{payment}/allocate ....................................... Api\PaymentController@allocate  
  POST            api/payments/{payment}/post ............................................... Api\PaymentController@post  
  GET|HEAD        api/payroll-adjustments ............ payroll-adjustments.index › Api\PayrollAdjustmentController@index  
  POST            api/payroll-adjustments ............ payroll-adjustments.store › Api\PayrollAdjustmentController@store  
  GET|HEAD        api/payroll-runs ...................................................... Api\PayrollRunController@index  
  POST            api/payroll-runs/calculate ........................................ Api\PayrollRunController@calculate  
  GET|HEAD        api/payroll-runs/{run} ................................................. Api\PayrollRunController@show  
  POST            api/payroll-runs/{run}/approve ...................................... Api\PayrollRunController@approve  
  POST            api/payroll-runs/{run}/post ............................................ Api\PayrollRunController@post  
  GET|HEAD        api/products ............................................ products.index › Api\ProductController@index  
  POST            api/products ............................................ products.store › Api\ProductController@store  
  GET|HEAD        api/products/{product} .................................... products.show › Api\ProductController@show  
  PUT|PATCH       api/products/{product} ................................ products.update › Api\ProductController@update  
  DELETE          api/products/{product} .............................. products.destroy › Api\ProductController@destroy  
  POST            api/products/{product}/suppliers ................................. Api\ProductController@syncSuppliers  
  GET|HEAD        api/purchase-invoices .................. purchase-invoices.index › Api\PurchaseInvoiceController@index  
  POST            api/purchase-invoices .................. purchase-invoices.store › Api\PurchaseInvoiceController@store  
  POST            api/purchase-invoices/{purchaseInvoice}/approve ................ Api\PurchaseInvoiceController@approve  
  POST            api/purchase-invoices/{purchaseInvoice}/post ...................... Api\PurchaseInvoiceController@post  
  GET|HEAD        api/purchase-invoices/{purchase_invoice} . purchase-invoices.show › Api\PurchaseInvoiceController@show  
  PUT|PATCH       api/purchase-invoices/{purchase_invoice} purchase-invoices.update › Api\PurchaseInvoiceController@upd…  
  DELETE          api/purchase-invoices/{purchase_invoice} purchase-invoices.destroy › Api\PurchaseInvoiceController@de…  
  POST            api/purchase-returns .............................................. Api\PurchaseReturnController@store  
  POST            api/purchase-returns/{purchaseReturn}/post ......................... Api\PurchaseReturnController@post  
  POST            api/receipts ............................................................. Api\ReceiptController@store  
  GET|HEAD        api/receipts/{receipt} .................................................... Api\ReceiptController@show  
  POST            api/receipts/{receipt}/allocate ....................................... Api\ReceiptController@allocate  
  POST            api/receipts/{receipt}/post ............................................... Api\ReceiptController@post  
  GET|HEAD        api/reports/agent-performance .................................. Api\ReportController@agentPerformance  
  GET|HEAD        api/reports/aging ......................................................... Api\ReportController@aging  
  GET|HEAD        api/reports/cash-movements ........................................ Api\ReportController@cashMovements  
  GET|HEAD        api/reports/customer-profit ...................................... Api\ReportController@customerProfit  
  GET|HEAD        api/reports/customer-purchases ................................ Api\ReportController@customerPurchases  
  GET|HEAD        api/reports/customer-statement ................................ Api\ReportController@customerStatement  
  GET|HEAD        api/reports/debts-summary .......................................... Api\ReportController@debtsSummary  
  GET|HEAD        api/reports/inventory-balances ................................ Api\ReportController@inventoryBalances
  GET|HEAD        api/reports/low-products ............................................ Api\ReportController@lowProducts  
  GET|HEAD        api/reports/low-profit-products ............................... Api\ReportController@lowProfitProducts  
  GET|HEAD        api/reports/product-movement .................................... Api\ReportController@productMovement  
  GET|HEAD        api/reports/product-profit ........................................ Api\ReportController@productProfit  
  GET|HEAD        api/reports/profit-summary ........................................ Api\ReportController@profitSummary  
  GET|HEAD        api/reports/staff-financials .................................... Api\ReportController@staffFinancials  
  GET|HEAD        api/reports/supplier-statement ................................ Api\ReportController@supplierStatement  
  GET|HEAD        api/reports/top-products ............................................ Api\ReportController@topProducts  
  GET|HEAD        api/reports/top-profit-products ............................... Api\ReportController@topProfitProducts  
  GET|HEAD        api/sales-invoices ........................... sales-invoices.index › Api\SalesInvoiceController@index  
  POST            api/sales-invoices ........................... sales-invoices.store › Api\SalesInvoiceController@store  
  POST            api/sales-invoices/{invoice}/approve .............................. Api\SalesInvoiceController@approve  
  POST            api/sales-invoices/{invoice}/assign-driver ................... Api\SalesInvoiceController@assignDriver  
  POST            api/sales-invoices/{invoice}/mark-delivered ................. Api\SalesInvoiceController@markDelivered  
  POST            api/sales-invoices/{invoice}/mark-prepared ................... Api\SalesInvoiceController@markPrepared  
  POST            api/sales-invoices/{invoice}/out-for-delivery .............. Api\SalesInvoiceController@outForDelivery  
  POST            api/sales-invoices/{invoice}/start-preparing ............... Api\SalesInvoiceController@startPreparing  
  POST            api/sales-invoices/{invoice}/submit ................................ Api\SalesInvoiceController@submit  
  GET|HEAD        api/sales-invoices/{sales_invoice} ............. sales-invoices.show › Api\SalesInvoiceController@show  
  PUT|PATCH       api/sales-invoices/{sales_invoice} ......... sales-invoices.update › Api\SalesInvoiceController@update  
  DELETE          api/sales-invoices/{sales_invoice} ....... sales-invoices.destroy › Api\SalesInvoiceController@destroy  
  POST            api/sales-returns .................................................... Api\SalesReturnController@store  
  POST            api/sales-returns/{return}/post ....................................... Api\SalesReturnController@post  
  GET|HEAD        api/staff .................................................... staff.index › Api\StaffController@index  
  POST            api/staff .................................................... staff.store › Api\StaffController@store  
  GET|HEAD        api/staff/{staff} .............................................. staff.show › Api\StaffController@show  
  PUT|PATCH       api/staff/{staff} .......................................... staff.update › Api\StaffController@update  
  DELETE          api/staff/{staff} ........................................ staff.destroy › Api\StaffController@destroy  
  GET|HEAD        api/suppliers ......................................... suppliers.index › Api\SupplierController@index  
  POST            api/suppliers ......................................... suppliers.store › Api\SupplierController@store  
  GET|HEAD        api/suppliers/{supplier} ................................ suppliers.show › Api\SupplierController@show  
  PUT|PATCH       api/suppliers/{supplier} ............................ suppliers.update › Api\SupplierController@update  
  DELETE          api/suppliers/{supplier} .......................... suppliers.destroy › Api\SupplierController@destroy  
  GET|HEAD        api/units ..................................................... units.index › Api\UnitController@index  
  POST            api/units ..................................................... units.store › Api\UnitController@store  
  GET|HEAD        api/units/{unit} ................................................ units.show › Api\UnitController@show  
  PUT|PATCH       api/units/{unit} ............................................ units.update › Api\UnitController@update  
  DELETE          api/units/{unit} .......................................... units.destroy › Api\UnitController@destroy  
  GET|HEAD        api/users ..................................................... users.index › Api\UserController@index  
  POST            api/users ..................................................... users.store › Api\UserController@store  
  GET|HEAD        api/users/{user} ................................................ users.show › Api\UserController@show  
  PUT|PATCH       api/users/{user} ............................................ users.update › Api\UserController@update  
  DELETE          api/users/{user} .......................................... users.destroy › Api\UserController@destroy  
  PATCH           api/users/{user}/password .......................................... Api\UserController@changePassword  
  PATCH           api/users/{user}/status .............................................. Api\UserController@toggleStatus  
  GET|HEAD        api/warehouses ...................................... warehouses.index › Api\WarehouseController@index  
  POST            api/warehouses ...................................... warehouses.store › Api\WarehouseController@store  
  GET|HEAD        api/warehouses/{warehouse} ............................ warehouses.show › Api\WarehouseController@show  
  PUT|PATCH       api/warehouses/{warehouse} ........................ warehouses.update › Api\WarehouseController@update  
  DELETE          api/warehouses/{warehouse} ...................... warehouses.destroy › Api\WarehouseController@destroy  

                                                                                                    Showing [130] routes  

PS D:\flutter\alwlid\al-waleed-api> 