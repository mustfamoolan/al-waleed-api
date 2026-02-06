<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'supplier_invoice_no',
        'supplier_id',
        'invoice_date',
        'currency',
        'exchange_rate',
        'subtotal_foreign',
        'discount_foreign',
        'total_foreign',
        'total_iqd',
        'paid_iqd',
        'remaining_iqd',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'journal_entry_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseInvoiceLine::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
