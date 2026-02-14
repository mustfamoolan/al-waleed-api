<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    // Simplified CRUD for brevity. Focus on Workflow.

    public function index()
    {
        return response()->json(SalesInvoice::with('party', 'customer', 'agent', 'creator', 'lines')->get());
    }

    public function show(SalesInvoice $invoice)
    {
        return response()->json($invoice->load('party', 'customer', 'agent', 'creator', 'lines'));
    }

    public function store(Request $request)
    {
        // Validation... (Create Request class ideally)
        $request->validate(['party_id' => 'required', 'payment_type' => 'required']);

        $invoice = DB::transaction(function () use ($request) {
            $subtotalIqd = 0;
            foreach ($request->lines as $line) {
                $subtotalIqd += ($line['qty'] * $line['price_iqd']);
            }
            $discountIqd = $request->discount_iqd ?? 0;
            $totalIqd = $subtotalIqd - $discountIqd;

            $invoice = SalesInvoice::create([
                'invoice_no' => 'SI-' . time(),
                'source_type' => $request->source_type ?? 'office',
                'source_user_id' => auth()->id(),
                'party_id' => $request->party_id,
                'customer_id' => $request->customer_id,
                'agent_id' => $request->agent_id,
                'payment_type' => $request->payment_type,
                'due_date' => $request->due_date,
                'delivery_required' => $request->delivery_required ?? false,
                'delivery_address_id' => $request->delivery_address_id,
                'subtotal_iqd' => $subtotalIqd,
                'discount_iqd' => $discountIqd,
                'total_iqd' => $totalIqd,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($request->lines as $line) {
                SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_id' => $line['unit_id'],
                    'unit_factor' => $line['unit_factor'] ?? 1,
                    'price_iqd' => $line['price_iqd'],
                    'line_total_iqd' => $line['qty'] * $line['price_iqd'],
                ]);
            }
            return $invoice;
        });

        // Auto submit/approve logic check could go here if source_type == office

        return response()->json(['message' => 'Invoice created', 'invoice' => $invoice->load('lines')], 201);
    }

    // Workflow Actions

    public function submit(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'draft')
            abort(400, 'Invalid status');
        $invoice->update(['status' => 'pending_approval']);
        return response()->json(['message' => 'Invoice submitted']);
    }

    public function approve(SalesInvoice $invoice)
    {
        if (!in_array($invoice->status, ['draft', 'pending_approval']))
            abort(400, 'Invalid status');
        $invoice->update([
            'status' => 'approved',
            'approved_by_user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'Invoice approved']);
    }

    public function startPreparing(SalesInvoice $invoice)
    {
        // Can prepare from approved
        if ($invoice->status !== 'approved')
            abort(400, 'Invalid status');
        $invoice->update(['status' => 'preparing']);
        return response()->json(['message' => 'Preparation started']);
    }

    public function markPrepared(SalesInvoice $invoice)
    {
        if (!in_array($invoice->status, ['approved', 'preparing']))
            abort(400, 'Invalid status');
        $invoice->update([
            'status' => 'prepared',
            'prepared_by_staff_id' => 1 // Hardcoded for simplified example or passed in request
        ]);
        // Observer handles Stock Deduction
        return response()->json(['message' => 'Invoice prepared and stock deducted']);
    }

    public function assignDriver(Request $request, SalesInvoice $invoice)
    {
        if (!in_array($invoice->status, ['prepared', 'assigned_to_driver']))
            abort(400, 'Invalid status');
        $invoice->update([
            'status' => 'assigned_to_driver',
            'driver_staff_id' => $request->driver_staff_id
        ]);
        return response()->json(['message' => 'Driver assigned']);
    }

    public function outForDelivery(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'assigned_to_driver')
            abort(400, 'Invalid status');
        $invoice->update(['status' => 'out_for_delivery']);
        return response()->json(['message' => 'Out for delivery']);
    }

    public function markDelivered(Request $request, SalesInvoice $invoice)
    {
        if (!in_array($invoice->status, ['prepared', 'assigned_to_driver', 'out_for_delivery']))
            abort(400, 'Invalid status');
        $invoice->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'paid_iqd' => $request->paid_iqd ?? 0 // If cash collection happen
        ]);
        // Observer handles Journal Entry
        return response()->json(['message' => 'Delivered and Financials recorded']);
    }

    public function cancel(SalesInvoice $invoice)
    {
        if (in_array($invoice->status, ['delivered', 'canceled', 'returned']))
            abort(400, 'Cannot cancel in this status');

        $invoice->update(['status' => 'canceled']);
        return response()->json(['message' => 'Invoice canceled']);
    }
}
