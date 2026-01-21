<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_invoice_details';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'total_row',
        'expiry_date',
        'batch_number',
        'product_name',
        'product_code',
        'discount_percentage',
        'tax_percentage',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_row' => 'decimal:2',
        'expiry_date' => 'date',
        'discount_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'purchase_invoice_detail_id');
    }

    public function inventoryBatch()
    {
        return $this->hasOne(InventoryBatch::class, 'purchase_invoice_detail_id');
    }

    public function purchaseReturnDetails()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'original_item_id');
    }

    // Methods
    /**
     * Calculate quantity in base unit
     */
    public function getQuantityInBaseUnit()
    {
        if ($this->unit && $this->unit->conversion_factor) {
            return $this->quantity * $this->unit->conversion_factor;
        }
        return $this->quantity;
    }

    /**
     * Calculate cost per base unit
     */
    public function getCostPerBaseUnit()
    {
        $baseQuantity = $this->getQuantityInBaseUnit();
        if ($baseQuantity > 0) {
            return $this->total_row / $baseQuantity;
        }
        return 0;
    }
}
