<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'sales_type',
        'credit_limit',
        'account_id',
        'agent_id',
        'is_active',
        'total_debt',
        'total_paid',
        'last_payment_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function agent()
    {
        return $this->belongsTo(SalesAgent::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
