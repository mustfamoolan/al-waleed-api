<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'tax_number',
        'address',
        'opening_balance',
        'current_balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'supplier_id');
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class, 'supplier_id');
    }

    // Methods
    /**
     * Calculate current balance from transactions
     */
    public function calculateCurrentBalance()
    {
        $openingBalance = $this->opening_balance ?? 0;
        
        $totalCredit = $this->transactions()->sum('credit');
        $totalDebit = $this->transactions()->sum('debit');
        
        return $openingBalance + $totalCredit - $totalDebit;
    }

    /**
     * Update current balance
     */
    public function updateBalance()
    {
        $this->current_balance = $this->calculateCurrentBalance();
        $this->save();
    }

    /**
     * Get total purchases (sum of all purchase invoices)
     */
    public function totalPurchases()
    {
        return $this->purchaseInvoices()
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get total returns
     */
    public function totalReturns()
    {
        return $this->purchaseReturns()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get total payments
     */
    public function totalPayments()
    {
        return $this->transactions()
            ->where('transaction_type', 'payment_out')
            ->sum('debit');
    }
}
