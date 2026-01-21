<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function details()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'invoice_id');
    }

    public function items()
    {
        return $this->details(); // Alias for backward compatibility
    }

    // Alias for old code compatibility
    public function getItemsAttribute()
    {
        return $this->details;
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'reference_invoice_id');
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class, 'reference_id')
            ->where('transaction_type', 'purchase_invoice');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Manager::class, 'created_by');
    }

    // Methods
    /**
     * Update payment status based on transactions
     */
    public function updatePaymentStatus()
    {
        $totalPaid = $this->transactions()
            ->where('transaction_type', 'payment_out')
            ->sum('debit');

        if ($totalPaid >= $this->total_amount) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->save();
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmount()
    {
        $totalPaid = $this->transactions()
            ->where('transaction_type', 'payment_out')
            ->sum('debit');

        return max(0, $this->total_amount - $totalPaid);
    }
}
