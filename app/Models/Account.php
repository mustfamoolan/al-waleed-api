<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parentAccount()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function childAccounts()
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'account_id');
    }

    // Methods
    public function updateBalance()
    {
        $totalDebit = $this->transactions()->sum('debit_amount');
        $totalCredit = $this->transactions()->sum('credit_amount');
        
        $this->current_balance = $this->opening_balance + ($totalDebit - $totalCredit);
        $this->save();
    }
}
