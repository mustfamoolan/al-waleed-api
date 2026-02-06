<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use App\Services\TargetService;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    protected $payrollService;
    protected $targetService;

    public function __construct(PayrollService $payrollService, TargetService $targetService)
    {
        $this->payrollService = $payrollService;
        $this->targetService = $targetService;
    }

    public function calculate(Request $request)
    {
        $request->validate(['period_month' => 'required|date_format:Y-m']);

        // 1. Calculate Targets First
        $this->targetService->calculate($request->period_month);

        // 2. Calculate Payroll
        $run = $this->payrollService->calculateRun($request->period_month);

        return response()->json(['message' => 'Payroll calculated', 'run' => $run->load('lines')]);
    }

    public function approve(PayrollRun $run)
    {
        if ($run->status !== 'calculated')
            abort(400, 'Invalid status');
        $run->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return response()->json(['message' => 'Approved']);
    }

    public function post(PayrollRun $run)
    {
        if ($run->status !== 'approved')
            abort(400, 'Invalid status');
        $run->update(['status' => 'posted']);
        // Observer handles Journal Entry
        return response()->json(['message' => 'Posted']);
    }

    public function show(PayrollRun $run)
    {
        return response()->json($run->load('lines.staff'));
    }

    public function index()
    {
        return response()->json(PayrollRun::all());
    }
}
