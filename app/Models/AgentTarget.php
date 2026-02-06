<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'period_month',
        'target_type',
        'target_qty',
        'reward_per_unit_iqd',
        'min_achievement_percent',
        'is_active',
    ];

    public function items()
    {
        return $this->hasMany(AgentTargetItem::class);
    }

    public function results() // Usually one per calculation
    {
        return $this->hasMany(AgentTargetResult::class);
    }
}
