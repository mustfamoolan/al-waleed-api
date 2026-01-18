# API Documentation: Preparer (المجهز)

## نظرة عامة

API للمجهز (Employee) لإدارة تجهيز الفواتير وتعيين السائقين.

**Base URL:** `/api`

**Authentication:** Laravel Sanctum (Manager/Employee login)

**Middleware:** `employee.only` - فقط الموظفين (Employees) يمكنهم الوصول

---

## 1. Invoices Management (إدارة الفواتير)

### 1.1 قائمة الفواتير المعتمدة جاهزة للتجهيز

**Endpoint:** `GET /api/preparer/invoices`

**Query Parameters:**
- `delivery_status` (optional): `not_prepared`, `preparing`
- `per_page` (optional)

**Response:** قائمة الفواتير المعتمدة (`request_status = approved`) الجاهزة للتجهيز

**القيود:** فقط الفواتير المعتمدة من المدير (`request_status = approved`)

---

### 1.2 عرض فاتورة محددة

**Endpoint:** `GET /api/preparer/invoices/{invoice}`

**Response:** تفاصيل الفاتورة الكاملة مع جميع العناصر

**القيود:** فقط الفواتير المعتمدة (`request_status = approved`)

---

### 1.3 بدء التجهيز

**Endpoint:** `POST /api/preparer/invoices/{invoice}/start-preparing`

**القيود:**
- فقط إذا `request_status = approved`
- فقط إذا `delivery_status = not_prepared`

**Actions:**
- `delivery_status` → `preparing`
- `prepared_by` = employee_id (المجهز المسجل دخول)

**Response:** الفاتورة بعد التحديث

---

### 1.4 إنهاء التجهيز

**Endpoint:** `POST /api/preparer/invoices/{invoice}/complete-preparing`

**القيود:**
- فقط إذا `request_status = approved`
- فقط إذا `delivery_status = preparing`

**Actions:**
- `delivery_status` → `prepared`
- `prepared_by` = employee_id
- `prepared_at` = now()

**Response:** الفاتورة بعد التحديث (جاهزة لتعيين السائق)

---

### 1.5 تعيين السائق

**Endpoint:** `POST /api/preparer/invoices/{invoice}/assign-driver`

**Request Body:**
- `driver_id` (required): `exists:pickers,picker_id`
- `notes` (optional): ملاحظات للسائق

**القيود:**
- فقط إذا `request_status = approved`
- فقط إذا `delivery_status = prepared`

**Actions:**
- `assigned_to_driver` = driver_id
- `assigned_at` = now()
- `delivery_status` → `assigned_to_driver`
- إذا `notes`: إضافة ملاحظات للسائق

**Response:** الفاتورة بعد التعيين مع بيانات السائق

---

## 2. سير العمل (Workflow)

### 2.1 سير عمل التجهيز

```
1. المدير → الموافقة على الفاتورة (request_status = approved)
   ↓
2. المجهز → عرض الفواتير المعتمدة الجاهزة للتجهيز
   ↓
3. المجهز → بدء التجهيز (delivery_status = preparing)
   ↓
4. المجهز → إنهاء التجهيز (delivery_status = prepared)
   ↓
5. المجهز → تعيين السائق (delivery_status = assigned_to_driver)
   ↓
6. السائق → استلام الفاتورة والتوصيل
```

### 2.2 حالات التوصيل للمجهز

**الحالات المتاحة للمجهز:**
- `not_prepared`: لم تجهز (بدء التجهيز)
- `preparing`: في التجهيز (إنهاء التجهيز)
- `prepared`: تم التجهيز (تعيين السائق)
- `assigned_to_driver`: معينة للسائق (نهاية عمل المجهز)

---

## 3. Business Logic

### 3.1 قيود المجهز

- المجهز يرى فقط الفواتير المعتمدة (`request_status = approved`)
- المجهز يستطيع تجهيز الفواتير فقط (`delivery_status IN (not_prepared, preparing)`)
- يجب إنهاء التجهيز قبل تعيين السائق
- لا يمكن تعيين سائق إلا إذا `delivery_status = prepared`

### 3.2 ترتيب الخطوات

1. **بدء التجهيز**: `not_prepared` → `preparing`
2. **إنهاء التجهيز**: `preparing` → `prepared`
3. **تعيين السائق**: `prepared` → `assigned_to_driver`

لا يمكن القفز على الخطوات أو التراجع (إلا بالإلغاء من المدير)

---

## 4. Error Handling

نفس صيغة الـ errors:

```json
{
  "status": "error",
  "message": "Error message"
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthenticated
- `403`: Unauthorized (ليس موظف - employee)
- `404`: Not Found
- `422`: Validation Error (حالة غير صحيحة أو قيود)
- `500`: Server Error

---

## 5. ملاحظات مهمة

1. المجهز يرى فقط الفواتير المعتمدة من المدير
2. لا يمكن البدء بالتجهيز إلا إذا `delivery_status = not_prepared`
3. يجب إنهاء التجهيز قبل تعيين السائق
4. تعيين السائق هو الخطوة الأخيرة للمجهز
5. يمكن تعيين ملاحظات للسائق عند التعيين

---

## 6. Authentication Flow

**Login Endpoint:** `POST /api/manager-auth/login`
- Body: `phone_number`, `password`
- Response: `token`, `user`, `user_type` (manager or employee)
- يجب أن يكون `user_type = employee` أو `user` instanceof Employee

**استخدام Token:**
- Header: `Authorization: Bearer {token}`

---

## 7. Response Format

نفس صيغة الـ responses:

```json
{
  "status": "success",
  "data": {...},
  "message": "Optional message"
}
```

