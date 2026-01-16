# API Documentation - نظام أهداف المندوبين والراتب والرصيد المالي

## نظرة عامة

هذا الـ API مخصص لإدارة نظام شامل للمندوبين يتضمن:
- **الأهداف (Targets)**: أهداف مبيعات شهرية متنوعة (حسب الفئة، المورد، المنتج، أو مزيج)
- **الرواتب (Salaries)**: رواتب شهرية ثابتة + مكافآت من الأهداف المحققة
- **الرصيد (Balance)**: رصيد مالي للمندوب يتتبع جميع المعاملات المالية

## Base URLs

### Manager API (للمديرين والموظفين)
```
https://maktabalwaleed.com/api
```

### Representative API (للمندوبين)
```
https://maktabalwaleed.com/representative-api
```

## Authentication

جميع الـ endpoints تحتاج إلى:
- Token صالح (Manager أو Representative authentication)
- Header: `Authorization: Bearer {token}`
- Header: `Content-Type: application/json`

---

## 1. نظام الأهداف (Targets)

### 1.1 أنواع الأهداف

النظام يدعم 4 أنواع من الأهداف:

1. **حسب الفئة (category)**: هدف لبيع منتجات من فئة معينة
2. **حسب المورد (supplier)**: هدف لبيع منتجات من مورد معين
3. **حسب المنتج (product)**: هدف لبيع منتج محدد
4. **متنوع (mixed)**: هدف يجمع عدة منتجات/فئات/موردين معاً

### 1.2 قائمة الأهداف (للمدير)

**GET** `/api/representatives/{rep_id}/targets`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Query Parameters:**
- `month` (string, optional) - فلترة حسب الشهر (Format: Y-m, مثال: 2026-01)
- `type` (string, optional) - فلترة حسب النوع: `category`, `supplier`, `product`, `mixed`
- `status` (string, optional) - فلترة حسب الحالة: `active`, `completed`, `cancelled`
- `per_page` (integer, optional) - عدد النتائج في الصفحة (default: 15)

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "target_id": 1,
      "rep_id": 1,
      "target_type": "category",
      "target_month": "2026-01",
      "target_name": "هدف مبيعات فئة الإلكترونيات - يناير",
      "category": {
        "category_id": 1,
        "category_name": "إلكترونيات"
      },
      "target_quantity": 100,
      "bonus_per_unit": 5000,
      "completion_percentage_required": 100,
      "status": "active",
      "achieved_quantity": 75,
      "achievement_percentage": 75,
      "total_bonus_earned": 0,
      "items": null,
      "created_at": "2026-01-16T10:00:00.000000Z",
      "updated_at": "2026-01-16T10:00:00.000000Z"
    },
    {
      "target_id": 2,
      "rep_id": 1,
      "target_type": "mixed",
      "target_month": "2026-01",
      "target_name": "هدف مزيج - منتجات متعددة",
      "category": null,
      "supplier": null,
      "product": null,
      "target_quantity": 0,
      "bonus_per_unit": 0,
      "completion_percentage_required": 100,
      "status": "active",
      "achieved_quantity": 150,
      "achievement_percentage": 0,
      "total_bonus_earned": 750000,
      "items": [
        {
          "target_item_id": 1,
          "target_id": 2,
          "item_type": "product",
          "item_id": 5,
          "target_quantity": 50,
          "bonus_per_unit": 3000,
          "achieved_quantity": 50
        },
        {
          "target_item_id": 2,
          "target_id": 2,
          "item_type": "category",
          "item_id": 2,
          "target_quantity": 100,
          "bonus_per_unit": 5000,
          "achieved_quantity": 100
        }
      ],
      "created_at": "2026-01-16T10:30:00.000000Z",
      "updated_at": "2026-01-16T10:30:00.000000Z"
    }
  ]
}
```

---

### 1.3 إنشاء هدف جديد (للمدير)

**POST** `/api/representatives/{rep_id}/targets`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

#### مثال 1: هدف حسب الفئة (Category)

**Request Body:**
```json
{
  "target_type": "category",
  "target_month": "2026-01",
  "target_name": "هدف مبيعات فئة الإلكترونيات - يناير",
  "category_id": 1,
  "target_quantity": 100,
  "bonus_per_unit": 5000,
  "completion_percentage_required": 100
}
```

#### مثال 2: هدف حسب المورد (Supplier)

**Request Body:**
```json
{
  "target_type": "supplier",
  "target_month": "2026-01",
  "target_name": "هدف مبيعات منتجات المورد الأول",
  "supplier_id": 1,
  "target_quantity": 200,
  "bonus_per_unit": 3000,
  "completion_percentage_required": 100
}
```

#### مثال 3: هدف حسب المنتج (Product)

**Request Body:**
```json
{
  "target_type": "product",
  "target_month": "2026-01",
  "target_name": "هدف مبيعات المنتج XYZ",
  "product_id": 5,
  "target_quantity": 50,
  "bonus_per_unit": 10000,
  "completion_percentage_required": 100
}
```

#### مثال 4: هدف متنوع (Mixed)

**Request Body:**
```json
{
  "target_type": "mixed",
  "target_month": "2026-01",
  "target_name": "هدف مزيج - منتجات متعددة",
  "items": [
    {
      "item_type": "product",
      "item_id": 5,
      "target_quantity": 50,
      "bonus_per_unit": 3000
    },
    {
      "item_type": "category",
      "item_id": 2,
      "target_quantity": 100,
      "bonus_per_unit": 5000
    },
    {
      "item_type": "supplier",
      "item_id": 1,
      "target_quantity": 150,
      "bonus_per_unit": 2000
    }
  ]
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Target created successfully",
  "data": {
    "target_id": 1,
    "rep_id": 1,
    "target_type": "category",
    "target_month": "2026-01",
    "target_name": "هدف مبيعات فئة الإلكترونيات - يناير",
    "category": {
      "category_id": 1,
      "category_name": "إلكترونيات"
    },
    "target_quantity": 100,
    "bonus_per_unit": 5000,
    "completion_percentage_required": 100,
    "status": "active",
    "achieved_quantity": 0,
    "achievement_percentage": 0,
    "total_bonus_earned": 0,
    "created_at": "2026-01-16T10:00:00.000000Z",
    "updated_at": "2026-01-16T10:00:00.000000Z"
  }
}
```

**Validation Rules:**
- `target_type`: مطلوب، يجب أن يكون: `category`, `supplier`, `product`, أو `mixed`
- `target_month`: مطلوب، Format: `Y-m` (مثال: `2026-01`)
- `target_name`: مطلوب، نص (max: 255)
- `category_id`: مطلوب إذا `target_type = category`
- `supplier_id`: مطلوب إذا `target_type = supplier`
- `product_id`: مطلوب إذا `target_type = product`
- `target_quantity`: مطلوب إذا `target_type != mixed`, رقم >= 0
- `bonus_per_unit`: مطلوب إذا `target_type != mixed`, رقم >= 0
- `completion_percentage_required`: اختياري، رقم من 0-100 (default: 100)
- `items`: مطلوب إذا `target_type = mixed`, array يحتوي على:
  - `item_type`: `product`, `category`, أو `supplier`
  - `item_id`: رقم
  - `target_quantity`: رقم >= 0
  - `bonus_per_unit`: رقم >= 0

---

### 1.4 عرض تفاصيل هدف (للمدير)

**GET** `/api/representatives/{rep_id}/targets/{target_id}`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "target_id": 1,
    "rep_id": 1,
    "target_type": "category",
    "target_month": "2026-01",
    "target_name": "هدف مبيعات فئة الإلكترونيات - يناير",
    "category": {
      "category_id": 1,
      "category_name": "إلكترونيات"
    },
    "target_quantity": 100,
    "bonus_per_unit": 5000,
    "completion_percentage_required": 100,
    "status": "active",
    "achieved_quantity": 75,
    "achievement_percentage": 75,
    "total_bonus_earned": 0,
    "created_at": "2026-01-16T10:00:00.000000Z",
    "updated_at": "2026-01-16T10:00:00.000000Z"
  }
}
```

---

### 1.5 تحديث هدف (للمدير)

**PUT/PATCH** `/api/representatives/{rep_id}/targets/{target_id}`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body (جميع الحقول اختيارية):**
```json
{
  "target_name": "هدف محدث",
  "target_quantity": 150,
  "bonus_per_unit": 6000,
  "completion_percentage_required": 80,
  "status": "active"
}
```

**ملاحظة:** لا يمكن تحديث هدف مكتمل (`status = completed`)

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Target updated successfully",
  "data": {
    "target_id": 1,
    "target_name": "هدف محدث",
    "target_quantity": 150,
    "bonus_per_unit": 6000,
    "achievement_percentage": 50,
    ...
  }
}
```

---

### 1.6 حذف/إلغاء هدف (للمدير)

**DELETE** `/api/representatives/{rep_id}/targets/{target_id}`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**ملاحظة:** هذا الإجراء يغير حالة الهدف إلى `cancelled` بدلاً من الحذف الفعلي.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Target cancelled successfully",
  "data": null
}
```

---

### 1.7 حساب تقدم الهدف (للمدير)

**POST** `/api/representatives/{rep_id}/targets/{target_id}/calculate`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**الوصف:** يحسب التقدم الحالي للهدف بناءً على المبيعات المسجلة للمندوب في الشهر المحدد.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Progress calculated successfully",
  "data": {
    "target": {
      "target_id": 1,
      "achieved_quantity": 75,
      "achievement_percentage": 75,
      "total_bonus_earned": 0,
      "status": "active"
    },
    "progress": {
      "achieved_quantity": 75,
      "achievement_percentage": 75,
      "total_bonus_earned": 0
    }
  }
}
```

---

### 1.8 إكمال الهدف وإضافة المكافأة للرصيد (للمدير)

**POST** `/api/representatives/{rep_id}/targets/{target_id}/complete`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**الوصف:** 
- يتحقق من أن الهدف وصل إلى نسبة الإنجاز المطلوبة
- يغير حالة الهدف إلى `completed`
- يحسب المكافأة
- يضيف المكافأة للرصيد المالي للمندوب فوراً

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Target completed successfully and bonus added to balance",
  "data": {
    "target_id": 1,
    "status": "completed",
    "achieved_quantity": 100,
    "achievement_percentage": 100,
    "total_bonus_earned": 500000,
    ...
  }
}
```

**Response (Error - 422) - إذا لم يصل الهدف للحد المطلوب:**
```json
{
  "status": "error",
  "message": "Target has not reached the required completion percentage"
}
```

---

### 1.9 قائمة أهدافي (للمندوب)

**GET** `/representative-api/my/targets`

**Headers:**
```
Authorization: Bearer {representative_token}
```

**Query Parameters:** نفس المعاملات السابقة (`month`, `type`, `status`, `per_page`)

**Response:** نفس الـ response السابق لكن للمندوب المسجل دخوله فقط.

---

### 1.10 تفاصيل هدفي (للمندوب)

**GET** `/representative-api/my/targets/{target_id}`

**Headers:**
```
Authorization: Bearer {representative_token}
```

**Response:** نفس الـ response السابق لكن فقط إذا كان الهدف يخص المندوب المسجل دخوله.

---

## 2. نظام الرواتب (Salaries)

### 2.1 قائمة الرواتب (للمدير)

**GET** `/api/representatives/{rep_id}/salaries`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Query Parameters:**
- `month` (string, optional) - فلترة حسب الشهر
- `status` (string, optional) - فلترة حسب الحالة: `pending`, `paid`, `cancelled`
- `per_page` (integer, optional) - عدد النتائج في الصفحة

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "salary_id": 1,
      "rep_id": 1,
      "representative": {
        "rep_id": 1,
        "full_name": "أحمد المندوب"
      },
      "month": "2026-01",
      "base_salary": 1000000,
      "total_bonuses": 750000,
      "total_amount": 1750000,
      "status": "pending",
      "paid_at": null,
      "paid_by": null,
      "notes": null,
      "created_at": "2026-01-16T10:00:00.000000Z",
      "updated_at": "2026-01-16T10:00:00.000000Z"
    }
  ]
}
```

---

### 2.2 إنشاء راتب جديد (للمدير)

**POST** `/api/representatives/{rep_id}/salaries`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "month": "2026-01",
  "base_salary": 1000000,
  "total_bonuses": 750000,
  "status": "pending",
  "notes": "راتب شهر يناير"
}
```

**ملاحظات:**
- `total_bonuses`: يتم حسابها تلقائياً من الأهداف المكتملة في الشهر (يمكن تحديدها يدوياً)
- `total_amount`: يتم حسابه تلقائياً = `base_salary + total_bonuses`
- الراتب الثابت الافتراضي: 1,000,000 دينار عراقي

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Salary created successfully",
  "data": {
    "salary_id": 1,
    "rep_id": 1,
    "month": "2026-01",
    "base_salary": 1000000,
    "total_bonuses": 750000,
    "total_amount": 1750000,
    "status": "pending",
    ...
  }
}
```

---

### 2.3 حساب الراتب تلقائياً (للمدير)

**POST** `/api/representatives/{rep_id}/salaries/calculate`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Query Parameters:**
- `month` (string, optional) - الشهر المطلوب (Format: Y-m, default: الشهر الحالي)

**الوصف:** 
- يحسب الراتب الشهري تلقائياً
- الراتب الثابت: 1,000,000 دينار عراقي (افتراضي)
- المكافآت: مجموع `total_bonus_earned` من جميع الأهداف المكتملة (`status = completed`) في الشهر المحدد
- إذا كان الراتب موجود، يتم تحديثه. إذا لم يكن موجود، يتم إنشاؤه.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Salary calculated successfully",
  "data": {
    "salary_id": 1,
    "month": "2026-01",
    "base_salary": 1000000,
    "total_bonuses": 750000,
    "total_amount": 1750000,
    "status": "pending",
    ...
  }
}
```

---

### 2.4 عرض تفاصيل راتب (للمدير)

**GET** `/api/representatives/{rep_id}/salaries/{salary_id}`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Response:** نفس الـ response السابق.

---

### 2.5 تحديث راتب (للمدير)

**PUT/PATCH** `/api/representatives/{rep_id}/salaries/{salary_id}`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body (جميع الحقول اختيارية):**
```json
{
  "base_salary": 1100000,
  "total_bonuses": 800000,
  "status": "paid",
  "notes": "تم الدفع"
}
```

**ملاحظات مهمة:**
- عند تغيير `status` إلى `paid`:
  - يتم تحديث `paid_at` و `paid_by` تلقائياً
  - يتم إضافة المبلغ (`total_amount`) للرصيد المالي للمندوب فوراً
  - يتم إنشاء transaction في `representative_balance_transactions`

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Salary updated successfully",
  "data": {
    "salary_id": 1,
    "status": "paid",
    "paid_at": "2026-01-16T12:00:00.000000Z",
    "paid_by": {
      "manager_id": 1,
      "full_name": "المدير"
    },
    ...
  }
}
```

---

### 2.6 راتبي لشهر معين (للمندوب)

**GET** `/representative-api/my/salary/{month?}`

**Headers:**
```
Authorization: Bearer {representative_token}
```

**Parameters:**
- `month` (string, optional) - الشهر المطلوب (Format: Y-m, default: الشهر الحالي)

**Response:**
```json
{
  "status": "success",
  "data": {
    "salary_id": 1,
    "month": "2026-01",
    "base_salary": 1000000,
    "total_bonuses": 750000,
    "total_amount": 1750000,
    "status": "paid",
    "paid_at": "2026-01-16T12:00:00.000000Z",
    ...
  }
}
```

---

## 3. نظام الرصيد المالي (Balance)

### 3.1 رصيدي الحالي (للمندوب)

**GET** `/representative-api/my/balance`

**Headers:**
```
Authorization: Bearer {representative_token}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "balance_id": 1,
    "rep_id": 1,
    "representative": {
      "rep_id": 1,
      "full_name": "أحمد المندوب"
    },
    "current_balance": 2500000,
    "total_earned": 5000000,
    "total_withdrawn": 2500000,
    "last_transaction_at": "2026-01-16T14:00:00.000000Z",
    "created_at": "2026-01-16T10:00:00.000000Z",
    "updated_at": "2026-01-16T14:00:00.000000Z"
  }
}
```

---

### 3.2 رصيد المندوب (للمدير)

**GET** `/api/representatives/{rep_id}/balance`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Response:** نفس الـ response السابق.

---

### 3.3 سجل المعاملات المالية (للمندوب)

**GET** `/representative-api/my/balance/transactions`

**Headers:**
```
Authorization: Bearer {representative_token}
```

**Query Parameters:**
- `transaction_type` (string, optional) - فلترة حسب النوع: `salary_payment`, `bonus`, `withdrawal`, `payment`, `adjustment`
- `from_date` (date, optional) - تاريخ البداية
- `to_date` (date, optional) - تاريخ النهاية
- `per_page` (integer, optional) - عدد النتائج في الصفحة

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "transaction_id": 1,
      "rep_id": 1,
      "transaction_type": "salary_payment",
      "amount": 1750000,
      "related_type": "representative_salary",
      "related_id": 1,
      "description": "راتب شهر 2026-01",
      "balance_before": 750000,
      "balance_after": 2500000,
      "creator": {
        "manager_id": 1,
        "full_name": "المدير"
      },
      "created_at": "2026-01-16T12:00:00.000000Z"
    },
    {
      "transaction_id": 2,
      "rep_id": 1,
      "transaction_type": "bonus",
      "amount": 500000,
      "related_type": "representative_target",
      "related_id": 1,
      "description": "مكافأة هدف: هدف مبيعات فئة الإلكترونيات - شهر 2026-01",
      "balance_before": 0,
      "balance_after": 500000,
      "creator": null,
      "created_at": "2026-01-16T11:00:00.000000Z"
    },
    {
      "transaction_id": 3,
      "rep_id": 1,
      "transaction_type": "withdrawal",
      "amount": -500000,
      "related_type": null,
      "related_id": null,
      "description": "سحب من الرصيد",
      "balance_before": 500000,
      "balance_after": 0,
      "creator": {
        "manager_id": 1,
        "full_name": "المدير"
      },
      "created_at": "2026-01-16T13:00:00.000000Z"
    }
  ]
}
```

---

### 3.4 سجل المعاملات المالية (للمدير)

**GET** `/api/representatives/{rep_id}/balance/transactions`

**Headers:**
```
Authorization: Bearer {manager_token}
```

**Query Parameters:** نفس المعاملات السابقة

**Response:** نفس الـ response السابق.

---

### 3.5 سحب من الرصيد (للمدير)

**POST** `/api/representatives/{rep_id}/balance/withdraw`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": 500000,
  "description": "سحب من الرصيد"
}
```

**Validation Rules:**
- `amount`: مطلوب، رقم > 0.01
- `description`: اختياري، نص (max: 500)

**ملاحظة:** يجب أن يكون الرصيد الحالي كافي للسحب.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Withdrawal completed successfully",
  "data": {
    "transaction_id": 4,
    "transaction_type": "withdrawal",
    "amount": -500000,
    "balance_before": 2500000,
    "balance_after": 2000000,
    "description": "سحب من الرصيد",
    ...
  }
}
```

**Response (Error - 422) - إذا كان الرصيد غير كافي:**
```json
{
  "status": "error",
  "message": "Insufficient balance"
}
```

---

### 3.6 إيداع/دفع إضافي للرصيد (للمدير)

**POST** `/api/representatives/{rep_id}/balance/deposit`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": 200000,
  "description": "دفع إضافي"
}
```

**Validation Rules:**
- `amount`: مطلوب، رقم > 0.01
- `description`: اختياري، نص (max: 500)

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Deposit completed successfully",
  "data": {
    "transaction_id": 5,
    "transaction_type": "payment",
    "amount": 200000,
    "balance_before": 2000000,
    "balance_after": 2200000,
    "description": "دفع إضافي",
    ...
  }
}
```

---

### 3.7 تعديل يدوي على الرصيد (للمدير)

**POST** `/api/representatives/{rep_id}/balance/adjust`

**Headers:**
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": -100000,
  "description": "تعديل يدوي - تصحيح خطأ"
}
```

**Validation Rules:**
- `amount`: مطلوب، رقم (يمكن أن يكون موجب أو سالب)
- `description`: مطلوب، نص (max: 500)

**ملاحظة:** هذا الإجراء يستخدم للتعديلات اليدوية فقط (مثل تصحيح الأخطاء).

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Balance adjusted successfully",
  "data": {
    "transaction_id": 6,
    "transaction_type": "adjustment",
    "amount": -100000,
    "balance_before": 2200000,
    "balance_after": 2100000,
    "description": "تعديل يدوي - تصحيح خطأ",
    ...
  }
}
```

---

## 4. أنواع المعاملات المالية (Transaction Types)

| النوع | الوصف | المبلغ |
|------|-------|--------|
| `salary_payment` | دفع راتب شهري | موجب (راتب + مكافآت) |
| `bonus` | مكافأة من هدف مكتمل | موجب |
| `withdrawal` | سحب من الرصيد | سالب |
| `payment` | إيداع/دفع إضافي | موجب |
| `adjustment` | تعديل يدوي | موجب أو سالب |

---

## 5. آلية عمل النظام

### 5.1 حساب إنجاز الأهداف

النظام يحسب إنجاز الأهداف بناءً على:
1. **المبيعات المسجلة**: البحث في `product_sales` حيث `representative_id = rep_id`
2. **الشهر المحدد**: فلترة حسب `sale_date` في الشهر المطلوب
3. **نوع الهدف**:
   - **Category**: جمع `quantity` من المبيعات حيث `product.category_id = category_id`
   - **Supplier**: جمع `quantity` من المبيعات حيث `product.supplier_id = supplier_id`
   - **Product**: جمع `quantity` من المبيعات حيث `product_id = product_id`
   - **Mixed**: حساب كل item بشكل منفصل ثم جمع النتائج

### 5.2 حساب المكافآت

- المكافأة تُحسب فوراً عند إكمال الهدف (`achievement_percentage >= completion_percentage_required`)
- **لأهداف عادية**: `bonus = achieved_quantity * bonus_per_unit`
- **لأهداف متنوعة**: `bonus = sum of all items bonuses` (كل item له حساب منفصل)
- المكافأة تُضاف للرصيد فوراً عند استدعاء `/complete`

### 5.3 حساب الراتب الشهري

- **الراتب الثابت**: 1,000,000 دينار عراقي (افتراضي)
- **المكافآت**: مجموع `total_bonus_earned` من جميع الأهداف المكتملة في الشهر
- **الراتب الإجمالي**: `base_salary + total_bonuses`
- عند دفع الراتب (`status = paid`): يُضاف للرصيد تلقائياً

### 5.4 الرصيد المالي

- **الرصيد الحالي**: يُحسب ديناميكياً من جميع المعاملات
- **إجمالي المكتسب**: مجموع جميع المعاملات الموجبة
- **إجمالي المسحوب**: مجموع جميع المعاملات السالبة (القيمة المطلقة)

---

## 6. أمثلة على الاستخدام

### مثال 1: إنشاء هدف وإكماله

```bash
# 1. إنشاء هدف
POST /api/representatives/1/targets
{
  "target_type": "category",
  "target_month": "2026-01",
  "target_name": "هدف فئة الإلكترونيات",
  "category_id": 1,
  "target_quantity": 100,
  "bonus_per_unit": 5000
}

# 2. تسجيل مبيعات (يجب إضافة representative_id عند إنشاء sale)
# 3. حساب التقدم
POST /api/representatives/1/targets/1/calculate

# 4. إكمال الهدف (إذا وصل للحد المطلوب)
POST /api/representatives/1/targets/1/complete
```

### مثال 2: حساب ودفع راتب

```bash
# 1. حساب الراتب تلقائياً
POST /api/representatives/1/salaries/calculate?month=2026-01

# 2. دفع الراتب (يضيف للرصيد تلقائياً)
PUT /api/representatives/1/salaries/1
{
  "status": "paid"
}
```

### مثال 3: المندوب يطلع على رصيده وأهدافه

```bash
# 1. أهدافي
GET /representative-api/my/targets

# 2. رصيدي
GET /representative-api/my/balance

# 3. سجل المعاملات
GET /representative-api/my/balance/transactions

# 4. راتبي
GET /representative-api/my/salary/2026-01
```

---

## 7. Status Codes

- `200` - Success
- `201` - Created
- `401` - Unauthenticated
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## 8. ملاحظات مهمة

1. **المبيعات والأهداف**: عند إنشاء sale جديد، يجب تحديد `representative_id` ليتم احتسابه في الأهداف
2. **المكافآت الفورية**: المكافآت تُضاف للرصيد فوراً عند إكمال الهدف (immediate)
3. **الراتب اليدوي**: الراتب يُدفع يدوياً عبر تغيير `status` إلى `paid`
4. **الرصيد الديناميكي**: الرصيد يُحسب تلقائياً من جميع المعاملات المالية
5. **الأهداف المتنوعة**: كل item في الهدف المتنوع له حساب مكافأة منفصل

---

## 9. مثال Flutter/Dart

```dart
// جلب أهدافي
Future<List<Target>> getMyTargets() async {
  final response = await dio.get(
    '/representative-api/my/targets',
    options: Options(headers: {
      'Authorization': 'Bearer $token',
    }),
  );
  return (response.data['data'] as List)
      .map((json) => Target.fromJson(json))
      .toList();
}

// جلب رصيدي
Future<Balance> getMyBalance() async {
  final response = await dio.get(
    '/representative-api/my/balance',
    options: Options(headers: {
      'Authorization': 'Bearer $token',
    }),
  );
  return Balance.fromJson(response.data['data']);
}
```

---

**آخر تحديث:** 2026-01-16

