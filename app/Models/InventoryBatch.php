<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'cost_price',
        'quantity_initial',
        'quantity_current',
        'purchase_invoice_detail_id',
        'status',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
        'cost_price' => 'decimal:2',
        'quantity_initial' => 'decimal:3',
        'quantity_current' => 'decimal:3',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function purchaseInvoiceDetail()
    {
        return $this->belongsTo(PurchaseInvoiceDetail::class, 'purchase_invoice_detail_id');
    }

    // Alias
    public function invoiceDetail()
    {
        return $this->purchaseInvoiceDetail();
    }

    public function purchaseReturnDetails()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'batch_id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'batch_id');
    }

    // Methods
    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        return $this->expiry_date < Carbon::today() && $this->quantity_current > 0;
    }

    /**
     * Check if batch is expiring soon (within specified days)
     */
    public function isExpiringSoon($days = 7)
    {
        return $this->expiry_date <= Carbon::today()->addDays($days) 
            && $this->expiry_date >= Carbon::today()
            && $this->quantity_current > 0;
    }

    /**
     * Deduct quantity from batch
     */
    public function deductQuantity($quantity)
    {
        if ($quantity > $this->quantity_current) {
            throw new \Exception('Insufficient quantity in batch');
        }

        $this->quantity_current -= $quantity;
        
        if ($this->quantity_current <= 0) {
            $this->status = 'consumed';
        }

        $this->save();
    }

    /**
     * Add quantity to batch
     */
    public function addQuantity($quantity)
    {
        $this->quantity_current += $quantity;
        $this->status = 'active';
        $this->save();
    }
}
