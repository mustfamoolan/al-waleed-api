<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_no',
        'supplier_id',
        'purchase_invoice_id',
        'return_date',
        'currency',
        'exchange_rate',
        'total_foreign',
        'total_iqd',
        'status',
        'created_by',
        'approved_by',
        'journal_entry_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseReturnLine::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
