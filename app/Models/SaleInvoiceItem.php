<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_price',
        'purchase_price_at_sale',
        'discount_percentage',
        'tax_percentage',
        'total_price',
        'profit_amount',
        'profit_percentage',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'purchase_price_at_sale' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'total_price' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(SaleInvoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Methods
    public function calculateProfit()
    {
        $profitAmount = ($this->unit_price - $this->purchase_price_at_sale) * $this->quantity;
        $profitPercentage = 0;
        
        if ($this->purchase_price_at_sale > 0) {
            $profitPercentage = (($this->unit_price - $this->purchase_price_at_sale) / $this->purchase_price_at_sale) * 100;
        }

        $this->profit_amount = $profitAmount;
        $this->profit_percentage = $profitPercentage;
        $this->save();

        return [
            'profit_amount' => $this->profit_amount,
            'profit_percentage' => $this->profit_percentage,
        ];
    }
}
