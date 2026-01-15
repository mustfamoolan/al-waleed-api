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
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'driver_cost',
        'worker_cost',
        'status',
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
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'driver_cost' => 'decimal:2',
        'worker_cost' => 'decimal:2',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'invoice_id');
    }

    public function returnInvoices()
    {
        return $this->hasMany(PurchaseReturnInvoice::class, 'original_invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function calculateRemaining()
    {
        $this->remaining_amount = $this->total_amount - $this->paid_amount;
        $this->save();
        return $this->remaining_amount;
    }

    public function updateStatus()
    {
        if ($this->remaining_amount <= 0 && $this->paid_amount > 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0 && $this->paid_amount < $this->total_amount) {
            $this->status = 'partial';
        } elseif ($this->status == 'draft') {
            // Keep draft status
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    /**
     * Get total transport cost (driver + worker)
     */
    public function getTotalTransportCost()
    {
        return ($this->driver_cost ?? 0) + ($this->worker_cost ?? 0);
    }

    /**
     * Get total number of cartons in all items
     */
    public function getTotalCartons()
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Calculate cost per carton for transport cost distribution
     */
    public function getCostPerCarton()
    {
        $totalCartons = $this->getTotalCartons();
        if ($totalCartons <= 0) {
            return 0;
        }
        return $this->getTotalTransportCost() / $totalCartons;
    }
}
