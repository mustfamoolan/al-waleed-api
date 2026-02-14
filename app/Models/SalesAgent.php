<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'salary',
        'commission_rate',
        'account_id',
        'user_id',
        'is_active',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'is_active' => 'boolean',
        'salary' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function targets()
    {
        return $this->hasMany(AgentTarget::class, 'staff_id');
    }

    public function sales()
    {
        return $this->hasMany(SalesInvoice::class, 'agent_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'agent_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'agent_id');
    }
}
