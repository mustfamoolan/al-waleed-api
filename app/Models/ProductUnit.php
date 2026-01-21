<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_factor',
        'is_base_unit',
        'purchase_price',
        'sale_price',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'is_base_unit' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchaseInvoiceDetails()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'unit_id');
    }

    // Methods
    /**
     * Convert quantity from this unit to base unit
     */
    public function convertToBaseUnit($quantity)
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit to this unit
     */
    public function convertFromBaseUnit($quantity)
    {
        if ($this->conversion_factor == 0) {
            return 0;
        }
        return $quantity / $this->conversion_factor;
    }
}
