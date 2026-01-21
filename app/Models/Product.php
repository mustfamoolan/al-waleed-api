<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'name_ar',
        'name_en',
        'sku',
        'barcode',
        'category_id',
        'description',
        'image_path',
        'min_stock_alert',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class, 'product_id');
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'product_id');
    }

    public function purchaseInvoiceDetails()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'product_id');
    }

    public function purchaseReturnDetails()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'product_id');
    }

    // Methods
    /**
     * Get base unit for this product
     */
    public function getBaseUnit()
    {
        return $this->productUnits()->where('is_base_unit', true)->first();
    }

    /**
     * Get current stock quantity (sum of all active batches)
     */
    public function getCurrentStock($warehouseId = null)
    {
        $query = $this->inventoryBatches()
            ->where('status', 'active')
            ->where('quantity_current', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity_current');
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock($warehouseId = null)
    {
        $currentStock = $this->getCurrentStock($warehouseId);
        return $currentStock <= $this->min_stock_alert;
    }

    /**
     * Get batches sorted by expiry date (FEFO - First Expired First Out)
     */
    public function getBatchesByFEFO($warehouseId = null, $quantity = null)
    {
        $query = $this->inventoryBatches()
            ->where('status', 'active')
            ->where('quantity_current', '>', 0)
            ->orderBy('expiry_date', 'asc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($quantity) {
            $query->where('quantity_current', '>=', $quantity);
        }

        return $query->get();
    }

    /**
     * Get batches sorted by creation date (FIFO - First In First Out)
     */
    public function getBatchesByFIFO($warehouseId = null, $quantity = null)
    {
        $query = $this->inventoryBatches()
            ->where('status', 'active')
            ->where('quantity_current', '>', 0)
            ->orderBy('created_at', 'asc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($quantity) {
            $query->where('quantity_current', '>=', $quantity);
        }

        return $query->get();
    }
}
