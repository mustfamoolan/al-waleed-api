# API Documentation: Driver Mobile Application

## نظرة عامة

API متكامل لتطبيق السائق على الموبايل. يسمح للسائق بإدارة الفواتير المخصصة له، التوصيل، الإرجاعات، وتسجيل الدفعات.

**Base URL:** `/api`

**Authentication:** Laravel Sanctum (مثل المندوب)

---

## 1. Invoices Management (إدارة الفواتير)

### 1.1 قائمة الفواتير المخصصة للسائق

**Endpoint:** `GET /api/driver/invoices`

**Query Parameters:**
- `delivery_status` (optional): `assigned_to_driver`, `in_delivery`
- `per_page` (optional)

**Response:** قائمة الفواتير المخصصة للسائق فقط

**القيود:** السائق يرى فقط الفواتير التي `assigned_to_driver = driver_id`

---

### 1.2 تفاصيل فاتورة مع موقع الزبون

**Endpoint:** `GET /api/driver/invoices/{invoice}`

**Response:** تفاصيل الفاتورة الكاملة مع:
- `customer_location`: `address`, `location_lat`, `location_lng`
- جميع تفاصيل الفاتورة والزبون

**القيود:** فقط الفواتير المخصصة للسائق

---

### 1.3 بدء التوصيل

**Endpoint:** `POST /api/driver/invoices/{invoice}/start-delivery`

**القيود:**
- فقط إذا `delivery_status = assigned_to_driver`
- فقط إذا `assigned_to_driver = driver_id`

**Response:** يتم تغيير `delivery_status` إلى `in_delivery`

---

### 1.4 تأكيد التسليم

**Endpoint:** `POST /api/driver/invoices/{invoice}/mark-delivered`

**القيود:**
- فقط إذا `delivery_status = in_delivery`
- فقط إذا `assigned_to_driver = driver_id`

**Actions:**
- `delivery_status` → `delivered`
- `delivered_by` = driver_id
- `delivered_at` = now()
- إذا `payment_method = cash`: `status` → `paid`
- تحديث رصيد الزبون إذا `payment_method = credit`
- تحديث المخزون (إذا لم يكن مسبقاً)

**Response:** الفاتورة بعد التحديث

---

## 2. Returns Management (إدارة الإرجاعات)

### 2.1 قائمة الإرجاعات المسجلة من السائق

**Endpoint:** `GET /api/driver/returns`

**Query Parameters:**
- `status` (optional): `pending`, `approved`, `rejected`, `completed`
- `per_page` (optional)

**Response:** قائمة الإرجاعات المسجلة من السائق

---

### 2.2 إنشاء إرجاع جديد

**Endpoint:** `POST /api/driver/returns`

**Request Body:**
- `sale_invoice_id` (required): الفاتورة المرتبطة
- `return_type` (required): `full` أو `partial`
- `return_date` (required): تاريخ الإرجاع
- `return_reason` (optional): سبب الإرجاع
- `items` (required_if:return_type,partial, array): قائمة العناصر المسترجعة
  - `sale_invoice_item_id` (required)
  - `quantity_returned` (required): الكمية المسترجعة
  - `reason` (optional): سبب إرجاع هذا العنصر

**القيود:**
- فقط للفواتير التي سلمها السائق (`delivered_by = driver_id`)
- فقط للفواتير المكتملة (`delivery_status = delivered`)
- في حالة `partial`: يجب تحديد `items` مع الكميات

**Auto-set Fields:**
- `returned_by` = driver_id
- `created_by` = driver_id
- `created_by_type` = `driver`
- `status` = `pending` (تحتاج موافقة المدير)

**Response:** الإرجاع مع جميع التفاصيل

**ملاحظة:** الإرجاع يحتاج موافقة المدير قبل التحديث في المخزون والرصيد

---

### 2.3 عرض إرجاع محدد

**Endpoint:** `GET /api/driver/returns/{return}`

**Response:** تفاصيل الإرجاع الكاملة مع العناصر والفاتورة

---

## 3. Payments Management (إدارة الدفعات)

### 3.1 قائمة الدفعات المسجلة من السائق

**Endpoint:** `GET /api/driver/payments`

**Query Parameters:**
- `status` (optional): `pending`, `approved`, `rejected`
- `customer_id` (optional)
- `per_page` (optional)

**Response:** قائمة الدفعات المسجلة من السائق

---

### 3.2 تسجيل دفعة جديدة

**Endpoint:** `POST /api/driver/payments`

**Request Body:**
- `sale_invoice_id` (required): الفاتورة المرتبطة
- `customer_id` (required): الزبون
- `payment_date` (required): تاريخ الدفعة
- `amount` (required): المبلغ
- `payment_method` (optional): `cash`, `bank_transfer`, `cheque`, `other` (default: cash)
- `reference_number` (optional): رقم المرجع (رقم الشيك/الحوالة)
- `notes` (optional)

**القيود:**
- فقط للفواتير التي سلمها السائق (`delivered_by = driver_id`)
- فقط للفواتير المكتملة (`delivery_status = delivered`)
- يجب أن يكون `customer_id` مطابق للفاتورة

**Auto-set Fields:**
- `driver_id` = driver_id
- `status` = `pending` (تحتاج موافقة المدير)

**Response:** الدفعة مع جميع التفاصيل

**ملاحظة:** الدفعة تحتاج موافقة المدير قبل تحديث رصيد الزبون والفواتير

---

### 3.3 عرض دفعة محددة

**Endpoint:** `GET /api/driver/payments/{payment}`

**Response:** تفاصيل الدفعة الكاملة مع الزبون والفاتورة

---

## 4. Customer Balance (كشف حساب الزبون)

### 4.1 كشف حساب زبون

**Endpoint:** `GET /api/driver/customers/{customer}/balance`

**Response Fields:**
- `customer`: بيانات الزبون
- `balance`: رصيد الزبون الحالي
- `invoices`: جميع فواتير الزبون التي سلمها السائق
- `recent_transactions`: آخر 20 معاملة

**ملاحظة:** هذا مفيد للسائق لمعرفة ديون الزبون وفواتيره قبل قبول دفعة

---

## 5. سير العمل (Workflow)

### 5.1 سير عمل الفاتورة للسائق

```
1. المجهز → تعيين السائق (delivery_status = assigned_to_driver)
   ↓
2. السائق → عرض الفاتورة مع موقع الزبون
   ↓
3. السائق → بدء التوصيل (delivery_status = in_delivery)
   ↓
4. السائق → تأكيد التسليم (delivery_status = delivered)
   - إذا cash: تحديث status = paid
   - إذا credit: تحديث رصيد الزبون
```

### 5.2 سير عمل الإرجاع

```
1. السائق → إنشاء إرجاع (status = pending)
   ↓
2. المدير → الموافقة (status = approved)
   ↓
3. النظام → تحديث المخزون والرصيد تلقائياً (status = completed)
```

### 5.3 سير عمل الدفعة

```
1. السائق → تسجيل دفعة (status = pending)
   ↓
2. المدير → الموافقة (status = approved)
   ↓
3. النظام → تحديث رصيد الزبون والفواتير تلقائياً
```

---

## 6. Business Logic

### 6.1 قيود السائق

- السائق يرى فقط الفواتير المخصصة له (`assigned_to_driver = driver_id`)
- السائق يستطيع إنشاء إرجاع فقط للفواتير التي سلمها (`delivered_by = driver_id`)
- السائق يستطيع تسجيل دفعة فقط للفواتير التي سلمها (`delivered_by = driver_id`)
- الإرجاع والدفعة يحتاجان موافقة المدير
- يمكن الإرجاع والدفع فقط بعد التسليم (`delivery_status = delivered`)

### 6.2 حالات الفاتورة

**حالة التوصيل (`delivery_status`):**
- `assigned_to_driver`: معينة للسائق (جاهزة للتوصيل)
- `in_delivery`: في التوصيل (السائق بدأ التوصيل)
- `delivered`: تم التسليم (نهاية العملية)

**حالة الإرجاع (`status`):**
- `pending`: في انتظار موافقة المدير
- `approved`: معتمدة (سيتم تحديث المخزون والرصيد)
- `rejected`: مرفوضة
- `completed`: مكتملة (تم التحديث)

**حالة الدفعة (`status`):**
- `pending`: في انتظار موافقة المدير
- `approved`: معتمدة (سيتم تحديث الرصيد)
- `rejected`: مرفوضة

---

## 7. Error Handling

نفس صيغة الـ errors في API المندوب:

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
- `404`: Not Found (الفاتورة غير موجودة أو غير مخصصة لك)
- `422`: Validation Error
- `500`: Server Error

---

## 8. ملاحظات مهمة

1. السائق يرى فقط الفواتير المخصصة له
2. يمكن البدء بالتوصيل فقط إذا `delivery_status = assigned_to_driver`
3. يمكن تأكيد التسليم فقط إذا `delivery_status = in_delivery`
4. الإرجاع والدفع ممكنان فقط بعد التسليم
5. جميع الإرجاعات والدفعات تحتاج موافقة المدير
6. العنوان والموقع يأتيان من جدول customers تلقائياً

---

## 9. Authentication Flow

نفس Authentication Flow للمندوب ولكن بـ Picker login:

**Login Endpoint:** `POST /api/picker-auth/login`
- Body: `phone_number`, `password`
- Response: `token`, `user`, `token_type`

**استخدام Token:**
- Header: `Authorization: Bearer {token}`

---

## 10. Response Format

نفس صيغة الـ responses في API المندوب:

```json
{
  "status": "success",
  "data": {...},
  "message": "Optional message"
}
```

