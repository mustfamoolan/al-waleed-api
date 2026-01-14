<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_name',
        'sku',
        'product_image',
        'category_id',
        'supplier_id',
        'unit_type',
        'pieces_per_carton',
        'piece_weight',
        'carton_weight',
        'current_stock',
        'purchase_price',
        'wholesale_price',
        'retail_price',
        'last_purchase_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'piece_weight' => 'decimal:3',
        'carton_weight' => 'decimal:3',
        'current_stock' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'last_purchase_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'product_id');
    }

    public function sales()
    {
        return $this->hasMany(ProductSale::class, 'product_id');
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'product_id');
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'product_id');
    }

    // Methods
    public function calculateCartonWeight()
    {
        if ($this->unit_type === 'carton' && $this->pieces_per_carton && $this->piece_weight) {
            return $this->pieces_per_carton * $this->piece_weight;
        }
        return null;
    }

    public function updateStock($quantity, $movementType = 'adjustment')
    {
        $stockBefore = $this->current_stock;
        $this->current_stock += $quantity;

        if ($this->current_stock < 0) {
            $this->current_stock = (float) 0;
        }

        $this->save();

        return [
            'stock_before' => $stockBefore,
            'stock_after' => $this->current_stock,
        ];
    }

    public function isLowStock($threshold = 10)
    {
        return $this->current_stock <= $threshold;
    }

    public function getTotalProfit()
    {
        return $this->sales()->sum('profit_amount');
    }

    public function getAverageProfit()
    {
        $salesCount = $this->sales()->count();
        if ($salesCount === 0) {
            return 0;
        }
        return $this->getTotalProfit() / $salesCount;
    }

    // Auto-calculate carton weight on save
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            if ($product->unit_type === 'carton' && $product->pieces_per_carton && $product->piece_weight) {
                $product->carton_weight = $product->pieces_per_carton * $product->piece_weight;
            }
        });
    }
}
