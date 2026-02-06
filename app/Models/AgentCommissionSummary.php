<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'period_month',
        'total_sales_iqd',
        'commission_iqd',
        'targets_bonus_iqd',
        'total_due_iqd',
        'status',
    ];
}
