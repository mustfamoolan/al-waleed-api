# Payroll & Targets API

## Attendance
**POST** `/api/attendance`
- `staff_id`, `date`, `status`, `minutes_late`

## Adjustments
**POST** `/api/payroll-adjustments`
- `type` (allowance/deduction/penalty/advance_repayment)
- `amount_iqd`

## Targets
**POST** `/api/agent-targets`
- `staff_id`, `period_month`
- `target_type` (product/supplier/category)
- `items`: `[{product_id: 1}, ...]`

## Payroll Run
**POST** `/api/payroll-runs/calculate`
- `period_month` (e.g. "2026-02")
- Triggers Target Calculation -> Then Payroll Calculation.

**POST** `/api/payroll-runs/{id}/approve`
**POST** `/api/payroll-runs/{id}/post`
- Generates Journal Entry (Salaries Expense).
