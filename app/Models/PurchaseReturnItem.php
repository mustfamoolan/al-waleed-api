<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_item_id';

    protected $fillable = [
        'return_invoice_id',
        'original_item_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_price',
        'total_price',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function returnInvoice()
    {
        return $this->belongsTo(PurchaseReturnInvoice::class, 'return_invoice_id');
    }

    public function originalItem()
    {
        return $this->belongsTo(PurchaseInvoiceItem::class, 'original_item_id');
    }
}
