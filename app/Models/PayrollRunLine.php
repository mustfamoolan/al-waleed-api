<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRunLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'base_salary_iqd',
        'attendance_deduction_iqd',
        'adjustments_plus_iqd',
        'adjustments_minus_iqd',
        'commissions_iqd',
        'net_salary_iqd',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
