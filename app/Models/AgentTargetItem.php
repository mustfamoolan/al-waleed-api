<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTargetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_target_id',
        'product_id',
        'supplier_id',
        'category_id',
    ];
}
