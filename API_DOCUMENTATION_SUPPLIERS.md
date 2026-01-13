# API Documentation - Suppliers System

## نظرة عامة

هذا الـ API مخصص لإدارة الموردين (Suppliers) مع نظام محاسبي متكامل يتضمن:
- إدارة الموردين (CRUD + رفع الصور)
- فواتير الشراء (Purchase Invoices)
- فواتير مرتجع الشراء (Purchase Return Invoices)
- سجل الدفعات (Payments)
- النظام المحاسبي (Chart of Accounts + Journal Entries)
- التقارير والتحليلات

## Base URL

```
https://maktabalwaleed.com/api
```

## Authentication

جميع الـ endpoints تحتاج إلى:
- Token صالح (Manager authentication)
- Header: `Authorization: Bearer {token}`

---

## 1. Suppliers Management

### 1.1 قائمة الموردين

**GET** `/api/suppliers`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `is_active` (boolean) - فلترة حسب الحالة
- `search` (string) - بحث في الاسم، اسم المسؤول، أو رقم الهاتف

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "supplier_id": 1,
      "company_name": "شركة المورد الأول",
      "contact_person_name": "أحمد محمد",
      "phone_number": "0501234567",
      "email": "supplier@example.com",
      "address": "العنوان الكامل",
      "profile_image": "suppliers/abc123.jpg",
      "profile_image_url": "https://maktabalwaleed.com/storage/suppliers/abc123.jpg",
      "notes": "ملاحظات",
      "is_active": true,
      "created_at": "2026-01-13T10:00:00.000000Z",
      "updated_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

---

### 1.2 إضافة مورد جديد

**POST** `/api/suppliers`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "company_name": "شركة المورد الأول",
  "contact_person_name": "أحمد محمد",
  "phone_number": "0501234567",
  "email": "supplier@example.com",
  "address": "العنوان الكامل",
  "notes": "ملاحظات",
  "is_active": true
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Supplier created successfully",
  "data": {
    "supplier_id": 1,
    "company_name": "شركة المورد الأول",
    "contact_person_name": "أحمد محمد",
    "phone_number": "0501234567",
    "email": "supplier@example.com",
    "address": "العنوان الكامل",
    "profile_image": null,
    "profile_image_url": null,
    "notes": "ملاحظات",
    "is_active": true,
    "created_at": "2026-01-13T10:00:00.000000Z",
    "updated_at": "2026-01-13T10:00:00.000000Z"
  }
}
```

---

### 1.3 عرض مورد محدد

**GET** `/api/suppliers/{supplier_id}`

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "supplier_id": 1,
    "company_name": "شركة المورد الأول",
    "contact_person_name": "أحمد محمد",
    "phone_number": "0501234567",
    "email": "supplier@example.com",
    "address": "العنوان الكامل",
    "profile_image": "suppliers/abc123.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/suppliers/abc123.jpg",
    "notes": "ملاحظات",
    "is_active": true,
    "created_at": "2026-01-13T10:00:00.000000Z",
    "updated_at": "2026-01-13T10:00:00.000000Z"
  }
}
```

---

### 1.4 تحديث مورد

**PATCH** `/api/suppliers/{supplier_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** (جميع الحقول اختيارية)
```json
{
  "company_name": "شركة المورد المحدثة",
  "contact_person_name": "محمد أحمد",
  "phone_number": "0507654321",
  "email": "newemail@example.com",
  "address": "عنوان جديد",
  "notes": "ملاحظات محدثة",
  "is_active": false
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Supplier updated successfully",
  "data": {
    "supplier_id": 1,
    "company_name": "شركة المورد المحدثة",
    ...
  }
}
```

---

### 1.5 حذف مورد

**DELETE** `/api/suppliers/{supplier_id}`

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Supplier deleted successfully",
  "data": null
}
```

---

### 1.6 رفع صورة/شعار المورد

**POST** `/api/suppliers/{supplier_id}/upload-image`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
- `profile_image` (file) - مطلوب - صورة (jpeg, png, jpg, gif, webp) - حجم أقصى: 2MB

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "profile_image": "suppliers/xyz789.jpg",
    "profile_image_url": "https://maktabalwaleed.com/storage/suppliers/xyz789.jpg"
  }
}
```

---

### 1.7 رصيد المورد

**GET** `/api/suppliers/{supplier_id}/balance`

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "supplier_id": 1,
    "company_name": "شركة المورد الأول",
    "current_balance": 15000.00,
    "total_purchases": 50000.00,
    "total_payments": 35000.00,
    "total_returns": 0.00
  }
}
```

---

### 1.8 ملخص شامل للمورد

**GET** `/api/suppliers/{supplier_id}/summary`

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "supplier": {
      "supplier_id": 1,
      "company_name": "شركة المورد الأول",
      ...
    },
    "balance": 15000.00,
    "total_invoices": 10,
    "total_purchases": 50000.00,
    "total_payments": 35000.00,
    "total_returns": 0.00,
    "pending_invoices": 3
  }
}
```

---

## 2. Purchase Invoices (فواتير الشراء)

### 2.1 قائمة فواتير الشراء

**GET** `/api/purchase-invoices`

**Query Parameters:**
- `supplier_id` - فلترة حسب المورد
- `status` - فلترة حسب الحالة (draft, pending, paid, partial, returned, cancelled)
- `from_date` - تاريخ البداية
- `to_date` - تاريخ النهاية
- `search` - بحث في رقم الفاتورة أو اسم المورد

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "invoice_id": 1,
      "supplier_id": 1,
      "supplier": {
        "supplier_id": 1,
        "company_name": "شركة المورد الأول",
        ...
      },
      "invoice_number": "PUR-2026-0001",
      "invoice_date": "2026-01-13",
      "due_date": "2026-02-13",
      "subtotal": 10000.00,
      "tax_amount": 500.00,
      "discount_amount": 200.00,
      "total_amount": 10300.00,
      "paid_amount": 5000.00,
      "remaining_amount": 5300.00,
      "status": "partial",
      "notes": "ملاحظات",
      "items": [
        {
          "item_id": 1,
          "product_name": "منتج 1",
          "product_code": "PRD001",
          "quantity": 10,
          "unit_price": 1000.00,
          "discount_percentage": 0,
          "tax_percentage": 5,
          "total_price": 10500.00,
          "notes": null
        }
      ],
      "created_at": "2026-01-13T10:00:00.000000Z",
      "updated_at": "2026-01-13T10:00:00.000000Z"
    }
  ]
}
```

---

### 2.2 إنشاء فاتورة شراء

**POST** `/api/purchase-invoices`

**Request Body:**
```json
{
  "supplier_id": 1,
  "invoice_number": "PUR-2026-0001",
  "invoice_date": "2026-01-13",
  "due_date": "2026-02-13",
  "subtotal": 10000.00,
  "tax_amount": 500.00,
  "discount_amount": 200.00,
  "total_amount": 10300.00,
  "notes": "ملاحظات",
  "items": [
    {
      "product_name": "منتج 1",
      "product_code": "PRD001",
      "quantity": 10,
      "unit_price": 1000.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    }
  ]
}
```

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice created successfully",
  "data": {
    "invoice_id": 1,
    ...
  }
}
```

---

### 2.3 عرض فاتورة مع تفاصيلها

**GET** `/api/purchase-invoices/{invoice_id}`

---

### 2.4 تحديث فاتورة (draft فقط)

**PATCH** `/api/purchase-invoices/{invoice_id}`

**ملاحظة:** فقط الفواتير بحالة `draft` يمكن تحديثها.

---

### 2.5 حذف فاتورة (draft فقط)

**DELETE** `/api/purchase-invoices/{invoice_id}`

---

### 2.6 إعادة طلب نفس الفاتورة

**POST** `/api/purchase-invoices/{invoice_id}/duplicate`

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice duplicated successfully",
  "data": {
    "invoice_id": 2,
    "invoice_number": "PUR-2026-0002",
    ...
  }
}
```

---

### 2.7 تأكيد الفاتورة (Post Invoice)

**POST** `/api/purchase-invoices/{invoice_id}/post`

**ملاحظة:** يغير حالة الفاتورة من `draft` إلى `pending` ويُنشئ قيود محاسبية تلقائياً.

---

### 2.8 فواتير مورد معين

**GET** `/api/suppliers/{supplier_id}/invoices`

---

## 3. Purchase Returns (فواتير مرتجع الشراء)

### 3.1 قائمة المرتجعات

**GET** `/api/purchase-returns`

**Query Parameters:**
- `supplier_id` - فلترة حسب المورد

---

### 3.2 إنشاء فاتورة مرتجع

**POST** `/api/purchase-returns`

**Request Body:**
```json
{
  "original_invoice_id": 1,
  "supplier_id": 1,
  "return_invoice_number": "RET-2026-0001",
  "return_date": "2026-01-15",
  "total_amount": 1000.00,
  "reason": "منتج معيب",
  "notes": "ملاحظات",
  "items": [
    {
      "original_item_id": 1,
      "product_name": "منتج 1",
      "product_code": "PRD001",
      "quantity": 1,
      "unit_price": 1000.00,
      "reason": "منتج معيب"
    }
  ]
}
```

---

## 4. Payments (الدفعات)

### 4.1 قائمة الدفعات

**GET** `/api/supplier-payments`

**Query Parameters:**
- `supplier_id` - فلترة حسب المورد
- `invoice_id` - فلترة حسب الفاتورة

---

### 4.2 تسجيل دفعة

**POST** `/api/supplier-payments`

**Request Body:**
```json
{
  "supplier_id": 1,
  "invoice_id": 1,
  "payment_number": "PAY-2026-0001",
  "payment_type": "payment",
  "amount": 5000.00,
  "payment_date": "2026-01-15",
  "payment_method": "bank_transfer",
  "bank_name": "البنك الأهلي",
  "cheque_number": null,
  "reference_number": "REF123456",
  "notes": "دفعة جزئية"
}
```

**ملاحظة:** عند ربط الدفعة بفاتورة، يتم تحديث `paid_amount` و `remaining_amount` تلقائياً.

---

### 4.3 دفعات مورد معين

**GET** `/api/suppliers/{supplier_id}/payments`

---

### 4.4 دفعات فاتورة معينة

**GET** `/api/purchase-invoices/{invoice_id}/payments`

---

## 5. Accounts (Chart of Accounts)

### 5.1 قائمة الحسابات

**GET** `/api/accounts`

**Query Parameters:**
- `account_type` - فلترة حسب النوع (asset, liability, equity, revenue, expense)
- `is_active` - فلترة حسب الحالة

---

### 5.2 إضافة حساب

**POST** `/api/accounts`

**Request Body:**
```json
{
  "account_code": "2010",
  "account_name": "حساب الموردين",
  "account_type": "liability",
  "parent_account_id": null,
  "opening_balance": 0,
  "is_active": true
}
```

---

### 5.3 حركات حساب

**GET** `/api/accounts/{account_id}/transactions`

---

### 5.4 رصيد حساب

**GET** `/api/accounts/{account_id}/balance`

---

## 6. Journal Entries (القيود المحاسبية)

### 6.1 قائمة القيود

**GET** `/api/journal-entries`

**Query Parameters:**
- `is_posted` - فلترة حسب حالة التأكيد

---

### 6.2 إنشاء قيد يدوي

**POST** `/api/journal-entries`

**Request Body:**
```json
{
  "entry_date": "2026-01-13",
  "description": "قيد محاسبي يدوي",
  "lines": [
    {
      "account_id": 1,
      "debit_amount": 1000.00,
      "credit_amount": 0,
      "description": "مدين"
    },
    {
      "account_id": 2,
      "debit_amount": 0,
      "credit_amount": 1000.00,
      "description": "دائن"
    }
  ]
}
```

**ملاحظة:** يجب أن يكون مجموع المدين = مجموع الدائن.

---

### 6.3 تأكيد القيد (Post Entry)

**POST** `/api/journal-entries/{entry_id}/post`

**ملاحظة:** عند التأكيد، يتم:
1. إنشاء حركات في `account_transactions`
2. تحديث أرصدة الحسابات
3. تغيير حالة القيد إلى `posted`

---

## 7. Reports & Analytics

### 7.1 ربح من مورد (خلال فترة)

**GET** `/api/suppliers/{supplier_id}/profit?from_date=2026-01-01&to_date=2026-01-31`

---

### 7.2 ملخص المشتريات

**GET** `/api/suppliers/{supplier_id}/purchases-summary?from_date=2026-01-01&to_date=2026-01-31`

---

### 7.3 مقارنة أسعار

**GET** `/api/suppliers/{supplier_id}/price-comparison?product_name=منتج 1`

---

### 7.4 ملخص مالي عام

**GET** `/api/reports/financial-summary?from_date=2026-01-01&to_date=2026-01-31`

---

### 7.5 تقرير الموردين

**GET** `/api/reports/suppliers-report`

---

## Status Codes

- `200` - Success
- `201` - Created Successfully
- `401` - Unauthenticated
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## ملاحظات مهمة

1. **المنتجات:** حالياً نستخدم `product_name` و `product_code` مؤقتاً. لاحقاً سنربط بجدول products
2. **الصلاحيات:** جميع endpoints تحتاج Manager authentication
3. **التوازن المحاسبي:** يجب التأكد من Debit = Credit في كل قيد
4. **الأرقام التلقائية:** يتم إنشاء أرقام تلقائية للفواتير والقيود
5. **الحسابات الافتراضية:** يجب إنشاء حسابات أساسية عند التثبيت (Seeder)

---

**تم إنشاء النظام بنجاح! 🚀**

