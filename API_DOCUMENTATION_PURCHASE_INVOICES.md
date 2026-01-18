# API Documentation - Purchase Invoices & Purchase Returns

## نظرة عامة

هذا الـ API مخصص لإدارة فواتير الشراء ومرتجعات الشراء مع نظام شامل للمخزون وتوزيع تكاليف النقل:
- إدارة فواتير الشراء (Purchase Invoices)
- إدارة مرتجعات الشراء (Purchase Returns)
- توزيع كراء السائق والعمال على المواد
- حساب سعر التكلفة بعد الشراء
- ربط الفواتير بالمنتجات والمخزون
- تتبع آخر شراء لكل مادة
- إدارة حركات المخزون تلقائياً

## Base URL

```
https://maktabalwaleed.com/api
```

## Authentication

جميع الـ endpoints تحتاج إلى:
- Token صالح (Manager authentication)
- Header: `Authorization: Bearer {token}`

---

# الجزء الأول: فواتير الشراء (Purchase Invoices)

## 1. Purchase Invoices Management

### 1.1 قائمة فواتير الشراء

**GET** `/api/purchase-invoices`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `supplier_id` (integer, optional) - فلترة حسب المورد
- `status` (string, optional) - فلترة حسب الحالة: `draft`, `pending`, `paid`, `partial`, `returned`, `cancelled`
- `from_date` (date, optional) - تاريخ البداية (Format: Y-m-d)
- `to_date` (date, optional) - تاريخ النهاية (Format: Y-m-d)
- `search` (string, optional) - بحث في رقم الفاتورة أو اسم المورد

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
        "contact_person": "أحمد محمد",
        "phone_number": "07501234567"
      },
      "invoice_number": "PUR-2026-0001",
      "invoice_date": "2026-01-15",
      "due_date": "2026-02-15",
      "subtotal": 10000.00,
      "tax_amount": 500.00,
      "discount_amount": 200.00,
      "total_amount": 10300.00,
      "paid_amount": 5000.00,
      "remaining_amount": 5300.00,
      "driver_cost": 200.00,
      "worker_cost": 300.00,
      "total_transport_cost": 500.00,
      "status": "partial",
      "notes": "ملاحظات على الفاتورة",
      "items": [
        {
          "item_id": 1,
          "invoice_id": 1,
          "product_id": 1,
          "product": {
            "product_id": 1,
            "product_name": "جهاز كمبيوتر محمول",
            "sku": "LAPTOP-001"
          },
          "product_name": "جهاز كمبيوتر محمول",
          "product_code": "LAPTOP-001",
          "category_name": "أجهزة إلكترونية",
          "quantity": 5.00,
          "unit_price": 2000.00,
          "cost_after_purchase": 2062.50,
          "transport_cost_share": 312.50,
          "retail_price": 2500.00,
          "wholesale_price": 2200.00,
          "discount_percentage": 0.00,
          "tax_percentage": 5.00,
          "total_price": 10500.00,
          "notes": null
        }
      ],
      "created_by": 1,
      "creator": {
        "manager_id": 1,
        "full_name": "المدير الرئيسي"
      },
      "created_at": "2026-01-15T10:00:00.000000Z",
      "updated_at": "2026-01-15T10:00:00.000000Z"
    }
  ]
}
```

---

### 1.2 إضافة فاتورة شراء جديدة

**POST** `/api/purchase-invoices`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "supplier_id": 1,
  "invoice_number": "PUR-2026-0001",
  "invoice_date": "2026-01-15",
  "due_date": "2026-02-15",
  "subtotal": 10000.00,
  "tax_amount": 500.00,
  "discount_amount": 200.00,
  "total_amount": 10300.00,
  "driver_cost": 200.00,
  "worker_cost": 300.00,
  "notes": "ملاحظات على الفاتورة",
  "items": [
    {
      "product_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "product_code": "LAPTOP-001",
      "quantity": 5,
      "unit_price": 2000.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    },
    {
      "product_id": 2,
      "product_name": "طابعة",
      "product_code": "PRINT-001",
      "quantity": 3,
      "unit_price": 1000.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    }
  ]
}
```

**Validation Rules:**
- `supplier_id`: مطلوب، يجب أن يكون موجود في جدول `suppliers`
- `invoice_number`: مطلوب، نص، يجب أن يكون فريداً
- `invoice_date`: مطلوب، تاريخ
- `due_date`: اختياري، تاريخ، يجب أن يكون بعد أو يساوي `invoice_date`
- `subtotal`: مطلوب، رقم ≥ 0
- `tax_amount`: اختياري، رقم ≥ 0
- `discount_amount`: اختياري، رقم ≥ 0
- `total_amount`: مطلوب، رقم ≥ 0
- `driver_cost`: اختياري، رقم ≥ 0
- `worker_cost`: اختياري، رقم ≥ 0
- `notes`: اختياري، نص
- `items`: مطلوب، مصفوفة، يجب أن تحتوي على عنصر واحد على الأقل
- `items.*.product_id`: اختياري، يجب أن يكون موجود في جدول `products`
- `items.*.product_name`: مطلوب، نص، أقصى 255 حرف
- `items.*.product_code`: اختياري، نص، أقصى 255 حرف
- `items.*.quantity`: مطلوب، رقم ≥ 0.01
- `items.*.unit_price`: مطلوب، رقم ≥ 0
- `items.*.discount_percentage`: اختياري، رقم بين 0-100
- `items.*.tax_percentage`: اختياري، رقم بين 0-100
- `items.*.notes`: اختياري، نص

**ملاحظات مهمة:**
- عند إنشاء الفاتورة، يتم تعيين الحالة تلقائياً إلى `draft`
- `paid_amount` يُعيّن تلقائياً إلى `0`
- `remaining_amount` يُحسب تلقائياً: `total_amount - paid_amount`
- **توزيع الكراوي**: يتم حساب `cost_after_purchase` و `transport_cost_share` تلقائياً لكل مادة

**كيفية حساب التوزيع:**
```
إجمالي الكراتين = مجموع quantity لكل المواد
مجموع الكراوي = driver_cost + worker_cost
نصيب كل كرتون = مجموع الكراوي / إجمالي الكراتين

لكل مادة:
  transport_cost_share = quantity × نصيب كل كرتون
  cost_after_purchase = unit_price + نصيب كل كرتون
```

**مثال على التوزيع:**
```
البيانات:
  - المادة 1: 5 كراتين × 2000 = 10000
  - المادة 2: 3 كراتين × 1000 = 3000
  - كراء السائق: 200
  - كراء العمال: 300

الحساب:
  إجمالي الكراتين = 5 + 3 = 8
  مجموع الكراوي = 200 + 300 = 500
  نصيب كل كرتون = 500 / 8 = 62.5

  المادة 1:
    transport_cost_share = 5 × 62.5 = 312.5
    cost_after_purchase = 2000 + 62.5 = 2062.5

  المادة 2:
    transport_cost_share = 3 × 62.5 = 187.5
    cost_after_purchase = 1000 + 62.5 = 1062.5
```

**إذا تم إرسال `product_id`:**
- يتم جلب `retail_price` و `wholesale_price` تلقائياً من المنتج
- يتم جلب `category_name` من فئة المنتج

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice created successfully",
  "data": {
    "invoice_id": 1,
    "supplier_id": 1,
    "supplier": {
      "supplier_id": 1,
      "company_name": "شركة المورد الأول"
    },
    "invoice_number": "PUR-2026-0001",
    "invoice_date": "2026-01-15",
    "due_date": "2026-02-15",
    "subtotal": 10000.00,
    "tax_amount": 500.00,
    "discount_amount": 200.00,
    "total_amount": 10300.00,
    "paid_amount": 0.00,
    "remaining_amount": 10300.00,
    "driver_cost": 200.00,
    "worker_cost": 300.00,
    "total_transport_cost": 500.00,
    "status": "draft",
    "notes": "ملاحظات على الفاتورة",
    "items": [
      {
        "item_id": 1,
        "invoice_id": 1,
        "product_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "product_code": "LAPTOP-001",
        "category_name": "أجهزة إلكترونية",
        "quantity": 5.00,
        "unit_price": 2000.00,
        "cost_after_purchase": 2062.50,
        "transport_cost_share": 312.50,
        "retail_price": 2500.00,
        "wholesale_price": 2200.00,
        "discount_percentage": 0.00,
        "tax_percentage": 5.00,
        "total_price": 10500.00,
        "notes": null
      }
    ],
    "created_by": 1,
    "created_at": "2026-01-15T10:00:00.000000Z",
    "updated_at": "2026-01-15T10:00:00.000000Z"
  }
}
```

---

### 1.3 عرض فاتورة شراء محددة

**GET** `/api/purchase-invoices/{invoice_id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (Success - 200):**
نفس الـ response في القسم 1.1، لكن لعنصر واحد فقط.

**Response (Error - 404):**
```json
{
  "status": "error",
  "message": "Purchase invoice not found"
}
```

---

### 1.4 تحديث فاتورة شراء

**PUT/PATCH** `/api/purchase-invoices/{invoice_id}`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**ملاحظات مهمة:**
- يمكن تحديث الفواتير في حالة `draft` فقط
- جميع الحقول اختيارية (يمكن تحديث بعض الحقول فقط)
- عند تحديث `driver_cost` أو `worker_cost` أو `items`، يتم إعادة حساب التوزيع تلقائياً
- عند تحديث `total_amount`، يتم إعادة حساب `remaining_amount` تلقائياً

**Request Body (جميع الحقول اختيارية):**
```json
{
  "supplier_id": 1,
  "invoice_number": "PUR-2026-0001",
  "invoice_date": "2026-01-15",
  "due_date": "2026-02-15",
  "subtotal": 11000.00,
  "tax_amount": 550.00,
  "discount_amount": 220.00,
  "total_amount": 11330.00,
  "driver_cost": 250.00,
  "worker_cost": 350.00,
  "notes": "ملاحظات محدثة",
  "items": [
    {
      "product_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "product_code": "LAPTOP-001",
      "quantity": 6,
      "unit_price": 2100.00,
      "discount_percentage": 0,
      "tax_percentage": 5,
      "notes": null
    }
  ]
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase invoice updated successfully",
  "data": {
    "invoice_id": 1,
    ...
  }
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Only draft invoices can be updated"
}
```

---

### 1.5 حذف فاتورة شراء

**DELETE** `/api/purchase-invoices/{invoice_id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**ملاحظات:**
- يمكن حذف الفواتير في حالة `draft` فقط
- عند الحذف، يتم حذف جميع العناصر المرتبطة تلقائياً

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase invoice deleted successfully",
  "data": null
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Only draft invoices can be deleted"
}
```

---

### 1.6 نسخ فاتورة شراء

**POST** `/api/purchase-invoices/{invoice_id}/duplicate`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**الوصف:**
- يتم نسخ الفاتورة الحالية مع جميع العناصر
- رقم الفاتورة الجديد: يتم إنشاؤه تلقائياً (Format: `PUR-YYYY-NNNN`)
- تاريخ الفاتورة: التاريخ الحالي
- الحالة: `draft`
- `paid_amount`: 0
- `remaining_amount`: يساوي `total_amount`

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase invoice duplicated successfully",
  "data": {
    "invoice_id": 2,
    "invoice_number": "PUR-2026-0002",
    "invoice_date": "2026-01-16",
    "status": "draft",
    "paid_amount": 0.00,
    "remaining_amount": 10300.00,
    ...
  }
}
```

---

### 1.7 تأكيد فاتورة شراء (Post)

**POST** `/api/purchase-invoices/{invoice_id}/post`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**الوصف:**
- تغيير حالة الفاتورة من `draft` إلى `pending`
- بعد التأكيد، لا يمكن تعديل أو حذف الفاتورة

**ما يحدث عند التأكيد:**

1. **تحديث المنتجات والمخزون:**
   - البحث عن المنتج بواسطة `product_id` أو `product_code` أو `product_name`
   - إذا تم العثور على المنتج:
     - زيادة `current_stock` بالمقدار `quantity`
     - تحديث `purchase_price` إلى `unit_price`
     - تحديث `last_purchase_date` إلى `invoice_date`

2. **إنشاء حركات المخزون (Inventory Movements):**
   - يتم إنشاء `InventoryMovement` لكل مادة
   - `movement_type`: `purchase`
   - `reference_type`: `purchase_invoice`
   - `reference_id`: `invoice_id`
   - `quantity`: الكمية الإيجابية
   - `stock_before`: المخزون قبل الإضافة
   - `stock_after`: المخزون بعد الإضافة

3. **إعادة حساب التكاليف:**
   - يتم إعادة حساب `cost_after_purchase` و `transport_cost_share` بناءً على أحدث قيمة للكراوي

4. **ربط البيانات:**
   - يتم ربط `PurchaseInvoiceItem` بـ `Product` و `InventoryMovement`

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase invoice posted successfully",
  "data": {
    "invoice_id": 1,
    "status": "pending",
    "items": [
      {
        "item_id": 1,
        "product_id": 1,
        "cost_after_purchase": 2062.50,
        "transport_cost_share": 312.50,
        "inventory_movement_id": 1
      }
    ]
  }
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Only draft invoices can be posted"
}
```

---

### 1.8 قائمة مدفوعات فاتورة شراء

**GET** `/api/purchase-invoices/{invoice_id}/payments`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**الوصف:**
- يعرض جميع المدفوعات المرتبطة بفاتورة الشراء
- يتم تحديث `paid_amount` و `remaining_amount` و `status` تلقائياً عند إضافة مدفوعات

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "payment_id": 1,
      "invoice_id": 1,
      "amount": 5000.00,
      "payment_date": "2026-01-20",
      "payment_method": "cash",
      "notes": "دفعة أولى"
    }
  ]
}
```

---

## 2. حالات فواتير الشراء (Invoice Status)

### 2.1 الحالات المتاحة

| الحالة | الوصف | الإجراءات المسموحة |
|--------|-------|-------------------|
| `draft` | مسودة | التعديل، الحذف، التأكيد (Post) |
| `pending` | معلقة - تم التأكيد | عرض فقط، إضافة مدفوعات |
| `paid` | مدفوعة بالكامل | عرض فقط |
| `partial` | مدفوعة جزئياً | عرض فقط، إضافة مدفوعات |
| `returned` | مرتجعة | عرض فقط |
| `cancelled` | ملغاة | عرض فقط |

### 2.2 التحديث التلقائي للحالة

يتم تحديث حالة الفاتورة تلقائياً بناءً على المدفوعات:
- إذا `remaining_amount <= 0` و `paid_amount > 0`: `status = 'paid'`
- إذا `paid_amount > 0` و `paid_amount < total_amount`: `status = 'partial'`
- إذا `status = 'draft'`: تبقى `draft` حتى التأكيد

---

# الجزء الثاني: مرتجعات الشراء (Purchase Returns)

## 3. Purchase Returns Management

### 3.1 قائمة مرتجعات الشراء

**GET** `/api/purchase-returns`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `supplier_id` (integer, optional) - فلترة حسب المورد

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "return_invoice_id": 1,
      "original_invoice_id": 1,
      "original_invoice": {
        "invoice_id": 1,
        "invoice_number": "PUR-2026-0001",
        "invoice_date": "2026-01-15"
      },
      "supplier_id": 1,
      "supplier": {
        "supplier_id": 1,
        "company_name": "شركة المورد الأول"
      },
      "return_invoice_number": "RET-2026-0001",
      "return_date": "2026-01-20",
      "total_amount": 2000.00,
      "reason": "تلف في المنتج",
      "status": "completed",
      "notes": "تم إرجاع المواد التالفة",
      "items": [
        {
          "return_item_id": 1,
          "return_invoice_id": 1,
          "original_item_id": 1,
          "product_name": "جهاز كمبيوتر محمول",
          "product_code": "LAPTOP-001",
          "quantity": 1.00,
          "unit_price": 2000.00,
          "total_price": 2000.00,
          "reason": "تلف في الشاشة"
        }
      ],
      "created_by": 1,
      "creator": {
        "manager_id": 1,
        "full_name": "المدير الرئيسي"
      },
      "created_at": "2026-01-20T10:00:00.000000Z",
      "updated_at": "2026-01-20T10:00:00.000000Z"
    }
  ]
}
```

---

### 3.2 إضافة مرتجع شراء جديد

**POST** `/api/purchase-returns`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "original_invoice_id": 1,
  "supplier_id": 1,
  "return_invoice_number": "RET-2026-0001",
  "return_date": "2026-01-20",
  "total_amount": 2000.00,
  "reason": "تلف في المنتج",
  "notes": "تم إرجاع المواد التالفة",
  "items": [
    {
      "original_item_id": 1,
      "product_name": "جهاز كمبيوتر محمول",
      "product_code": "LAPTOP-001",
      "quantity": 1,
      "unit_price": 2000.00,
      "reason": "تلف في الشاشة"
    },
    {
      "original_item_id": 2,
      "product_name": "طابعة",
      "product_code": "PRINT-001",
      "quantity": 1,
      "unit_price": 1000.00,
      "reason": "عطل في الطباعة"
    }
  ]
}
```

**Validation Rules:**
- `original_invoice_id`: اختياري، يجب أن يكون موجود في جدول `purchase_invoices`
- `supplier_id`: مطلوب، يجب أن يكون موجود في جدول `suppliers`
- `return_invoice_number`: مطلوب، نص، يجب أن يكون فريداً
- `return_date`: مطلوب، تاريخ
- `total_amount`: مطلوب، رقم ≥ 0
- `reason`: اختياري، نص
- `notes`: اختياري، نص
- `items`: مطلوب، مصفوفة، يجب أن تحتوي على عنصر واحد على الأقل
- `items.*.original_item_id`: اختياري، يجب أن يكون موجود في جدول `purchase_invoice_items`
- `items.*.product_name`: مطلوب، نص، أقصى 255 حرف
- `items.*.product_code`: اختياري، نص، أقصى 255 حرف
- `items.*.quantity`: مطلوب، رقم ≥ 0.01
- `items.*.unit_price`: مطلوب، رقم ≥ 0
- `items.*.reason`: اختياري، نص

**ملاحظات مهمة:**
- عند إنشاء المرتجع، يتم تعيين الحالة تلقائياً إلى `draft`
- `original_invoice_id`: اختياري - يمكن إنشاء مرتجع بدون ربطه بفاتورة شراء محددة
- `original_item_id`: اختياري - يمكن إنشاء مرتجع لعنصر بدون ربطه بعنصر فاتورة محددة
- `total_price` لكل عنصر يُحسب تلقائياً: `quantity × unit_price`

**Response (Success - 201):**
```json
{
  "status": "success",
  "message": "Purchase return created successfully",
  "data": {
    "return_invoice_id": 1,
    "original_invoice_id": 1,
    "original_invoice": {
      "invoice_id": 1,
      "invoice_number": "PUR-2026-0001"
    },
    "supplier_id": 1,
    "supplier": {
      "supplier_id": 1,
      "company_name": "شركة المورد الأول"
    },
    "return_invoice_number": "RET-2026-0001",
    "return_date": "2026-01-20",
    "total_amount": 2000.00,
    "reason": "تلف في المنتج",
    "status": "draft",
    "notes": "تم إرجاع المواد التالفة",
    "items": [
      {
        "return_item_id": 1,
        "return_invoice_id": 1,
        "original_item_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "product_code": "LAPTOP-001",
        "quantity": 1.00,
        "unit_price": 2000.00,
        "total_price": 2000.00,
        "reason": "تلف في الشاشة"
      }
    ],
    "created_by": 1,
    "created_at": "2026-01-20T10:00:00.000000Z",
    "updated_at": "2026-01-20T10:00:00.000000Z"
  }
}
```

---

### 3.3 عرض مرتجع شراء محدد

**GET** `/api/purchase-returns/{return_invoice_id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (Success - 200):**
نفس الـ response في القسم 3.1، لكن لعنصر واحد فقط.

**Response (Error - 404):**
```json
{
  "status": "error",
  "message": "Purchase return not found"
}
```

---

### 3.4 حذف مرتجع شراء

**DELETE** `/api/purchase-returns/{return_invoice_id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**ملاحظات:**
- يمكن حذف المرتجعات في حالة `draft` فقط
- عند الحذف، يتم حذف جميع العناصر المرتبطة تلقائياً

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase return deleted successfully",
  "data": null
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Only draft returns can be deleted"
}
```

---

### 3.5 تأكيد مرتجع شراء (Post)

**POST** `/api/purchase-returns/{return_invoice_id}/post`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**الوصف:**
- تغيير حالة المرتجع من `draft` إلى `completed`
- بعد التأكيد، لا يمكن حذف المرتجع

**ما يحدث عند التأكيد:**

1. **البحث عن المنتجات:**
   - البحث يتم بالترتيب التالي:
     1. بواسطة `product_code` (SKU) إذا كان موجود
     2. بواسطة `product_name` و `supplier_id` إذا لم يوجد `product_code`
     3. من `original_item_id` إذا كان موجود ومرتبط بفاتورة شراء

2. **تحديث المنتجات والمخزون:**
   - إذا تم العثور على المنتج:
     - تقليل `current_stock` بالمقدار `quantity` (ناقص لأنها مرتجع)
     - استخدام method `updateStock(-quantity, 'return')`

3. **إنشاء حركات المخزون (Inventory Movements):**
   - يتم إنشاء `InventoryMovement` لكل مادة
   - `movement_type`: `return`
   - `reference_type`: `purchase_return`
   - `reference_id`: `return_invoice_id`
   - `quantity`: الكمية **السالبة** (لأنها خروج من المخزون)
   - `stock_before`: المخزون قبل الإرجاع
   - `stock_after`: المخزون بعد الإرجاع
   - `notes`: يتضمن رقم فاتورة المرتجع وسبب الإرجاع

4. **ربط البيانات:**
   - يتم ربط `PurchaseReturnItem` بـ `Product` و `InventoryMovement`
   - يتم حفظ `product_id` و `inventory_movement_id` في العنصر

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Purchase return posted successfully",
  "data": {
    "return_invoice_id": 1,
    "status": "completed",
    "items": [
      {
        "return_item_id": 1,
        "product_id": 1,
        "inventory_movement_id": 10
      }
    ]
  }
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Only draft returns can be posted"
}
```

---

## 4. حالات مرتجعات الشراء (Return Status)

### 4.1 الحالات المتاحة

| الحالة | الوصف | الإجراءات المسموحة |
|--------|-------|-------------------|
| `draft` | مسودة | الحذف، التأكيد (Post) |
| `completed` | مكتمل - تم التأكيد | عرض فقط |

### 4.2 ملاحظات

- **لا يوجد `update` method** لمرتجعات الشراء - بمجرد الإنشاء يمكن التأكيد أو الحذف فقط
- المرتجعات في حالة `completed` لا يمكن تعديلها أو حذفها
- المرتجعات تؤثر على المخزون فقط عند التأكيد (`post`)

---

# الجزء الثالث: التكامل والنظام الكامل

## 5. توزيع تكاليف النقل (Transport Cost Distribution)

### 5.1 كيفية التوزيع في فواتير الشراء

عند إضافة أو تحديث فاتورة شراء، يتم توزيع كراء السائق والعمال على المواد كالتالي:

**الخطوات:**
1. حساب عدد الكراتين الإجمالي: `total_cartons = sum(quantity لكل مادة)`
2. حساب نصيب كل كرتون: `cost_per_carton = (driver_cost + worker_cost) / total_cartons`
3. لكل مادة في الفاتورة:
   - `transport_cost_share = quantity × cost_per_carton`
   - `cost_after_purchase = unit_price + cost_per_carton`

**مثال عملي:**
```
البيانات:
  - المادة 1: 5 كراتين × 2000 = 10000
  - المادة 2: 3 كراتين × 1000 = 3000
  - كراء السائق: 200
  - كراء العمال: 300

الحساب:
  إجمالي الكراتين = 5 + 3 = 8
  مجموع الكراوي = 200 + 300 = 500
  نصيب كل كرتون = 500 / 8 = 62.5

  المادة 1:
    transport_cost_share = 5 × 62.5 = 312.5
    cost_after_purchase = 2000 + 62.5 = 2062.5

  المادة 2:
    transport_cost_share = 3 × 62.5 = 187.5
    cost_after_purchase = 1000 + 62.5 = 1062.5
```

---

## 6. تتبع آخر شراء

### 6.1 معلومات آخر شراء في فاتورة الشراء

كل مادة في فاتورة الشراء تحتوي على:
- `cost_after_purchase`: سعر التكلفة بعد إضافة نصيب الكراوي
- `transport_cost_share`: نصيب المادة من الكراوي
- `retail_price`: سعر البيع المفرد وقت الشراء (يُحفظ من المنتج)
- `wholesale_price`: سعر البيع الجملة وقت الشراء (يُحفظ من المنتج)
- `category_name`: اسم الفئة وقت الشراء (يُحفظ من المنتج)

### 6.2 تحديث المنتج عند تأكيد الفاتورة

عند تأكيد فاتورة الشراء (`post`):
- يتم تحديث `last_purchase_date` للمنتج إلى `invoice_date`
- يتم تحديث `purchase_price` للمنتج إلى `unit_price`
- يتم تحديث `current_stock` للمنتج بزيادة `quantity`

---

## 7. إدارة حركات المخزون

### 7.1 حركات المخزون من فواتير الشراء

عند تأكيد فاتورة شراء:
- يتم إنشاء `InventoryMovement` لكل مادة
- `movement_type`: `purchase`
- `quantity`: قيمة إيجابية (زيادة في المخزون)
- `reference_type`: `purchase_invoice`
- `reference_id`: `invoice_id`

### 7.2 حركات المخزون من مرتجعات الشراء

عند تأكيد مرتجع شراء:
- يتم إنشاء `InventoryMovement` لكل مادة
- `movement_type`: `return`
- `quantity`: قيمة **سالبة** (نقص في المخزون)
- `reference_type`: `purchase_return`
- `reference_id`: `return_invoice_id`

---

## 8. Flutter/Dart Examples

### 8.1 إنشاء فاتورة شراء

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> createPurchaseInvoice({
  required String token,
  required int supplierId,
  required String invoiceNumber,
  required String invoiceDate,
  required double subtotal,
  required double totalAmount,
  double? driverCost,
  double? workerCost,
  required List<Map<String, dynamic>> items,
}) async {
  final url = Uri.parse('https://maktabalwaleed.com/api/purchase-invoices');
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'supplier_id': supplierId,
      'invoice_number': invoiceNumber,
      'invoice_date': invoiceDate,
      'subtotal': subtotal,
      'total_amount': totalAmount,
      'driver_cost': driverCost ?? 0,
      'worker_cost': workerCost ?? 0,
      'items': items,
    }),
  );

  if (response.statusCode == 201) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to create purchase invoice: ${response.body}');
  }
}

// مثال على الاستخدام
final invoice = await createPurchaseInvoice(
  token: 'your_token',
  supplierId: 1,
  invoiceNumber: 'PUR-2026-0001',
  invoiceDate: '2026-01-15',
  subtotal: 10000.00,
  totalAmount: 10300.00,
  driverCost: 200.00,
  workerCost: 300.00,
  items: [
    {
      'product_id': 1,
      'product_name': 'جهاز كمبيوتر محمول',
      'product_code': 'LAPTOP-001',
      'quantity': 5,
      'unit_price': 2000.00,
    },
  ],
);
```

### 8.2 تأكيد فاتورة شراء

```dart
Future<Map<String, dynamic>> postPurchaseInvoice({
  required String token,
  required int invoiceId,
}) async {
  final url = Uri.parse(
    'https://maktabalwaleed.com/api/purchase-invoices/$invoiceId/post'
  );
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to post purchase invoice: ${response.body}');
  }
}
```

### 8.3 قائمة فواتير الشراء

```dart
Future<List<Map<String, dynamic>>> getPurchaseInvoices({
  required String token,
  int? supplierId,
  String? status,
  String? fromDate,
  String? toDate,
  String? search,
}) async {
  final queryParams = <String, String>{};
  if (supplierId != null) queryParams['supplier_id'] = supplierId.toString();
  if (status != null) queryParams['status'] = status;
  if (fromDate != null) queryParams['from_date'] = fromDate;
  if (toDate != null) queryParams['to_date'] = toDate;
  if (search != null) queryParams['search'] = search;

  final uri = Uri.parse('https://maktabalwaleed.com/api/purchase-invoices')
      .replace(queryParameters: queryParams);
  
  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return List<Map<String, dynamic>>.from(data['data']);
  } else {
    throw Exception('Failed to get purchase invoices: ${response.body}');
  }
}
```

### 8.4 إنشاء مرتجع شراء

```dart
Future<Map<String, dynamic>> createPurchaseReturn({
  required String token,
  int? originalInvoiceId,
  required int supplierId,
  required String returnInvoiceNumber,
  required String returnDate,
  required double totalAmount,
  String? reason,
  required List<Map<String, dynamic>> items,
}) async {
  final url = Uri.parse('https://maktabalwaleed.com/api/purchase-returns');
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'original_invoice_id': originalInvoiceId,
      'supplier_id': supplierId,
      'return_invoice_number': returnInvoiceNumber,
      'return_date': returnDate,
      'total_amount': totalAmount,
      'reason': reason,
      'items': items,
    }),
  );

  if (response.statusCode == 201) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to create purchase return: ${response.body}');
  }
}
```

### 8.5 تأكيد مرتجع شراء

```dart
Future<Map<String, dynamic>> postPurchaseReturn({
  required String token,
  required int returnInvoiceId,
}) async {
  final url = Uri.parse(
    'https://maktabalwaleed.com/api/purchase-returns/$returnInvoiceId/post'
  );
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to post purchase return: ${response.body}');
  }
}
```

---

## 9. cURL Examples

### 9.1 إنشاء فاتورة شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-invoices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "supplier_id": 1,
    "invoice_number": "PUR-2026-0001",
    "invoice_date": "2026-01-15",
    "due_date": "2026-02-15",
    "subtotal": 10000.00,
    "tax_amount": 500.00,
    "discount_amount": 200.00,
    "total_amount": 10300.00,
    "driver_cost": 200.00,
    "worker_cost": 300.00,
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "product_code": "LAPTOP-001",
        "quantity": 5,
        "unit_price": 2000.00,
        "discount_percentage": 0,
        "tax_percentage": 5
      }
    ]
  }'
```

### 9.2 تأكيد فاتورة شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-invoices/1/post \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 9.3 قائمة فواتير الشراء

```bash
curl -X GET "https://maktabalwaleed.com/api/purchase-invoices?supplier_id=1&status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 9.4 إنشاء مرتجع شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-returns \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "original_invoice_id": 1,
    "supplier_id": 1,
    "return_invoice_number": "RET-2026-0001",
    "return_date": "2026-01-20",
    "total_amount": 2000.00,
    "reason": "تلف في المنتج",
    "items": [
      {
        "original_item_id": 1,
        "product_name": "جهاز كمبيوتر محمول",
        "product_code": "LAPTOP-001",
        "quantity": 1,
        "unit_price": 2000.00,
        "reason": "تلف في الشاشة"
      }
    ]
  }'
```

### 9.5 تأكيد مرتجع شراء

```bash
curl -X POST https://maktabalwaleed.com/api/purchase-returns/1/post \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## 10. Error Responses

### 10.1 Validation Error (422)

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "supplier_id": ["The supplier id field is required."],
    "items": ["The items field is required."],
    "invoice_number": ["The invoice number has already been taken."]
  }
}
```

### 10.2 Unauthorized (401)

```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

### 10.3 Not Found (404)

```json
{
  "status": "error",
  "message": "Purchase invoice not found"
}
```

أو:

```json
{
  "status": "error",
  "message": "Purchase return not found"
}
```

### 10.4 Cannot Update/Delete/Post (422)

```json
{
  "status": "error",
  "message": "Only draft invoices can be updated"
}
```

أو:

```json
{
  "status": "error",
  "message": "Only draft invoices can be posted"
}
```

أو:

```json
{
  "status": "error",
  "message": "Only draft returns can be deleted"
}
```

---

## 11. Important Notes

### 11.1 حالات فواتير الشراء

| الحالة | الوصف | الإجراءات المسموحة |
|--------|-------|-------------------|
| `draft` | مسودة | التعديل، الحذف، التأكيد (Post) |
| `pending` | معلقة - تم التأكيد | عرض فقط، إضافة مدفوعات |
| `paid` | مدفوعة بالكامل | عرض فقط |
| `partial` | مدفوعة جزئياً | عرض فقط، إضافة مدفوعات |
| `returned` | مرتجعة | عرض فقط |
| `cancelled` | ملغاة | عرض فقط |

### 11.2 حالات مرتجعات الشراء

| الحالة | الوصف | الإجراءات المسموحة |
|--------|-------|-------------------|
| `draft` | مسودة | الحذف، التأكيد (Post) |
| `completed` | مكتمل - تم التأكيد | عرض فقط |

### 11.3 توزيع الكراوي في فواتير الشراء

- يتم التوزيع تلقائياً عند إنشاء أو تحديث الفاتورة
- التوزيع يتم بالتساوي على كل كرتون
- `cost_after_purchase` = سعر الشراء + نصيب الكرتون من الكراوي
- يتم إعادة الحساب عند التأكيد (Post) أيضاً

### 11.4 ربط المنتجات

**في فواتير الشراء:**
- يُفضل إرسال `product_id` عند إضافة المواد
- إذا تم إرسال `product_id`، يتم جلب الأسعار والفئة تلقائياً
- عند التأكيد (Post)، يتم البحث عن المنتج بـ `product_id` أو `product_code` أو `product_name`
- يتم تحديث المخزون تلقائياً عند التأكيد

**في مرتجعات الشراء:**
- البحث عن المنتج يتم بالترتيب:
  1. `product_code` (SKU)
  2. `product_name` + `supplier_id`
  3. من `original_item_id` إذا كان موجود
- يتم تحديث المخزون (نقص) تلقائياً عند التأكيد

### 11.5 تتبع آخر شراء

- يتم حفظ جميع تفاصيل الشراء في `purchase_invoice_items`
- يتم تحديث `last_purchase_date` في المنتج عند تأكيد الفاتورة
- يمكن تتبع جميع المشتريات من خلال `InventoryMovement`
- `cost_after_purchase` يحتوي على سعر التكلفة الكامل (شامل نصيب الكراوي)

### 11.6 إدارة المخزون

**عند تأكيد فاتورة شراء:**
- `current_stock` يزيد بـ `quantity`
- يتم إنشاء `InventoryMovement` مع `quantity` إيجابية
- `movement_type`: `purchase`

**عند تأكيد مرتجع شراء:**
- `current_stock` ينقص بـ `quantity`
- يتم إنشاء `InventoryMovement` مع `quantity` سالبة
- `movement_type`: `return`

---

## 12. Integration with Products System

### 12.1 عند تأكيد فاتورة الشراء (Post)

1. يتم البحث عن المنتج بواسطة `product_id` أو `product_code` أو `product_name`
2. يتم تحديث `current_stock` للمنتج بزيادة `quantity`
3. يتم تحديث `purchase_price` و `last_purchase_date` للمنتج
4. يتم إنشاء `InventoryMovement` لتسجيل الحركة
5. يتم ربط `PurchaseInvoiceItem` بالمنتج والحركة

### 12.2 عند تأكيد مرتجع الشراء (Post)

1. يتم البحث عن المنتج (SKU → Name → Original Item)
2. يتم تحديث `current_stock` للمنتج بنقص `quantity`
3. يتم إنشاء `InventoryMovement` مع `quantity` سالبة
4. يتم ربط `PurchaseReturnItem` بالمنتج والحركة

---

## 13. Reports & Analytics

يمكن استخدام endpoints التقارير الموجودة في:
- `/api/reports/suppliers/{supplier_id}/purchases` - تقرير مشتريات المورد
- `/api/reports/products/{product_id}/purchases` - تقرير مشتريات المنتج
- `/api/inventory-movements` - حركات المخزون
- `/api/purchase-invoices/{invoice_id}/payments` - مدفوعات فاتورة شراء

---

## 14. Support

للمساعدة أو الاستفسارات، يرجى التواصل مع فريق التطوير.

---

**آخر تحديث:** 2026-01-16
**الإصدار:** 2.0.0
