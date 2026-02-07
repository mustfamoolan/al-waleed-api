<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_number',
        'currency',
        'exchange_rate',
        'account_id',
        'opening_balance',
        'notes',
        'profile_image',
        'is_active',
    ];

    public function getCurrentBalanceAttribute()
    {
        try {
            return $this->account ? ($this->account->current_balance ?? 0) : ($this->opening_balance ?? 0);
        } catch (\Exception $e) {
            return $this->opening_balance ?? 0;
        }
    }

    protected $casts = [
        'is_active' => 'boolean',
        'exchange_rate' => 'decimal:6',
        'opening_balance' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, ProductSupplier::class, 'supplier_id', 'id', 'id', 'product_id');
    }
}
