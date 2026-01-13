<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'company_name',
        'contact_person_name',
        'phone_number',
        'email',
        'address',
        'profile_image',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function purchaseReturnInvoices()
    {
        return $this->hasMany(PurchaseReturnInvoice::class, 'supplier_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id');
    }

    // Methods
    public function currentBalance()
    {
        $totalInvoices = $this->purchaseInvoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        
        $totalReturns = $this->purchaseReturnInvoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        
        $totalPayments = $this->payments()
            ->where('payment_type', 'payment')
            ->sum('amount');
        
        $totalRefunds = $this->payments()
            ->where('payment_type', 'refund')
            ->sum('amount');

        return ($totalInvoices - $totalReturns) - ($totalPayments - $totalRefunds);
    }

    public function totalPurchases()
    {
        return $this->purchaseInvoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    public function totalPayments()
    {
        return $this->payments()
            ->where('payment_type', 'payment')
            ->sum('amount');
    }

    public function totalReturns()
    {
        return $this->purchaseReturnInvoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }
}
