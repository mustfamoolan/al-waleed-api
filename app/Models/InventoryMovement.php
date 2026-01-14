<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'product_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_price',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
        'unit_price' => 'decimal:2',
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
    public function getMovementTypeLabel()
    {
        return match($this->movement_type) {
            'purchase' => 'شراء',
            'return' => 'مرتجع',
            'sale' => 'بيع',
            'adjustment' => 'تعديل',
            'transfer' => 'نقل',
            default => $this->movement_type,
        };
    }
}
