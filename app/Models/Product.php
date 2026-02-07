<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'image_path',
        'category_id',
        'purchase_price',
        'sale_price_retail',
        'sale_price_wholesale',
        'base_unit_id',
        'has_pack',
        'pack_unit_id',
        'units_per_pack',
        'piece_weight',
        'weight_unit',
        'carton_weight',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_pack' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price_retail' => 'decimal:2',
        'sale_price_wholesale' => 'decimal:2',
        'piece_weight' => 'decimal:3',
        'carton_weight' => 'decimal:3',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function packUnit()
    {
        return $this->belongsTo(Unit::class, 'pack_unit_id');
    }

    public function suppliers()
    {
        return $this->hasMany(ProductSupplier::class);
    }

    public function defaultSupplier()
    {
        return $this->hasOne(ProductSupplier::class)->where('is_default', true)->with('supplier');
    }

    public function balances()
    {
        return $this->hasMany(InventoryBalance::class);
    }
}
