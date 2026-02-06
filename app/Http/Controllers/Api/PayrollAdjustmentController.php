<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use Illuminate\Http\Request;

class PayrollAdjustmentController extends Controller
{
    public function store(Request $request)
    {
        $adj = PayrollAdjustment::create(array_merge($request->all(), ['created_by' => auth()->id()]));
        return response()->json($adj, 201);
    }

    public function index(Request $request)
    {
        $query = PayrollAdjustment::query();
        if ($request->has('staff_id'))
            $query->where('staff_id', $request->staff_id);
        if ($request->has('period_month'))
            $query->where('period_month', $request->period_month);
        return response()->json($query->get());
    }
}
