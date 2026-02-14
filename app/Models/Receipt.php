<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_no',
        'party_id',
        'customer_id',
        'agent_id',
        'receipt_type',
        'amount_iqd',
        'status',
        'journal_entry_id',
        'created_by',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(SalesAgent::class);
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class);
    }
}
