<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $table = 'purchase_returns';

    protected $primaryKey = 'id';

    protected $fillable = [
        'reference_invoice_id',
        'supplier_id',
        'return_number',
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
    public function referenceInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'reference_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'purchase_return_id');
    }

    public function items()
    {
        return $this->details(); // Alias for backward compatibility
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class, 'reference_id')
            ->where('transaction_type', 'purchase_return');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Manager::class, 'created_by');
    }
}
