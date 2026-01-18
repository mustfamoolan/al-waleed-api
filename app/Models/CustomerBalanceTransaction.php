<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBalanceTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'customer_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'related_type',
        'related_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }
}
