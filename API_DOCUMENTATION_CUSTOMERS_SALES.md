# API Documentation: Customers & Smart Sale Invoices System

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Customers Management](#customers-management)
4. [Smart Sale Invoices](#smart-sale-invoices)
5. [Customer Payments](#customer-payments)
6. [Customer Balance & Transactions](#customer-balance--transactions)
7. [Business Logic](#business-logic)
8. [Error Handling](#error-handling)

---

## Overview

This API provides comprehensive management for customers, smart sale invoices, and customer payments. The system supports:

- **Customer Management**: Create, update, and manage customers with representative assignments
- **Smart Sale Invoices**: Flexible invoice system supporting 4 buyer types:
  - `customer`: Regular customers (can have credit/debt)
  - `walk_in`: Walk-in customers (cash only, temporary name)
  - `employee`: Employees (special discounts, cash only)
  - `representative`: Representatives (special discounts, cash only)
- **Seller Types**: Invoices can be created by:
  - **Office** (Manager/Employee): `representative_id = null`
  - **Representative**: `representative_id = value`
- **Customer Payments**: Track and manage customer payments
- **Balance Management**: Automatic balance tracking and transaction history

---

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

Only Managers can access these endpoints (protected by `manager.only` middleware).

---

## Customers Management

### Base URL
```
/api/customers
```

### 1. List Customers

**Endpoint:** `GET /api/customers`

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `inactive`)
- `search` (optional): Search by name or phone number
- `representative_id` (optional): Filter by assigned representative
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "customer_id": 1,
        "customer_name": "Ahmed Ali",
        "phone_number": "07501234567",
        "address": "Baghdad, Al-Karada",
        "total_debt": 500000.00,
        "total_paid": 250000.00,
        "last_payment_date": "2026-01-15",
        "status": "active",
        "notes": null,
        "balance": {
          "balance_id": 1,
          "customer_id": 1,
          "current_balance": 500000.00,
          "total_debt": 750000.00,
          "total_paid": 250000.00
        },
        "representatives": [
          {
            "rep_id": 1,
            "full_name": "Mohammed Hassan",
            "phone_number": "07509876543"
          }
        ],
        "created_at": "2026-01-10T10:00:00.000000Z",
        "updated_at": "2026-01-15T14:30:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 15
  }
}
```

**cURL Example:**
```bash
curl -X GET "http://localhost/api/customers?status=active&search=Ahmed" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Flutter/Dart Example:**
```dart
final response = await http.get(
  Uri.parse('http://localhost/api/customers?status=active&search=Ahmed'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  },
);

final data = json.decode(response.body);
```

---

### 2. Create Customer

**Endpoint:** `POST /api/customers`

**Request Body:**
```json
{
  "customer_name": "Ahmed Ali",
  "phone_number": "07501234567",
  "address": "Baghdad, Al-Karada",
  "status": "active",
  "notes": "Regular customer"
}
```

**Validation Rules:**
- `customer_name`: required, string, max 255
- `phone_number`: optional, string, max 20, unique
- `address`: optional, string
- `status`: optional, enum (`active`, `inactive`)
- `notes`: optional, string

**Response:**
```json
{
  "status": "success",
  "message": "Customer created successfully",
  "data": {
    "customer_id": 1,
    "customer_name": "Ahmed Ali",
    "phone_number": "07501234567",
    "address": "Baghdad, Al-Karada",
    "total_debt": 0.00,
    "total_paid": 0.00,
    "status": "active",
    "notes": "Regular customer",
    "balance": {
      "balance_id": 1,
      "current_balance": 0.00,
      "total_debt": 0.00,
      "total_paid": 0.00
    },
    "created_at": "2026-01-18T10:00:00.000000Z"
  }
}
```

**cURL Example:**
```bash
curl -X POST "http://localhost/api/customers" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_name": "Ahmed Ali",
    "phone_number": "07501234567",
    "address": "Baghdad, Al-Karada",
    "status": "active",
    "notes": "Regular customer"
  }'
```

**Flutter/Dart Example:**
```dart
final response = await http.post(
  Uri.parse('http://localhost/api/customers'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: json.encode({
    'customer_name': 'Ahmed Ali',
    'phone_number': '07501234567',
    'address': 'Baghdad, Al-Karada',
    'status': 'active',
    'notes': 'Regular customer',
  }),
);

final data = json.decode(response.body);
```

---

### 3. Get Customer

**Endpoint:** `GET /api/customers/{customer}`

**Response:**
```json
{
  "status": "success",
  "data": {
    "customer_id": 1,
    "customer_name": "Ahmed Ali",
    "phone_number": "07501234567",
    "address": "Baghdad, Al-Karada",
    "total_debt": 500000.00,
    "total_paid": 250000.00,
    "last_payment_date": "2026-01-15",
    "status": "active",
    "notes": null,
    "balance": {
      "balance_id": 1,
      "current_balance": 500000.00,
      "total_debt": 750000.00,
      "total_paid": 250000.00
    },
    "representatives": [...],
    "creator": {
      "manager_id": 1,
      "full_name": "Manager Name"
    }
  }
}
```

---

### 4. Update Customer

**Endpoint:** `PUT /api/customers/{customer}`

**Request Body:** (Same as create, all fields optional with `sometimes`)

**Response:** (Same structure as Get Customer)

---

### 5. Delete Customer

**Endpoint:** `DELETE /api/customers/{customer}`

**Note:** Cannot delete customer with unpaid invoices.

**Response:**
```json
{
  "status": "success",
  "message": "Customer deleted successfully",
  "data": null
}
```

---

### 6. Get Customer Balance

**Endpoint:** `GET /api/customers/{customer}/balance`

**Response:**
```json
{
  "status": "success",
  "data": {
    "balance_id": 1,
    "customer_id": 1,
    "current_balance": 500000.00,
    "total_debt": 750000.00,
    "total_paid": 250000.00,
    "last_transaction_at": "2026-01-18T14:30:00.000000Z"
  }
}
```

---

### 7. Get Customer Transactions

**Endpoint:** `GET /api/customers/{customer}/transactions`

**Query Parameters:**
- `type` (optional): Filter by transaction type (`invoice`, `payment`, `adjustment`, `refund`)
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `per_page` (optional): Items per page

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "transaction_id": 1,
        "customer_id": 1,
        "transaction_type": "invoice",
        "amount": 500000.00,
        "balance_before": 0.00,
        "balance_after": 500000.00,
        "related_type": "sale_invoice",
        "related_id": 1,
        "description": "فاتورة بيع: SAL-2026-0001",
        "created_at": "2026-01-18T10:00:00.000000Z"
      }
    ]
  }
}
```

---

### 8. Get Customer Invoices

**Endpoint:** `GET /api/customers/{customer}/invoices`

**Query Parameters:**
- `status` (optional): Filter by invoice status
- `per_page` (optional): Items per page

**Response:** (List of sale invoices)

---

### 9. Get Customer Representatives

**Endpoint:** `GET /api/customers/{customer}/representatives`

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "rep_id": 1,
      "full_name": "Mohammed Hassan",
      "phone_number": "07509876543"
    }
  ]
}
```

---

### 10. Assign Representative to Customer

**Endpoint:** `POST /api/customers/{customer}/representatives/{representative}`

**Response:**
```json
{
  "status": "success",
  "message": "Representative assigned successfully",
  "data": null
}
```

---

### 11. Remove Representative from Customer

**Endpoint:** `DELETE /api/customers/{customer}/representatives/{representative}`

**Response:**
```json
{
  "status": "success",
  "message": "Representative removed successfully",
  "data": null
}
```

---

## Smart Sale Invoices

### Base URL
```
/api/sale-invoices
```

### Overview

Smart Sale Invoices support 4 buyer types:

1. **Customer** (`buyer_type: "customer"`):
   - Requires `customer_id`
   - Supports credit/debt (`payment_method: "credit"`)
   - Can have `due_date`
   - Updates customer balance

2. **Walk-in** (`buyer_type: "walk_in"`):
   - Requires `buyer_name` (temporary name)
   - Cash only (`payment_method: "cash"`)
   - No debt tracking
   - No customer record created

3. **Employee** (`buyer_type: "employee"`):
   - Requires `buyer_id` (employee ID)
   - Cash only (`payment_method: "cash"`)
   - Supports `special_discount_percentage`
   - No debt tracking

4. **Representative** (`buyer_type: "representative"`):
   - Requires `buyer_id` (representative ID)
   - Cash only (`payment_method: "cash"`)
   - Supports `special_discount_percentage`
   - No debt tracking

**Seller Types:**
- **Office**: `representative_id: null` (sold by Manager/Employee in office)
- **Representative**: `representative_id: {id}` (sold by specific representative)

---

### 1. List Sale Invoices

**Endpoint:** `GET /api/sale-invoices`

**Query Parameters:**
- `buyer_type` (optional): Filter by buyer type
- `customer_id` (optional): Filter by customer
- `representative_id` (optional): Filter by seller representative
- `status` (optional): Filter by status
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `search` (optional): Search by invoice number or buyer name
- `per_page` (optional): Items per page

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "invoice_id": 1,
        "representative_id": null,
        "representative": null,
        "buyer_type": "customer",
        "buyer_id": 1,
        "buyer_name": null,
        "customer_id": 1,
        "customer": {
          "customer_id": 1,
          "customer_name": "Ahmed Ali"
        },
        "invoice_number": "SAL-2026-0001",
        "invoice_date": "2026-01-18",
        "due_date": "2026-02-18",
        "subtotal": 1000000.00,
        "tax_amount": 0.00,
        "discount_amount": 0.00,
        "special_discount_percentage": 0.00,
        "special_discount_amount": 0.00,
        "total_amount": 1000000.00,
        "paid_amount": 0.00,
        "remaining_amount": 1000000.00,
        "payment_method": "credit",
        "status": "pending",
        "buyer_display_name": "Ahmed Ali",
        "is_overdue": false,
        "items": [
          {
            "item_id": 1,
            "product_id": 1,
            "product_name": "Product Name",
            "quantity": 10.00,
            "unit_price": 100000.00,
            "total_price": 1000000.00,
            "profit_amount": 200000.00,
            "profit_percentage": 25.00
          }
        ]
      }
    ]
  }
}
```

---

### 2. Create Sale Invoice (Customer)

**Endpoint:** `POST /api/sale-invoices`

**Request Body (Customer):**
```json
{
  "representative_id": null,
  "buyer_type": "customer",
  "customer_id": 1,
  "invoice_number": "SAL-2026-0001",
  "invoice_date": "2026-01-18",
  "due_date": "2026-02-18",
  "subtotal": 1000000.00,
  "tax_amount": 0.00,
  "discount_amount": 0.00,
  "total_amount": 1000000.00,
  "payment_method": "credit",
  "notes": null,
  "items": [
    {
      "product_id": 1,
      "quantity": 10.00,
      "unit_price": 100000.00,
      "discount_percentage": 0.00,
      "tax_percentage": 0.00
    }
  ]
}
```

---

### 3. Create Sale Invoice (Walk-in)

**Request Body (Walk-in):**
```json
{
  "representative_id": null,
  "buyer_type": "walk_in",
  "buyer_name": "عميل عادي - رقم 123",
  "invoice_number": "SAL-2026-0002",
  "invoice_date": "2026-01-18",
  "subtotal": 500000.00,
  "tax_amount": 0.00,
  "discount_amount": 0.00,
  "total_amount": 500000.00,
  "payment_method": "cash",
  "items": [
    {
      "product_id": 2,
      "quantity": 5.00,
      "unit_price": 100000.00
    }
  ]
}
```

**Note:** `payment_method` is automatically set to `cash` for walk-in customers.

---

### 4. Create Sale Invoice (Employee)

**Request Body (Employee):**
```json
{
  "representative_id": null,
  "buyer_type": "employee",
  "buyer_id": 1,
  "invoice_number": "SAL-2026-0003",
  "invoice_date": "2026-01-18",
  "subtotal": 500000.00,
  "special_discount_percentage": 10.00,
  "tax_amount": 0.00,
  "discount_amount": 0.00,
  "total_amount": 450000.00,
  "payment_method": "cash",
  "items": [
    {
      "product_id": 3,
      "quantity": 5.00,
      "unit_price": 100000.00
    }
  ]
}
```

**Note:** 
- `payment_method` is automatically set to `cash`
- `special_discount_amount` is calculated automatically: `subtotal * (special_discount_percentage / 100)`
- `total_amount = subtotal - discount_amount - special_discount_amount + tax_amount`

---

### 5. Create Sale Invoice (Representative)

**Request Body (Representative):**
```json
{
  "representative_id": null,
  "buyer_type": "representative",
  "buyer_id": 1,
  "invoice_number": "SAL-2026-0004",
  "invoice_date": "2026-01-18",
  "subtotal": 500000.00,
  "special_discount_percentage": 15.00,
  "tax_amount": 0.00,
  "discount_amount": 0.00,
  "total_amount": 425000.00,
  "payment_method": "cash",
  "items": [
    {
      "product_id": 4,
      "quantity": 5.00,
      "unit_price": 100000.00
    }
  ]
}
```

---

### 6. Create Sale Invoice (Sold by Representative)

**Request Body:**
```json
{
  "representative_id": 1,
  "buyer_type": "customer",
  "customer_id": 1,
  "invoice_number": "SAL-2026-0005",
  "invoice_date": "2026-01-18",
  "due_date": "2026-02-18",
  "subtotal": 1000000.00,
  "total_amount": 1000000.00,
  "payment_method": "credit",
  "items": [...]
}
```

**Note:** When `representative_id` is provided, the invoice is recorded as sold by that representative.

---

### 7. Get Sale Invoice

**Endpoint:** `GET /api/sale-invoices/{sale_invoice}`

**Response:** (Full invoice details with items and payments)

---

### 8. Update Sale Invoice

**Endpoint:** `PUT /api/sale-invoices/{sale_invoice}`

**Note:** Only `draft` invoices can be updated.

**Request Body:** (Same structure as create, all fields optional)

---

### 9. Delete Sale Invoice

**Endpoint:** `DELETE /api/sale-invoices/{sale_invoice}`

**Note:** Only `draft` invoices can be deleted.

---

### 10. Post (Confirm) Sale Invoice

**Endpoint:** `POST /api/sale-invoices/{sale_invoice}/post`

**Note:** Only `draft` invoices can be posted.

**What happens when posting:**
1. Inventory stock is updated for each item
2. Inventory movements are created
3. Product `last_sale_date` is updated
4. If `buyer_type = customer` and `payment_method = credit`:
   - Customer balance is updated
   - Balance transaction is recorded
5. Invoice status changes from `draft` to `pending`

**Response:**
```json
{
  "status": "success",
  "message": "Sale invoice posted successfully",
  "data": {
    "invoice_id": 1,
    "status": "pending",
    ...
  }
}
```

**cURL Example:**
```bash
curl -X POST "http://localhost/api/sale-invoices/1/post" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 11. Duplicate Sale Invoice

**Endpoint:** `POST /api/sale-invoices/{sale_invoice}/duplicate`

**Response:**
```json
{
  "status": "success",
  "message": "Sale invoice duplicated successfully",
  "data": {
    "invoice_id": 2,
    "invoice_number": "SAL-2026-0002",
    "status": "draft",
    ...
  }
}
```

---

### 12. Get Invoice Payments

**Endpoint:** `GET /api/sale-invoices/{sale_invoice}/payments`

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "payment_id": 1,
      "customer_id": 1,
      "invoice_id": 1,
      "payment_date": "2026-01-20",
      "amount": 500000.00,
      "payment_method": "cash"
    }
  ]
}
```

---

## Customer Payments

### Base URL
```
/api/customer-payments
```

### 1. List Customer Payments

**Endpoint:** `GET /api/customer-payments`

**Query Parameters:**
- `customer_id` (optional): Filter by customer
- `invoice_id` (optional): Filter by invoice
- `from_date` (optional): Filter from date
- `to_date` (optional): Filter to date
- `per_page` (optional): Items per page

---

### 2. Create Customer Payment

**Endpoint:** `POST /api/customer-payments`

**Request Body:**
```json
{
  "customer_id": 1,
  "invoice_id": 1,
  "payment_date": "2026-01-20",
  "amount": 500000.00,
  "payment_method": "cash",
  "reference_number": "CHK-12345",
  "notes": "Partial payment"
}
```

**Validation Rules:**
- `customer_id`: required, exists in customers
- `invoice_id`: optional, exists in sale_invoices (must belong to customer)
- `payment_date`: required, date
- `amount`: required, numeric, min 0.01
- `payment_method`: optional, enum (`cash`, `bank_transfer`, `cheque`, `other`)
- `reference_number`: optional, string
- `notes`: optional, string

**What happens:**
1. Payment is recorded
2. If `invoice_id` provided:
   - Invoice `paid_amount` is updated
   - Invoice `remaining_amount` is recalculated
   - Invoice status is updated (paid/partial)
3. Customer balance is updated
4. Balance transaction is recorded
5. Customer `total_paid` and `last_payment_date` are updated

**Response:**
```json
{
  "status": "success",
  "message": "Payment recorded successfully",
  "data": {
    "payment_id": 1,
    "customer_id": 1,
    "invoice_id": 1,
    "payment_date": "2026-01-20",
    "amount": 500000.00,
    "payment_method": "cash",
    "reference_number": "CHK-12345",
    "notes": "Partial payment",
    "created_at": "2026-01-20T10:00:00.000000Z"
  }
}
```

**cURL Example:**
```bash
curl -X POST "http://localhost/api/customer-payments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "invoice_id": 1,
    "payment_date": "2026-01-20",
    "amount": 500000.00,
    "payment_method": "cash",
    "reference_number": "CHK-12345",
    "notes": "Partial payment"
  }'
```

**Flutter/Dart Example:**
```dart
final response = await http.post(
  Uri.parse('http://localhost/api/customer-payments'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: json.encode({
    'customer_id': 1,
    'invoice_id': 1,
    'payment_date': '2026-01-20',
    'amount': 500000.00,
    'payment_method': 'cash',
    'reference_number': 'CHK-12345',
    'notes': 'Partial payment',
  }),
);

final data = json.decode(response.body);
```

---

### 3. Get Customer Payment

**Endpoint:** `GET /api/customer-payments/{customer_payment}`

---

### 4. Update Customer Payment

**Endpoint:** `PUT /api/customer-payments/{customer_payment}`

**Request Body:** (All fields optional)
```json
{
  "payment_date": "2026-01-21",
  "amount": 600000.00,
  "payment_method": "bank_transfer",
  "reference_number": "TRF-67890",
  "notes": "Updated payment"
}
```

**Note:** If amount is changed, invoice and balance are automatically updated.

---

### 5. Delete Customer Payment

**Endpoint:** `DELETE /api/customer-payments/{customer_payment}`

**Note:** Payment deletion reverses all balance and invoice updates.

---

### 6. Apply Payment to Invoice

**Endpoint:** `POST /api/customer-payments/{payment}/apply-to-invoice/{invoice}`

**Note:** Transfers payment from current invoice (if any) to the specified invoice.

**Response:**
```json
{
  "status": "success",
  "message": "Payment applied to invoice successfully",
  "data": {
    "payment_id": 1,
    "invoice_id": 2,
    ...
  }
}
```

---

## Customer Balance & Transactions

### Customer Balance

The balance is automatically maintained in the `customer_balances` table:

- `current_balance`: Current outstanding debt (positive = debt)
- `total_debt`: Total debt accumulated from all invoices
- `total_paid`: Total payments received

### Balance Transactions

Every invoice creation or payment creates a transaction record:

**Transaction Types:**
- `invoice`: Invoice creation (increases debt)
- `payment`: Payment received (decreases debt)
- `adjustment`: Manual adjustment
- `refund`: Refund issued

**Transaction Fields:**
- `amount`: Positive for debt increase, negative for payment
- `balance_before`: Balance before transaction
- `balance_after`: Balance after transaction
- `related_type`: `sale_invoice` or `customer_payment`
- `related_id`: ID of related invoice or payment

---

## Business Logic

### Invoice Status Flow

1. **draft**: Initial state when created
   - Can be updated or deleted
   - Inventory not affected
   - Balance not affected

2. **pending**: After posting (`POST /post`)
   - Inventory updated
   - Balance updated (if credit customer)
   - Cannot be deleted (can be cancelled)

3. **paid**: All amount paid
   - `paid_amount == total_amount`
   - `remaining_amount == 0`

4. **partial**: Partially paid
   - `paid_amount > 0` and `paid_amount < total_amount`

5. **overdue**: Past due date with remaining amount
   - `due_date < now()` and `remaining_amount > 0`

6. **cancelled**: Cancelled invoice
   - Reverses inventory and balance changes

### Special Discount Calculation

For employees and representatives:

```php
special_discount_amount = subtotal × (special_discount_percentage / 100)
total_amount = subtotal - discount_amount - special_discount_amount + tax_amount
```

### Payment Application

When a payment is created:

1. If `invoice_id` is provided:
   - Payment is linked to invoice
   - Invoice `paid_amount` += payment `amount`
   - Invoice `remaining_amount` = `total_amount` - `paid_amount`
   - Invoice status updated

2. Customer balance:
   - `current_balance` -= payment `amount` (payment reduces debt)
   - `total_paid` += payment `amount`

3. Transaction recorded:
   - Type: `payment`
   - Amount: negative (reduces debt)

### Invoice Posting

When an invoice is posted:

1. **Inventory Update:**
   - For each item:
     - Product stock -= item quantity
     - Inventory movement created
     - Product `last_sale_date` updated

2. **Customer Balance (if customer + credit):**
   - Balance `current_balance` += invoice `remaining_amount`
   - Balance `total_debt` += invoice `remaining_amount`
   - Transaction recorded (type: `invoice`, amount: positive)

3. **Invoice Status:**
   - Changed from `draft` to `pending`

---

## Error Handling

### Standard Error Response

```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### Common Errors

**400 Bad Request:**
- Invalid request data
- Validation errors

**401 Unauthorized:**
- Missing or invalid token
- Token expired

**403 Forbidden:**
- User is not a manager

**404 Not Found:**
- Resource not found

**422 Unprocessable Entity:**
- Business rule violation
- Cannot delete customer with unpaid invoices
- Cannot update/post non-draft invoice
- Insufficient stock

**500 Internal Server Error:**
- Server error
- Check logs for details

---

## Example Workflows

### Workflow 1: Customer Sale with Credit

1. **Create Customer:**
   ```bash
   POST /api/customers
   ```

2. **Create Sale Invoice (draft):**
   ```bash
   POST /api/sale-invoices
   {
     "buyer_type": "customer",
     "customer_id": 1,
     "payment_method": "credit",
     ...
   }
   ```

3. **Post Invoice:**
   ```bash
   POST /api/sale-invoices/1/post
   ```
   - Inventory updated
   - Customer balance updated
   - Invoice status: `pending`

4. **Record Payment:**
   ```bash
   POST /api/customer-payments
   {
     "customer_id": 1,
     "invoice_id": 1,
     "amount": 500000.00
   }
   ```
   - Invoice `paid_amount` updated
   - Invoice status: `partial` or `paid`
   - Customer balance decreased

### Workflow 2: Walk-in Sale (Cash)

1. **Create Sale Invoice:**
   ```bash
   POST /api/sale-invoices
   {
     "buyer_type": "walk_in",
     "buyer_name": "عميل عادي",
     "payment_method": "cash",
     ...
   }
   ```

2. **Post Invoice:**
   ```bash
   POST /api/sale-invoices/1/post
   ```
   - Only inventory updated
   - No balance tracking
   - Invoice status: `paid` (cash payment)

### Workflow 3: Employee Purchase with Discount

1. **Create Sale Invoice:**
   ```bash
   POST /api/sale-invoices
   {
     "buyer_type": "employee",
     "buyer_id": 1,
     "special_discount_percentage": 10.00,
     ...
   }
   ```

2. **Post Invoice:**
   ```bash
   POST /api/sale-invoices/1/post
   ```
   - Inventory updated
   - Special discount applied
   - Invoice status: `paid` (cash payment)

---

## Summary

This API provides a comprehensive system for managing customers, smart sale invoices, and payments. The system is flexible enough to handle:

- Regular customers with credit/debt tracking
- Walk-in customers (cash only)
- Employee/Representative purchases with special discounts
- Sales from office or by representatives
- Automatic balance and transaction tracking

All endpoints are protected and require manager authentication. The system automatically handles inventory updates, balance tracking, and transaction recording.

