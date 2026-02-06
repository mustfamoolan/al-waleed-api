# Reports API

## Statements
**GET** `/api/reports/customer-statement`
- `customer_id` (required), `date_from`, `date_to`
- Return: Opening Bal, Invoices, Receipts, Closing Bal.

**GET** `/api/reports/supplier-statement`
- `supplier_id` (required), `date_from`, `date_to`

**GET** `/api/reports/customer-purchases`
- `customer_id` (required)
- Return: Products purchased, quantity, total spent.

**GET** `/api/reports/staff-financials` (Formerly staff-transactions)
- `staff_id` (required)
- Return: Sales to staff, Advances, Adjustments.

## Financials
**GET** `/api/reports/profit-summary`
- `date_from`, `date_to`
- Return: Revenue, COGS, Gross Profit, Expenses, Net Profit.

**GET** `/api/reports/debts-summary`
- `date_as_of`
- Return: Total Receivables/Payables, Top Debtors/Creditors.

**GET** `/api/reports/cash-movements`
- `date_from`, `date_to`
- Return: Inflow/Outflow of Main Cashbox.

**GET** `/api/reports/customer-profit`
- `customer_id`
- Return: Total Revenue, Total Cost, Total Profit from that customer.

**GET** `/api/reports/aging`
- `type` (customer|supplier), `date_as_of`
- Return: Overdue invoices with days overdue.

## Analytics & Operations
**GET** `/api/reports/product-movement`
- `product_id` (required)
- Return: Inventory transactions.

**GET** `/api/reports/inventory-balances`
- `warehouse_id`
- Return: Current stock levels.

**GET** `/api/reports/top-products` / `/api/reports/low-products`
- Sort by Quantity Sold.

**GET** `/api/reports/top-profit-products` / `/api/reports/low-profit-products`
- Sort by Total Profit (Revenue - Cost).

**GET** `/api/reports/product-profit`
- `product_id` (optional)
- Return: Profitability details per product.

**GET** `/api/reports/agent-performance`
- `staff_id`, `period_month`
- Return: Sales, Commissions, Targets.
