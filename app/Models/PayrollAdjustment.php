<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'period_month',
        'type', // allowance, deduction, penalty, advance_repayment, bonus_manual
        'amount_iqd',
        'reason',
        'created_by',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
