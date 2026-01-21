# 🚀 API Quick Start Guide

## Authentication

### 1. Login
```bash
POST /api/manager-auth/login
Content-Type: application/json

{
  "phone_number": "1234567890",
  "password": "password"
}
```

### 2. Use Token
```bash
Authorization: Bearer {token}
```

---

## 📦 Common Workflows

### Workflow 1: Create Product with Units

```bash
# 1. Create Product
POST /api/products
{
  "name_ar": "منتج جديد",
  "sku": "PRD-001",
  "category_id": 1,
  "min_stock_alert": 10
}

# 2. Add Base Unit
POST /api/products/{product_id}/units
{
  "unit_name": "قطعة",
  "conversion_factor": 1.000,
  "is_base_unit": true,
  "purchase_price": 10.00,
  "sale_price": 15.00
}

# 3. Add Secondary Unit
POST /api/products/{product_id}/units
{
  "unit_name": "كرتون",
  "conversion_factor": 24.000,
  "is_base_unit": false,
  "purchase_price": 200.00,
  "sale_price": 300.00
}
```

### Workflow 2: Create Purchase Invoice

```bash
# 1. Create Invoice (Draft)
POST /api/purchase-invoices
{
  "supplier_id": 1,
  "warehouse_id": 1,
  "invoice_number": "PUR-2025-0001",
  "invoice_date": "2025-01-21",
  "payment_status": "unpaid",
  "payment_method": "deferred",
  "subtotal": 1000.00,
  "tax_amount": 150.00,
  "total_amount": 1150.00,
  "items": [
    {
      "product_id": 1,
      "unit_id": 1,
      "product_name": "منتج 1",
      "quantity": 10.000,
      "unit_price": 100.00,
      "expiry_date": "2025-12-31",
      "batch_number": "BATCH-001"
    }
  ]
}

# 2. Approve Invoice (Creates Batches)
POST /api/purchase-invoices/{invoice_id}/approve
```

### Workflow 3: Create Purchase Return

```bash
POST /api/purchase-returns
{
  "supplier_id": 1,
  "return_number": "RET-2025-0001",
  "return_date": "2025-01-21",
  "total_amount": 100.00,
  "items": [
    {
      "product_id": 1,
      "batch_id": 1,
      "quantity": 5.000,
      "unit_price": 20.00,
      "product_name": "منتج 1",
      "reason": "Damaged"
    }
  ]
}
```

### Workflow 4: Stock Adjustment

```bash
POST /api/stock-adjustments
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

---

## 🔍 Common Queries

### Get Product Stock
```bash
GET /api/products/{product_id}/stock?warehouse_id=1
```

### Get Product Batches
```bash
GET /api/products/{product_id}/batches?warehouse_id=1&status=active
```

### Get Supplier Balance
```bash
GET /api/suppliers/{supplier_id}/balance
```

### Get Supplier Transactions
```bash
GET /api/suppliers/{supplier_id}/transactions?from_date=2025-01-01&to_date=2025-01-31
```

### Get Low Stock Products
```bash
GET /api/products/low-stock?warehouse_id=1
```

---

## ⚠️ Important Rules

1. **Purchase Invoice:**
   - Must include `expiry_date` for all items
   - Must include `unit_id` (must exist in product_units)
   - Approve invoice to create batches

2. **Purchase Return:**
   - Must include `batch_id` for each item
   - Automatically processes on creation

3. **Stock Adjustment:**
   - `batch_id` is optional
   - If provided, batch quantity is automatically updated

4. **Product Units:**
   - At least one base unit required
   - Cannot delete base unit
   - Cannot delete unit used in invoices

---

## 📚 Full Documentation

See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for complete API reference.

