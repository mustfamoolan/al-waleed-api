<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_item_id';

    protected $fillable = [
        'return_id',
        'sale_invoice_item_id',
        'product_id',
        'quantity_returned',
        'unit_type',
        'carton_count',
        'unit_price',
        'total_return_price',
        'reason',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'carton_count' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_return_price' => 'decimal:2',
    ];

    // Relationships
    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'return_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(SaleInvoiceItem::class, 'sale_invoice_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Calculate quantity returned in pieces (for stock calculations)
     */
    public function getQuantityInPieces(): float
    {
        if ($this->unit_type === 'carton') {
            $product = $this->product ?? Product::find($this->product_id);
            if ($product && $product->pieces_per_carton) {
                // Use carton_count if available, otherwise use quantity_returned
                $cartonCount = $this->carton_count ?? $this->quantity_returned;
                return $cartonCount * $product->pieces_per_carton;
            }
            // Fallback: if pieces_per_carton is null, return quantity_returned as is (assume it's already in pieces)
            return $this->quantity_returned;
        }
        // If unit_type is 'piece', return quantity_returned directly
        return $this->quantity_returned;
    }
}
