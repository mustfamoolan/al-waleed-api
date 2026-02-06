<?php

namespace App\Services;

use App\Models\AgentCommissionSummary;
use App\Models\Attendance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function calculateRun(string $periodMonth)
    {
        // 1. Create Draft Run
        $run = PayrollRun::firstOrCreate(
            ['period_month' => $periodMonth],
            ['status' => 'draft', 'created_by' => auth()->id()]
        );

        if ($run->status !== 'draft') {
            throw new \Exception("Payroll for $periodMonth is already {$run->status}");
        }

        // Delete existing lines if recalculating
        $run->lines()->delete();

        // 2. Fetch Active Staff
        $staffMembers = Staff::where('is_active', true)->get();

        foreach ($staffMembers as $staff) {
            // A. Base Salary
            $baseSalary = $staff->salary_monthly;

            // B. Attendance Deductions
            // Count 'absent' days in month
            $start = $periodMonth . '-01';
            $end = date("Y-m-t", strtotime($start));

            $absentDays = Attendance::where('staff_id', $staff->id)
                ->whereBetween('date', [$start, $end])
                ->where('status', 'absent')
                ->count();

            // Calc day rate (Monthly / 30)
            $dayRate = $baseSalary / 30; // Standard 30 days
            $attendanceDed = $absentDays * $dayRate;

            // C. Adjustments
            $adjs = PayrollAdjustment::where('staff_id', $staff->id)
                ->where('period_month', $periodMonth)
                ->get();

            $plus = $adjs->whereIn('type', ['allowance', 'bonus_manual'])->sum('amount_iqd');
            $minus = $adjs->whereIn('type', ['deduction', 'penalty', 'advance_repayment'])->sum('amount_iqd');

            // D. Commissions
            $commSummary = AgentCommissionSummary::where('staff_id', $staff->id)
                ->where('period_month', $periodMonth)
                ->first();

            $commission = $commSummary ? ($commSummary->commission_iqd + $commSummary->targets_bonus_iqd) : 0;

            // E. Net
            $net = $baseSalary - $attendanceDed + $plus - $minus + $commission;

            // Create Line
            PayrollRunLine::create([
                'payroll_run_id' => $run->id,
                'staff_id' => $staff->id,
                'base_salary_iqd' => $baseSalary,
                'attendance_deduction_iqd' => $attendanceDed,
                'adjustments_plus_iqd' => $plus,
                'adjustments_minus_iqd' => $minus,
                'commissions_iqd' => $commission,
                'net_salary_iqd' => $net
            ]);
        }

        $run->status = 'calculated';
        $run->save();

        return $run;
    }
}
