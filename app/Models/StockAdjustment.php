<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'adjustment_date',
        'type',
        'reason',
        'product_id',
        'batch_id',
        'quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'quantity' => 'decimal:3',
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Manager::class, 'created_by');
    }
}
