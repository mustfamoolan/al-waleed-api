<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_item_id';

    protected $fillable = [
        'return_invoice_id',
        'original_item_id',
        'product_id',
        'inventory_movement_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_type',
        'carton_count',
        'unit_price',
        'total_price',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'carton_count' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function returnInvoice()
    {
        return $this->belongsTo(PurchaseReturnInvoice::class, 'return_invoice_id');
    }

    public function originalItem()
    {
        return $this->belongsTo(PurchaseInvoiceItem::class, 'original_item_id');
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
