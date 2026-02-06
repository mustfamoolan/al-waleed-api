<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transaction_id',
        'product_id',
        'qty',
        'unit_id',
        'unit_factor',
        'cost_iqd',
        'sale_price_iqd',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
