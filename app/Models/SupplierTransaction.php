<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'transaction_type',
        'reference_id',
        'debit',
        'credit',
        'balance_after',
        'transaction_date',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Methods
    /**
     * Get transaction type label in Arabic
     */
    public function getTransactionTypeLabel()
    {
        return match($this->transaction_type) {
            'purchase_invoice' => 'فاتورة شراء',
            'payment_out' => 'دفعة',
            'purchase_return' => 'مرتجع شراء',
            'opening_balance' => 'رصيد افتتاحي',
            default => $this->transaction_type,
        };
    }
}
