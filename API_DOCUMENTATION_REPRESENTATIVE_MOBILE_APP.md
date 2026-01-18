# API Documentation: Representative Mobile Application

## نظرة عامة

API متكامل لتطبيق المندوب على الموبايل. يسمح للمندوب بإدارة زبائنه، إنشاء طلبات فواتير البيع، وعرض حساباته الشخصية.

**Base URL:** `/representative-api`

---

## Authentication

جميع الـ endpoints تحتاج Authentication باستخدام Laravel Sanctum. يجب إرسال Bearer token في Authorization header.

**Login Endpoint:** `POST /representative-api/auth/login`

**Headers:**
- `Authorization: Bearer {token}` (لجميع الـ endpoints بعد login)

---

## 1. Customers Management (إدارة الزبائن)

### 1.1 قائمة زبائن المندوب

**Endpoint:** `GET /representative-api/my/customers`

**Query Parameters:**
- `search` (optional): البحث بالاسم أو رقم الهاتف
- `per_page` (optional): عدد العناصر في الصفحة (default: 15)

**Response Fields:**
- `customer_id`, `customer_name`, `phone_number`, `address`, `location_lat`, `location_lng`
- `total_debt`, `total_paid`, `status`
- `balance` (object): `current_balance`, `total_debt`, `total_paid`
- `representatives` (array)

---

### 1.2 تفاصيل زبون محدد

**Endpoint:** `GET /representative-api/my/customers/{customer}`

**Response:** نفس حقول قائمة الزبائن مع تفاصيل إضافية

**القيود:** المندوب يرى فقط الزبائن المخصصين له

---

### 1.3 فواتير زبون محدد

**Endpoint:** `GET /representative-api/my/customers/{customer}/invoices`

**Query Parameters:**
- `request_status` (optional): `pending_approval`, `approved`, `rejected`
- `delivery_status` (optional): `not_prepared`, `preparing`, `prepared`, `assigned_to_driver`, `in_delivery`, `delivered`, `cancelled`
- `status` (optional): `pending`, `paid`, `partial`, `overdue`, `cancelled`
- `per_page` (optional)

**Response:** قائمة فواتير البيع للزبون مع حالة الموافقة والتوصيل

---

### 1.4 كشف حساب زبون

**Endpoint:** `GET /representative-api/my/customers/{customer}/balance`

**Response Fields:**
- `balance`: رصيد الزبون الحالي
- `recent_transactions`: آخر 20 معاملة
- `summary`: `total_invoices`, `total_debt`, `total_paid`

---

### 1.5 تحديث موقع الزبون

**Endpoint:** `PUT /representative-api/my/customers/{customer}/location`

**Request Body:**
- `address` (optional): العنوان الجديد
- `location_lat` (optional): خط العرض (-90 to 90)
- `location_lng` (optional): خط الطول (-180 to 180)

**القيود:** المندوب يستطيع تحديث موقع زبائنه فقط

---

## 2. Sale Invoices (طلبات فواتير البيع)

### 2.1 قائمة طلبات الفواتير

**Endpoint:** `GET /representative-api/my/sales`

**Query Parameters:**
- `request_status` (optional): `pending_approval`, `approved`, `rejected`
- `delivery_status` (optional): جميع حالات التوصيل
- `customer_id` (optional): فلترة حسب الزبون
- `status` (optional): حالة الدفع
- `per_page` (optional)

**Response:** قائمة طلبات فواتير البيع للمندوب

**ملاحظة:** الفواتير تحتاج موافقة المدير قبل التجهيز (`request_status = pending_approval` → `approved`)

---

### 2.2 إنشاء طلب فاتورة بيع

**Endpoint:** `POST /representative-api/my/sales`

**Request Body:**
- `customer_id` (required): يجب أن يكون من زبائن المندوب
- `invoice_number` (required): رقم فريد
- `invoice_date` (required): تاريخ الفاتورة
- `due_date` (optional): تاريخ الاستحقاق (للزبائن)
- `subtotal` (required): المجموع الفرعي
- `tax_amount` (optional): قيمة الضريبة
- `discount_amount` (optional): قيمة الخصم
- `total_amount` (required): المجموع الكلي
- `payment_method` (optional): `cash` أو `credit` (default: credit للزبائن)
- `notes` (optional)
- `items` (required, array): قائمة المنتجات
  - `product_id` (required)
  - `quantity` (required)
  - `unit_price` (required)
  - `discount_percentage` (optional)
  - `tax_percentage` (optional)

**Auto-set Fields:**
- `representative_id` = المندوب المسجل دخول تلقائياً
- `request_type` = `representative`
- `request_status` = `pending_approval`
- `delivery_status` = `not_prepared`
- `status` = `pending`
- `buyer_type` = `customer`

**Response:** الفاتورة/الطلب مع جميع التفاصيل

**القيود:**
- فقط للزبائن المخصصين للمندوب
- التحقق من توفر المخزون قبل الإنشاء
- العنوان والموقع يأخذان من جدول customers

---

### 2.3 عرض فاتورة محددة

**Endpoint:** `GET /representative-api/my/sales/{sale_invoice}`

**Response:** تفاصيل الفاتورة الكاملة مع:
- `request_status`: حالة الموافقة
- `delivery_status`: حالة التوصيل
- `status`: حالة الدفع
- جميع التفاصيل والعلاقات

---

### 2.4 تحديث طلب فاتورة (فقط قبل الموافقة)

**Endpoint:** `PUT /representative-api/my/sales/{sale_invoice}`

**القيود:** فقط إذا `request_status = pending_approval`

**ملاحظة:** حالياً غير مكتمل التطبيق - يفضل الإلغاء وإنشاء طلب جديد

---

### 2.5 إلغاء طلب فاتورة

**Endpoint:** `POST /representative-api/my/sales/{sale_invoice}/cancel`

**القيود:** فقط إذا `request_status = pending_approval`

**Response:** يتم تغيير `request_status` إلى `rejected` و `delivery_status` إلى `cancelled`

---

## 3. My Account (حسابي الشخصي)

### 3.1 أهدافي

**Endpoint:** `GET /representative-api/my/targets`

**Response:** قائمة أهداف المندوب مع حالة الإنجاز

---

### 3.2 تفاصيل هدف محدد

**Endpoint:** `GET /representative-api/my/targets/{target}`

**Response:** تفاصيل الهدف الكاملة مع نسبة الإنجاز

---

### 3.3 رصيدي

**Endpoint:** `GET /representative-api/my/balance`

**Response:** رصيد المندوب الحالي وملخص الحركات

---

### 3.4 معاملات رصيدي

**Endpoint:** `GET /representative-api/my/balance/transactions`

**Response:** قائمة جميع معاملات الرصيد

---

### 3.5 راتبي

**Endpoint:** `GET /representative-api/my/salary/{month?}`

**Query Parameters:**
- `month` (optional): الشهر بصيغة Y-m (مثال: 2026-01)

**Response:** تفاصيل الراتب الشهري مع المكافآت من الأهداف

---

## 4. سير العمل (Workflow)

### 4.1 سير عمل الفاتورة

```
1. المندوب → إنشاء طلب فاتورة (request_status = pending_approval)
   ↓
2. المدير → الموافقة (request_status = approved)
   ↓
3. المجهز → التجهيز (delivery_status = preparing → prepared)
   ↓
4. المجهز → تعيين السائق (delivery_status = assigned_to_driver)
   ↓
5. السائق → التوصيل (delivery_status = in_delivery → delivered)
```

### 4.2 حالات الفاتورة

**حالة الموافقة (`request_status`):**
- `pending_approval`: في انتظار موافقة المدير
- `approved`: معتمدة من المدير
- `rejected`: مرفوضة من المدير

**حالة التوصيل (`delivery_status`):**
- `not_prepared`: لم تجهز
- `preparing`: في التجهيز
- `prepared`: تم التجهيز
- `assigned_to_driver`: معينة للسائق
- `in_delivery`: في التوصيل
- `delivered`: تم التسليم
- `cancelled`: ملغية

**حالة الدفع (`status`):**
- `pending`: في الانتظار (دين)
- `paid`: مدفوعة كاملة
- `partial`: مدفوعة جزئياً
- `overdue`: متأخرة
- `cancelled`: ملغية

---

## 5. Business Logic

### 5.1 قيود المندوب

- المندوب يرى فقط الزبائن المخصصين له
- المندوب يستطيع إنشاء فواتير فقط للزبائن المخصصين له
- جميع الفواتير تحتاج موافقة المدير قبل التجهيز
- العنوان والموقع تُحدث في جدول customers

### 5.2 حساب الأهداف

- يتم حساب أهداف المندوب بناءً على الفواتير المكتملة (`delivery_status = delivered`)
- فقط الفواتير الخاصة بالمندوب (`representative_id = rep_id`)
- يتم احتساب المكافآت تلقائياً بناءً على تحقيق الأهداف

---

## 6. Error Handling

جميع الـ errors تُرجع بصيغة:

```json
{
  "status": "error",
  "message": "Error message"
}
```

**Status Codes:**
- `200`: Success
- `201`: Created
- `401`: Unauthenticated
- `403`: Unauthorized (ليس لديك صلاحية)
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

---

## 7. ملاحظات مهمة

1. جميع الفواتير تحتاج موافقة المدير قبل التجهيز
2. العنوان والموقع يُحفظان في جدول customers وليس في الفاتورة
3. المندوب يستطيع تحديث موقع الزبون في أي وقت
4. يمكن إلغاء الطلب فقط قبل الموافقة
5. حالة التوصيل تتبع سير عمل صارم ولا يمكن القفز على الخطوات

---

## 8. Authentication Flow

1. Login: `POST /representative-api/auth/login`
   - Body: `phone_number`, `password`
   - Response: `token`, `user`, `token_type`

2. استخدام Token في جميع الـ requests:
   - Header: `Authorization: Bearer {token}`

3. Logout: `POST /representative-api/auth/logout`
   - Headers: `Authorization: Bearer {token}`

4. Get Me: `GET /representative-api/auth/me`
   - Headers: `Authorization: Bearer {token}`
   - Response: بيانات المندوب المسجل دخول

---

## 9. Response Format

جميع الـ responses بصيغة:

```json
{
  "status": "success",
  "data": {...},
  "message": "Optional message"
}
```

للـ pagination:

```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 50,
    "per_page": 15,
    "last_page": 4
  }
}
```

