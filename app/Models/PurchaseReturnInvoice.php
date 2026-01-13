<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnInvoice extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_invoice_id';

    protected $fillable = [
        'original_invoice_id',
        'supplier_id',
        'return_invoice_number',
        'return_date',
        'total_amount',
        'reason',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function originalInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'original_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }
}
