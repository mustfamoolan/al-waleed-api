<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|in:supplier_payment,expense,salary_payment,advance',
            'amount_iqd' => 'required|numeric|min:0.01',
            'supplier_id' => 'required_if:payment_type,supplier_payment|exists:suppliers,id',
            'expense_account_id' => 'required_if:payment_type,expense|exists:accounts,id',
            'staff_id' => 'required_if:payment_type,advance,salary_payment|exists:staff,id',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create([
            'payment_no' => 'PY' . date('Y') . str_pad((Payment::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT),
            'party_id' => $request->party_id,
            'supplier_id' => $request->supplier_id,
            'staff_id' => $request->staff_id,
            'expense_account_id' => $request->expense_account_id,
            'payment_type' => $request->payment_type,
            'amount_iqd' => $request->amount_iqd,
            'status' => 'draft',
            'created_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Payment created', 'payment' => $payment], 201);
    }

    public function allocate(Request $request, Payment $payment)
    {
        if ($payment->status !== 'draft')
            abort(400, 'Must be draft');

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'purchase_invoice_id' => $request->purchase_invoice_id,
            'allocated_iqd' => $request->allocated_iqd,
        ]);

        return response()->json(['message' => 'Allocated']);
    }

    public function post(Payment $payment)
    {
        if ($payment->status !== 'draft')
            abort(400, 'Invalid status');
        $payment->update(['status' => 'posted']);
        return response()->json(['message' => 'Payment posted']);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load('allocations.invoice'));
    }

    public function destroy(Payment $payment)
    {
        if ($payment->status !== 'draft') {
            return response()->json(['message' => 'Cannot delete posted payment'], 400);
        }
        $payment->delete();
        return response()->json(['message' => 'Payment deleted']);
    }
}
