<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesAgentResource;
use App\Models\SalesAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesAgentController extends Controller
{
    public function index()
    {
        $agents = SalesAgent::withCount('sales')
            ->with('account')
            ->orderBy('name')
            ->get();

        return SalesAgentResource::collection($agents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'salary' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['salary'] = $validated['salary'] ?? 0;

        // Handle User link if password is provided or if phone already exists in users table
        if (($request->has('password') && !empty($request->password)) || !empty($request->phone)) {
            $user = \App\Models\User::where('phone', $request->phone)->first();

            if (!$user && !empty($request->password)) {
                // Create new user if not exists and password provided
                $user = \App\Models\User::create([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                    'role' => 'agent',
                    'status' => 'active',
                ]);
            } elseif ($user) {
                // If user exists, optionally update password if provided
                if (!empty($request->password)) {
                    $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
                }
                // Ensure role is agent if we are linking
                if ($user->role !== 'agent') {
                    $user->update(['role' => 'agent']);
                }
            }

            if ($user) {
                $validated['user_id'] = $user->id;
            }
        }

        $agent = SalesAgent::create($validated);

        return response()->json([
            'message' => 'تم إنشاء المندوب بنجاح',
            'agent' => new SalesAgentResource($agent)
        ], 201);
    }

    public function show(SalesAgent $salesAgent)
    {
        $salesAgent->loadCount('sales');
        $salesAgent->load('account');

        return new SalesAgentResource($salesAgent);
    }

    public function update(Request $request, SalesAgent $salesAgent)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'salary' => 'sometimes|required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $salesAgent->update($validated);

        return response()->json([
            'message' => 'تم تحديث المندوب بنجاح',
            'agent' => new SalesAgentResource($salesAgent)
        ]);
    }

    public function destroy(SalesAgent $salesAgent)
    {
        // Check if agent has sales
        if ($salesAgent->sales()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف مندوب لديه فواتير مبيعات. يمكنك إيقافه بدلاً من ذلك.'
            ], 400);
        }

        $salesAgent->delete();

        return response()->json([
            'message' => 'تم حذف المندوب بنجاح'
        ]);
    }

    public function calculateCommission(Request $request, SalesAgent $salesAgent)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Get delivered sales for the period
        $totalSales = $salesAgent->sales()
            ->where('status', 'delivered')
            ->whereBetween('invoice_date', [$validated['start_date'], $validated['end_date']])
            ->sum('total_iqd');

        $commissionRate = $salesAgent->commission_rate ?? 0;
        $commission = ($totalSales * $commissionRate) / 100;

        return response()->json([
            'agent_id' => $salesAgent->id,
            'agent_name' => $salesAgent->name,
            'period' => [
                'start' => $validated['start_date'],
                'end' => $validated['end_date'],
            ],
            'total_sales' => $totalSales,
            'commission_rate' => $commissionRate,
            'commission_amount' => round($commission, 2),
        ]);
    }
}
