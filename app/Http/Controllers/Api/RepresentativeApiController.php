<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Receipt;
use App\Models\AgentTarget;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepresentativeApiController extends Controller
{
    /**
     * Get authenticated user and their agent data.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('salesAgent');
        return response()->json([
            'user' => $user,
            'agent' => $user->salesAgent
        ]);
    }

    /**
     * Agent Dashboard
     */
    public function dashboard(Request $request)
    {
        $agent = $request->user()->salesAgent;

        if (!$agent) {
            return response()->json(['message' => 'User is not associated with any sales agent'], 404);
        }

        // Stats
        $customersCount = Customer::where('agent_id', $agent->id)->count();
        $totalSalesMonth = SalesInvoice::where('agent_id', $agent->id)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'delivered')
            ->sum('total_iqd');

        $totalCollectedMonth = Receipt::where('agent_id', $agent->id)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'posted')
            ->sum('amount_iqd');

        // Recent Sales
        $recentSales = SalesInvoice::where('agent_id', $agent->id)
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_customers' => $customersCount,
                'total_sales_month' => $totalSalesMonth,
                'total_collected_month' => $totalCollectedMonth,
            ],
            'recent_sales' => $recentSales,
        ]);
    }

    /**
     * Agent's Customers
     */
    public function customers(Request $request)
    {
        $agent = $request->user()->salesAgent;
        if (!$agent)
            return response()->json(['data' => []]);

        $customers = Customer::where('agent_id', $agent->id)
            ->with('addresses')
            ->get();

        return response()->json(['data' => $customers]);
    }

    /**
     * Agent's Sales
     */
    public function sales(Request $request)
    {
        $agent = $request->user()->salesAgent;
        if (!$agent)
            return response()->json(['data' => []]);

        $sales = SalesInvoice::where('agent_id', $agent->id)
            ->with(['customer', 'lines.product', 'lines.unit'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $sales]);
    }

    /**
     * Store new sales invoice from agent app
     */
    public function storeInvoice(Request $request)
    {
        $agent = $request->user()->salesAgent;
        if (!$agent)
            abort(403, 'Unauthorized');

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.qty' => 'required|numeric|min:1',
            'lines.*.unit_id' => 'required|exists:units,id',
            'lines.*.price_iqd' => 'required|numeric|min:0',
            'payment_type' => 'required|in:cash,credit',
            'notes' => 'nullable|string',
        ]);

        $invoice = DB::transaction(function () use ($validated, $agent) {
            $subtotalIqd = 0;
            foreach ($validated['lines'] as $line) {
                $subtotalIqd += ($line['qty'] * $line['price_iqd']);
            }

            $invoice = SalesInvoice::create([
                'invoice_no' => 'REP-' . strtoupper(uniqid()),
                'source_type' => 'agent',
                'source_user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'customer_id' => $validated['customer_id'],
                'payment_type' => $validated['payment_type'],
                'subtotal_iqd' => $subtotalIqd,
                'total_iqd' => $subtotalIqd,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['lines'] as $line) {
                SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_id' => $line['unit_id'],
                    'price_iqd' => $line['price_iqd'],
                    'line_total_iqd' => $line['qty'] * $line['price_iqd'],
                ]);
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'تم إنشاء الفاتورة كمسودة بنجاح',
            'data' => $invoice->load('lines.product')
        ]);
    }

    /**
     * Submit invoice for approval
     */
    public function submitInvoice(SalesInvoice $invoice)
    {
        $agent = auth()->user()->salesAgent;
        if ($invoice->agent_id !== $agent->id)
            abort(403);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'الفاتورة ليست في حالة مسودة'], 400);
        }

        $invoice->update(['status' => 'pending_approval']);
        return response()->json(['message' => 'تم إرسال الفاتورة للموافقة']);
    }

    /**
     * Agent's Targets
     */
    public function targets(Request $request)
    {
        $agent = $request->user()->salesAgent;
        if (!$agent)
            return response()->json(['data' => []]);

        $targets = AgentTarget::where('staff_id', $agent->id)
            ->with(['items.product', 'items.category'])
            ->orderBy('period_month', 'desc')
            ->get();

        return response()->json(['data' => $targets]);
    }

    /**
     * Store a new customer for the authenticated agent
     */
    public function storeCustomer(Request $request)
    {
        $agent = $request->user()->salesAgent;
        if (!$agent)
            abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? '',
            'agent_id' => $agent->id,
            'sales_type' => 'credit',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'تم إضافة الزبون بنجاح',
            'data' => $customer
        ]);
    }

    /**
     * Get products available for sale
     */
    public function products(Request $request)
    {
        $products = Product::where('is_active', true)
            ->with(['category', 'baseUnit', 'packUnit'])
            ->withSum('balances', 'qty_on_hand')
            ->get();

        return response()->json(['data' => $products]);
    }
}
