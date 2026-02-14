<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'qty',
        'unit_id',
        'unit_factor',
        'price_iqd',
        'line_total_iqd',
        'cost_iqd_snapshot',
        'notes',
    ];

    protected $appends = ['product_name', 'unit_name'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Accessors to include product_name and unit_name in JSON
    public function getProductNameAttribute()
    {
        return $this->product?->name_ar ?? '';
    }

    public function getUnitNameAttribute()
    {
        return $this->unit?->name_ar ?? '';
    }
}
