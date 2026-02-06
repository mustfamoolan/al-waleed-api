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
        'category_id',
        'purchase_price',
        'sale_price_retail',
        'sale_price_wholesale',
        'base_unit_id',
        'has_pack',
        'pack_unit_id',
        'units_per_pack',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
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

    public function balances()
    {
        return $this->hasMany(InventoryBalance::class);
    }
}
