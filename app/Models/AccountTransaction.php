<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'entry_id',
        'transaction_date',
        'debit_amount',
        'credit_amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'entry_id');
    }
}
