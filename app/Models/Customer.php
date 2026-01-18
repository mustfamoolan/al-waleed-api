<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'customer_name',
        'phone_number',
        'address',
        'location_lat',
        'location_lng',
        'total_debt',
        'total_paid',
        'last_payment_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'total_debt' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'last_payment_date' => 'date',
    ];

    // Relationships
    public function representatives()
    {
        return $this->belongsToMany(Representative::class, 'customer_representatives', 'customer_id', 'representative_id')
            ->withPivot('assigned_at', 'assigned_by', 'notes')
            ->withTimestamps();
    }

    public function saleInvoices()
    {
        return $this->hasMany(SaleInvoice::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id');
    }

    public function balance()
    {
        return $this->hasOne(CustomerBalance::class, 'customer_id');
    }

    public function transactions()
    {
        return $this->hasMany(CustomerBalanceTransaction::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function calculateTotalDebt()
    {
        $this->total_debt = $this->saleInvoices()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('remaining_amount');
        $this->save();
        return $this->total_debt;
    }

    public function updateBalance()
    {
        $this->calculateTotalDebt();
        
        $balance = $this->balance;
        if (!$balance) {
            $balance = CustomerBalance::create([
                'customer_id' => $this->customer_id,
            ]);
        }
        
        $balance->current_balance = $this->total_debt;
        $balance->total_debt = $this->total_debt;
        $balance->total_paid = $this->total_paid;
        $balance->save();
        
        return $balance;
    }
}
