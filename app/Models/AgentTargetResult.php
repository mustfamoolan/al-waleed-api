<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTargetResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_target_id',
        'achieved_qty',
        'achievement_percent',
        'bonus_iqd',
        'calculated_at',
    ];
}
