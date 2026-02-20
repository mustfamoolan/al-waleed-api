<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'source_type',
        'source_user_id',
        'party_id',
        'customer_id',
        'agent_id',
        'payment_type',
        'due_date',
        'delivery_required',
        'delivery_address_id',
        'delivery_address_text',
        'delivery_lat',
        'delivery_lng',
        'subtotal_iqd',
        'discount_iqd',
        'total_iqd',
        'paid_iqd',
        'remaining_iqd',
        'status',
        'approved_by_user_id',
        'prepared_by_staff_id',
        'driver_staff_id',
        'delivered_at',
        'journal_entry_id',
        'created_by',
        'notes',
        'customer_city',
        'customer_phone',
        'customer_address',
    ];

    public function lines()
    {
        return $this->hasMany(SalesInvoiceLine::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(SalesAgent::class);
    }

    public function driver()
    {
        return $this->belongsTo(Staff::class, 'driver_staff_id');
    }

    public function preparer()
    {
        return $this->belongsTo(Staff::class, 'prepared_by_staff_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
