<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'inventory_movement_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_type',
        'carton_count',
        'unit_price',
        'discount_percentage',
        'tax_percentage',
        'total_price',
        'cost_after_purchase',
        'transport_cost_share',
        'retail_price',
        'wholesale_price',
        'category_name',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'carton_count' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'cost_after_purchase' => 'decimal:2',
        'transport_cost_share' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    public function returnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'original_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inventoryMovement()
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    /**
     * Calculate quantity in pieces (for stock calculations)
     */
    public function getQuantityInPieces(): float
    {
        if ($this->unit_type === 'carton') {
            $product = $this->product ?? Product::find($this->product_id);
            if ($product && $product->pieces_per_carton) {
                // Use carton_count if available, otherwise use quantity
                $cartonCount = $this->carton_count ?? $this->quantity;
                return $cartonCount * $product->pieces_per_carton;
            }
            // Fallback: if pieces_per_carton is null, return quantity as is (assume it's already in pieces)
            return $this->quantity;
        }
        // If unit_type is 'piece', return quantity directly
        return $this->quantity;
    }
}
