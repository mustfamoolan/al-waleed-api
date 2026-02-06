# دليل الطرفيات الشامل (Full API Endpoints List)

هذا المستند يحتوي على كافة مسارات الـ API المبرمجة في النظام حتى الآن، مقسمة حسب الموديولات، لاستخدامها في تطبيق الـ .NET أو Flutter.

**Base URL**: `https://maktabalwaleed.com/api`

---

## 1. الحسابات والدخول (Auth & Users)
- `POST /auth/login`: تسجيل الدخول (يرسل `phone` و `password`).
- `POST /auth/logout`: تسجيل الخروج.
- `GET /auth/me`: بيانات المستخدم الحالي.
- `GET /users`: قائمة المستخدمين.
- `PATCH /users/{id}/status`: تفعيل/تعطيل مستخدم.
- `PATCH /users/{id}/password`: تغيير كلمة السر لمستخدم.

---

## 2. البيانات الأساسية (Master Data)
- `GET /suppliers`: قائمة الموردين.
- `PATCH /suppliers/{id}/status`: تفعيل/تعطيل مورد.
- `GET /customers`: قائمة الزبائن.
- `PATCH /customers/{id}/status`: تفعيل/تعطيل زبون.
- `GET /warehouses`: قائمة المخازن.
- `PATCH /warehouses/{id}/status`: تفعيل/تعطيل مخزن.
- `GET /staff`: قائمة الموظفين (الموحدة).
- `GET /parties`: قائمة الجهات (Polymorphic Entities).

---

## 3. المنتجات والمخزون (Products & Inventory)
- `GET /products`: قائمة المنتجات مع الأسعار.
- `PATCH /products/{id}/status`: تفعيل/تعطيل منتج.
- `GET /categories`: أصناف المنتجات.
- `PATCH /categories/{id}/status`: تفعيل/تعطيل صنف.
- `GET /units`: الوحدات (كارتون، قطعة، إلخ).
- `PATCH /units/{id}/status`: تفعيل/تعطيل وحدة.
- `GET /inventory/balances`: جرد المخازن الحالي.
- `GET /inventory/transactions`: حركة المخزون.
- `POST /inventory/opening-balance`: رصيد أول المدة للمخزون.

---

## 4. دورة المبيعات (Sales Cycle)
- `GET /sales-invoices`: عرض الفواتير.
- `POST /sales-invoices`: إنشاء فاتورة جديدة.
- `POST /sales-invoices/{id}/submit`: إرسال للمراجعة.
- `POST /sales-invoices/{id}/approve`: الموافقة على الطلب.
- `POST /sales-invoices/{id}/start-preparing`: بدء التجهيز.
- `POST /sales-invoices/{id}/mark-prepared`: تم التجهيز.
- `POST /sales-invoices/{id}/assign-driver`: تعيين سائق.
- `POST /sales-invoices/{id}/out-for-delivery`: خرج للتوصيل.
- `POST /sales-invoices/{id}/mark-delivered`: تم التسليم.
- `POST /sales-returns`: إنشاء مرتجع مبيعات.

---

## 5. دورة المشتريات (Purchase Cycle)
- `GET /purchase-invoices`: عرض فواتير الشراء.
- `POST /purchase-invoices`: تسجيل فاتورة شراء.
- `POST /purchase-invoices/{id}/approve`: موافقة.
- `POST /purchase-invoices/{id}/post`: ترحيل محاسبي.
- `POST /purchase-returns`: إنشاء مرتجع مشتريات.

---

## 6. المالية والتحصيل (Finance & Cash)
- `GET /cash-accounts`: حسابات الصندوق والبنك.
- `PATCH /cash-accounts/{id}/status`: تفعيل/تعطيل حساب صندوق/بنك.
- `POST /receipts`: إنشاء سند قبض (من زبون).
- `POST /receipts/{id}/post`: ترحيل سند القبض.
- `POST /payments`: إنشاء سند صرف (لمورد أو مصروف).
- `POST /payments/{id}/post`: ترحيل سند الصرف.
- `GET /accounts`: دليل الحسابات (Tree/List).

---

## 7. الرواتب والأداء (Payroll & HR)
- `POST /attendance`: تسجيل الحضور والانصراف.
- `POST /payroll-adjustments`: إضافة (سلفة، مكافأة، خصم).
- `GET /agent-targets`: عرض الأهداف البيعية للمندوب.
- `POST /payroll-runs/calculate`: احتساب الرواتب لشهر معين.
- `POST /payroll-runs/{id}/approve`: موافقة على الرواتب.
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
> جميع الطلبات التي تعدل بيانات (POST/PUT/PATCH) يجب أن تحتوي على `Bearer Token` في الهيدر بعد تسجيل الدخول، بالإضافة إلى `Accept: application/json`.