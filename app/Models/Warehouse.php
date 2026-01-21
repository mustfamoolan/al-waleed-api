<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'manager_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'warehouse_id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'warehouse_id');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'warehouse_id');
    }
}
