# 📚 API Documentation - Al-Waleed Inventory Management System

## نظرة عامة

هذا التوثيق يغطي جميع الـ API endpoints للنظام الجديد الذي يدعم:
- **Multi-Unit Support**: دعم وحدات متعددة للمنتجات
- **Batch Tracking**: تتبع الدفعات مع تواريخ الإنتاج والانتهاء
- **FIFO/FEFO Logic**: منطق FIFO/FEFO لإدارة المخزون
- **Supplier Financial Tracking**: نظام مالي متقدم للموردين

---

## 🔐 Authentication

جميع الـ endpoints (ما عدا Health Check) تتطلب Authentication باستخدام Laravel Sanctum.

### Headers المطلوبة:
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### الحصول على Token:
```
POST /api/manager-auth/login
```

**Request:**
```json
{
  "phone_number": "1234567890",
  "password": "password"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "manager": {
      "manager_id": 1,
      "full_name": "Admin User",
      "phone_number": "1234567890"
    },
    "token": "1|xxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

---

## 📋 Base URL

```
Production: https://api.example.com/api
Development: http://localhost:8000/api
```

---

## 📦 Products Management

### 1. Get All Products
```
GET /api/products
```

**Query Parameters:**
- `category_id` (optional): Filter by category
- `is_active` (optional): Filter by active status (true/false)
- `low_stock` (optional): Filter low stock products (true/false)
- `warehouse_id` (optional): Filter by warehouse for stock calculation
- `search` (optional): Search by name_ar, name_en, sku, or barcode
- `sort_by` (optional): Sort field (default: name_ar)
- `sort_order` (optional): asc or desc (default: asc)
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "product_id": 1,
        "name_ar": "منتج 1",
        "name_en": "Product 1",
        "sku": "PRD-001",
        "barcode": "1234567890123",
        "category_id": 1,
        "description": "Product description",
        "min_stock_alert": 10,
        "is_active": true,
        "product_units": [],
        "inventory_batches": []
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### 2. Get Single Product
```
GET /api/products/{product_id}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "product_id": 1,
    "name_ar": "منتج 1",
    "name_en": "Product 1",
    "sku": "PRD-001",
    "barcode": "1234567890123",
    "category": {
      "category_id": 1,
      "category_name": "Category 1"
    },
    "product_units": [
      {
        "id": 1,
        "unit_name": "قطعة",
        "conversion_factor": 1.000,
        "is_base_unit": true,
        "purchase_price": 10.00,
        "sale_price": 15.00
      }
    ],
    "inventory_batches": []
  }
}
```

### 3. Create Product
```
POST /api/products
```

**Request:**
```json
{
  "name_ar": "منتج جديد",
  "name_en": "New Product",
  "sku": "PRD-002",
  "barcode": "1234567890124",
  "category_id": 1,
  "description": "Product description",
  "min_stock_alert": 10,
  "is_active": true,
  "notes": "Additional notes"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Product created successfully",
  "data": {
    "product_id": 2,
    "name_ar": "منتج جديد",
    "name_en": "New Product",
    "sku": "PRD-002"
  }
}
```

### 4. Update Product
```
PUT /api/products/{product_id}
```

**Request:** (Same as Create, all fields optional)

### 5. Delete Product
```
DELETE /api/products/{product_id}
```

### 6. Get Product Stock
```
GET /api/products/{product_id}/stock?warehouse_id={warehouse_id}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "product_id": 1,
    "product_name": "منتج 1",
    "current_stock": 150.500,
    "is_low_stock": false,
    "min_stock_alert": 10,
    "batches": [
      {
        "id": 1,
        "batch_number": "BATCH-001",
        "expiry_date": "2025-12-31",
        "quantity_current": 100.000,
        "status": "active"
      }
    ]
  }
}
```

### 7. Get Product Batches
```
GET /api/products/{product_id}/batches?warehouse_id={warehouse_id}&status={status}&per_page={per_page}
```

**Query Parameters:**
- `warehouse_id` (optional): Filter by warehouse
- `status` (optional): Filter by status (active, expired, consumed)
- `per_page` (optional): Items per page

### 8. Get Low Stock Products
```
GET /api/products/low-stock?warehouse_id={warehouse_id}
```

### 9. Upload Product Image
```
POST /api/products/{product_id}/upload-image
```

**Request:** (multipart/form-data)
- `image` (file): Image file (jpeg, png, jpg, gif, webp, max: 2MB)

**Response:**
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "image_path": "products/xyz123.jpg",
    "image_url": "https://api.example.com/storage/products/xyz123.jpg"
  }
}
```

---

## 📏 Product Units Management

### 1. Get Product Units
```
GET /api/products/{product_id}/units
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "unit_name": "قطعة",
      "conversion_factor": 1.000,
      "is_base_unit": true,
      "purchase_price": 10.00,
      "sale_price": 15.00,
      "created_at": "2025-01-21T10:00:00.000000Z",
      "updated_at": "2025-01-21T10:00:00.000000Z"
    },
    {
      "id": 2,
      "product_id": 1,
      "unit_name": "كرتون",
      "conversion_factor": 24.000,
      "is_base_unit": false,
      "purchase_price": 200.00,
      "sale_price": 300.00
    }
  ]
}
```

### 2. Create Product Unit
```
POST /api/products/{product_id}/units
```

**Request:**
```json
{
  "unit_name": "كرتون",
  "conversion_factor": 24.000,
  "is_base_unit": false,
  "purchase_price": 200.00,
  "sale_price": 300.00
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Product unit created successfully",
  "data": {
    "id": 2,
    "product_id": 1,
    "unit_name": "كرتون",
    "conversion_factor": 24.000,
    "is_base_unit": false,
    "purchase_price": 200.00,
    "sale_price": 300.00
  }
}
```

### 3. Get Single Product Unit
```
GET /api/product-units/{product_unit_id}
```

### 4. Update Product Unit
```
PUT /api/product-units/{product_unit_id}
```

**Request:** (All fields optional)
```json
{
  "unit_name": "كرتون كبير",
  "conversion_factor": 48.000,
  "purchase_price": 400.00
}
```

### 5. Delete Product Unit
```
DELETE /api/product-units/{product_unit_id}
```

**Note:** Cannot delete base unit or units used in purchase invoices.

---

## 🏢 Suppliers Management

### 1. Get All Suppliers
```
GET /api/suppliers
```

**Query Parameters:**
- `is_active` (optional): Filter by active status
- `search` (optional): Search by name, contact_person, or phone

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "supplier_id": 1,
      "name": "مورد 1",
      "contact_person": "أحمد محمد",
      "phone": "1234567890",
      "email": "supplier@example.com",
      "tax_number": "123456789",
      "address": "Address here",
      "opening_balance": 0.00,
      "current_balance": 5000.00,
      "is_active": true
    }
  ]
}
```

### 2. Create Supplier
```
POST /api/suppliers
```

**Request:**
```json
{
  "name": "مورد جديد",
  "contact_person": "محمد أحمد",
  "phone": "0987654321",
  "email": "new@supplier.com",
  "tax_number": "987654321",
  "address": "New address",
  "opening_balance": 0.00,
  "notes": "Additional notes",
  "is_active": true
}
```

### 3. Get Supplier Balance
```
GET /api/suppliers/{supplier_id}/balance
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "supplier_id": 1,
    "name": "مورد 1",
    "opening_balance": 0.00,
    "current_balance": 5000.00,
    "total_purchases": 10000.00,
    "total_payments": 5000.00,
    "total_returns": 0.00
  }
}
```

### 4. Get Supplier Summary
```
GET /api/suppliers/{supplier_id}/summary
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "supplier": {
      "supplier_id": 1,
      "name": "مورد 1"
    },
    "opening_balance": 0.00,
    "current_balance": 5000.00,
    "total_invoices": 5,
    "total_purchases": 10000.00,
    "total_payments": 5000.00,
    "total_returns": 0.00,
    "pending_invoices": 2,
    "transactions": []
  }
}
```

### 5. Get Supplier Transactions
```
GET /api/suppliers/{supplier_id}/transactions
```

**Query Parameters:**
- `transaction_type` (optional): Filter by type (purchase_invoice, payment_out, purchase_return, opening_balance)
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `per_page` (optional): Items per page

---

## 🧾 Purchase Invoices

### 1. Get All Purchase Invoices
```
GET /api/purchase-invoices
```

**Query Parameters:**
- `supplier_id` (optional): Filter by supplier
- `warehouse_id` (optional): Filter by warehouse
- `payment_status` (optional): Filter by status (paid, partial, unpaid)
- `payment_method` (optional): Filter by method (cash, bank, deferred)
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `search` (optional): Search by invoice_number or supplier name
- `per_page` (optional): Items per page

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "invoice_id": 1,
        "supplier_id": 1,
        "warehouse_id": 1,
        "invoice_number": "PUR-2025-0001",
        "invoice_date": "2025-01-21",
        "due_date": "2025-02-21",
        "payment_status": "unpaid",
        "payment_method": "deferred",
        "subtotal": 1000.00,
        "tax_amount": 150.00,
        "discount_amount": 0.00,
        "total_amount": 1150.00,
        "supplier": {
          "supplier_id": 1,
          "name": "مورد 1"
        },
        "warehouse": {
          "id": 1,
          "name": "المستودع الرئيسي"
        },
        "details": []
      }
    ]
  }
}
```

### 2. Create Purchase Invoice
```
POST /api/purchase-invoices
```

**Request:**
```json
{
  "supplier_id": 1,
  "warehouse_id": 1,
  "invoice_number": "PUR-2025-0002",
  "invoice_date": "2025-01-21",
  "due_date": "2025-02-21",
  "payment_status": "unpaid",
  "payment_method": "deferred",
  "subtotal": 1000.00,
  "tax_amount": 150.00,
  "discount_amount": 0.00,
  "total_amount": 1150.00,
  "notes": "Invoice notes",
  "items": [
    {
      "product_id": 1,
      "unit_id": 1,
      "product_name": "منتج 1",
      "product_code": "PRD-001",
      "quantity": 10.000,
      "unit_price": 100.00,
      "expiry_date": "2025-12-31",
      "batch_number": "BATCH-001",
      "discount_percentage": 0,
      "tax_percentage": 15,
      "notes": "Item notes"
    }
  ]
}
```

**Important Notes:**
- `expiry_date` is **required** for all items
- `unit_id` must exist in `product_units` table
- `warehouse_id` is optional (will use default warehouse if not provided)

### 3. Get Single Purchase Invoice
```
GET /api/purchase-invoices/{invoice_id}
```

### 4. Update Purchase Invoice
```
PUT /api/purchase-invoices/{invoice_id}
```

**Note:** Can only update unpaid invoices that haven't been approved yet.

### 5. Delete Purchase Invoice
```
DELETE /api/purchase-invoices/{invoice_id}
```

**Note:** Can only delete unpaid invoices without inventory batches.

### 6. Approve Purchase Invoice
```
POST /api/purchase-invoices/{invoice_id}/approve
```

**This endpoint:**
- Creates inventory batches for each item
- Updates supplier balance
- Creates supplier transaction

**Response:**
```json
{
  "status": "success",
  "message": "Purchase invoice approved successfully",
  "data": {
    "invoice_id": 1,
    "invoice_number": "PUR-2025-0001",
    "payment_status": "unpaid",
    "details": [
      {
        "id": 1,
        "product_id": 1,
        "quantity": 10.000,
        "expiry_date": "2025-12-31",
        "inventory_batches": [
          {
            "id": 1,
            "batch_number": "BATCH-001",
            "quantity_initial": 10.000,
            "quantity_current": 10.000,
            "status": "active"
          }
        ]
      }
    ]
  }
}
```

---

## 🔄 Purchase Returns

### 1. Get All Purchase Returns
```
GET /api/purchase-returns
```

**Query Parameters:**
- `supplier_id` (optional): Filter by supplier
- `status` (optional): Filter by status
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `search` (optional): Search by return_number or supplier name
- `per_page` (optional): Items per page

### 2. Create Purchase Return
```
POST /api/purchase-returns
```

**Request:**
```json
{
  "reference_invoice_id": 1,
  "supplier_id": 1,
  "return_number": "RET-2025-0001",
  "return_date": "2025-01-21",
  "total_amount": 100.00,
  "reason": "Damaged goods",
  "notes": "Return notes",
  "items": [
    {
      "product_id": 1,
      "batch_id": 1,
      "quantity": 5.000,
      "unit_price": 20.00,
      "product_name": "منتج 1",
      "product_code": "PRD-001",
      "reason": "Damaged"
    }
  ]
}
```

**Important Notes:**
- `batch_id` is **required** for each item
- The observer will automatically:
  - Deduct quantity from the batch
  - Update supplier balance
  - Create supplier transaction

### 3. Get Single Purchase Return
```
GET /api/purchase-returns/{return_id}
```

### 4. Delete Purchase Return
```
DELETE /api/purchase-returns/{return_id}
```

**Note:** Can only delete draft returns. Deleting will reverse all transactions.

---

## 📊 Stock Adjustments

### 1. Get All Stock Adjustments
```
GET /api/stock-adjustments
```

**Query Parameters:**
- `warehouse_id` (optional): Filter by warehouse
- `product_id` (optional): Filter by product
- `type` (optional): Filter by type (addition, subtraction)
- `reason` (optional): Filter by reason (damaged, expired, inventory_count, gift)
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `per_page` (optional): Items per page

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "warehouse_id": 1,
        "product_id": 1,
        "batch_id": 1,
        "adjustment_date": "2025-01-21",
        "type": "subtraction",
        "reason": "damaged",
        "quantity": 5.000,
        "notes": "Damaged items",
        "warehouse": {
          "id": 1,
          "name": "المستودع الرئيسي"
        },
        "product": {
          "product_id": 1,
          "name_ar": "منتج 1"
        },
        "batch": {
          "id": 1,
          "batch_number": "BATCH-001"
        }
      }
    ]
  }
}
```

### 2. Create Stock Adjustment
```
POST /api/stock-adjustments
```

**Request:**
```json
{
  "warehouse_id": 1,
  "product_id": 1,
  "batch_id": 1,
  "adjustment_date": "2025-01-21",
  "type": "subtraction",
  "reason": "damaged",
  "quantity": 5.000,
  "notes": "Damaged items"
}
```

**Type Values:**
- `addition`: Add quantity to batch
- `subtraction`: Subtract quantity from batch

**Reason Values:**
- `damaged`: Damaged items
- `expired`: Expired items
- `inventory_count`: Inventory count adjustment
- `gift`: Gift items

**Note:** If `batch_id` is provided, the batch quantity will be automatically updated.

### 3. Get Single Stock Adjustment
```
GET /api/stock-adjustments/{adjustment_id}
```

### 4. Delete Stock Adjustment
```
DELETE /api/stock-adjustments/{adjustment_id}
```

**Note:** Deleting will reverse the adjustment (add back if subtraction, subtract if addition).

---

## 📦 Inventory Batches

### Get Product Batches
```
GET /api/products/{product_id}/batches
```

**Query Parameters:**
- `warehouse_id` (optional): Filter by warehouse
- `status` (optional): Filter by status (active, expired, consumed)
- `per_page` (optional): Items per page

**Response:**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "product_id": 1,
        "warehouse_id": 1,
        "batch_number": "BATCH-001",
        "production_date": null,
        "expiry_date": "2025-12-31",
        "cost_price": 10.00,
        "quantity_initial": 100.000,
        "quantity_current": 95.000,
        "status": "active",
        "warehouse": {
          "id": 1,
          "name": "المستودع الرئيسي"
        }
      }
    ]
  }
}
```

---

## 🔍 Error Responses

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "expiry_date": [
      "The expiry date field is required."
    ],
    "unit_id": [
      "The unit id field is required."
    ]
  }
}
```

### Not Found (404)
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

### Unauthorized (401)
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "status": "error",
  "message": "This action is unauthorized"
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to create purchase invoice"
}
```

---

## 📝 Important Notes

### 1. Purchase Invoice Workflow
1. **Create** invoice (status: draft)
2. **Update** invoice if needed (only if unpaid)
3. **Approve** invoice:
   - Creates inventory batches
   - Updates supplier balance
   - Creates supplier transaction

### 2. Purchase Return Workflow
1. **Create** return (observer automatically processes)
2. **Delete** return (if draft, reverses all transactions)

### 3. Stock Calculation
- Stock is calculated from `inventory_batches` table
- Use `GET /api/products/{product_id}/stock` to get current stock
- Stock is calculated per warehouse

### 4. Batch Management
- Batches are created automatically when invoice is approved
- Batches track expiry dates for FIFO/FEFO
- Expired batches are automatically marked as expired (daily job)

### 5. Supplier Balance
- Balance is calculated from `supplier_transactions` table
- Opening balance is set when creating supplier
- Current balance is updated automatically on transactions

---

## 🔄 Response Format

All responses follow this format:

### Success Response
```json
{
  "status": "success",
  "message": "Optional message",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error message",
  "errors": {
    // Validation errors (if any)
  }
}
```

---

## 📅 Version History

- **v1.0.0** (2025-01-21): Initial release with new inventory system
  - Multi-unit support
  - Batch tracking
  - Supplier financial tracking
  - Stock adjustments

---

## 🆘 Support

For API support, please contact the development team.

---

**Last Updated:** January 21, 2025

