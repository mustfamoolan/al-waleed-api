# Finance API



## Receipts

### Create Receipt (Draft)
**POST** `/api/receipts`
- `amount_iqd`, `receipt_type` (customer_payment / general_income)

### Allocate to Invoice
**POST** `/api/receipts/{id}/allocate`
- `sales_invoice_id`, `allocated_iqd`

### Post Receipt
**POST** `/api/receipts/{id}/post`
- Generates Journal Entry + Updates Invoice Balance.

## Payments

### Create Payment (Draft)
**POST** `/api/payments`
- `amount_iqd`, `payment_type` (supplier_payment / expense / ...)

### Allocate to Invoice
**POST** `/api/payments/{id}/allocate`
- `purchase_invoice_id`, `allocated_iqd`

### Post Payment
**POST** `/api/payments/{id}/post`
- Generates Journal Entry + Updates Bill Balance.
