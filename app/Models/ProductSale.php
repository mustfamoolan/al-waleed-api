<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    use HasFactory;

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'product_id',
        'sale_invoice_id',
        'sale_date',
        'quantity',
        'unit_price',
        'total_price',
        'purchase_price_at_sale',
        'profit_amount',
        'profit_percentage',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchase_price_at_sale' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function calculateProfit()
    {
        $profit = ($this->unit_price - $this->purchase_price_at_sale) * $this->quantity;
        $percentage = 0;
        
        if ($this->purchase_price_at_sale > 0) {
            $percentage = (($this->unit_price - $this->purchase_price_at_sale) / $this->purchase_price_at_sale) * 100;
        }

        return [
            'profit_amount' => $profit,
            'profit_percentage' => $percentage,
        ];
    }
}
