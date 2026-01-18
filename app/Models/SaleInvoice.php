<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SaleInvoice extends Model
{
    use HasFactory;

    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'representative_id',
        'buyer_type',
        'buyer_id',
        'buyer_name',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'special_discount_percentage',
        'special_discount_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_method',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'special_discount_percentage' => 'decimal:2',
        'special_discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // Relationships
    public function representative()
    {
        return $this->belongsTo(Representative::class, 'representative_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function buyerEmployee()
    {
        return $this->belongsTo(Employee::class, 'buyer_id')
            ->where('buyer_type', 'employee');
    }

    public function buyerRepresentative()
    {
        return $this->belongsTo(Representative::class, 'buyer_id')
            ->where('buyer_type', 'representative');
    }

    public function items()
    {
        return $this->hasMany(SaleInvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function getBuyerName()
    {
        switch ($this->buyer_type) {
            case 'customer':
                return $this->customer ? $this->customer->customer_name : 'Unknown Customer';
            case 'walk_in':
                return $this->buyer_name ?? 'Walk-in Customer';
            case 'employee':
                $employee = Employee::find($this->buyer_id);
                return $employee ? $employee->full_name : 'Unknown Employee';
            case 'representative':
                $rep = Representative::find($this->buyer_id);
                return $rep ? $rep->full_name : 'Unknown Representative';
            default:
                return 'Unknown';
        }
    }

    public function calculateRemaining()
    {
        $this->remaining_amount = $this->total_amount - $this->paid_amount;
        $this->save();
        return $this->remaining_amount;
    }

    public function updateStatus()
    {
        if ($this->status === 'draft' || $this->status === 'cancelled') {
            return;
        }

        if ($this->remaining_amount <= 0 && $this->paid_amount > 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0 && $this->paid_amount < $this->total_amount) {
            $this->status = 'partial';
        } elseif ($this->due_date && $this->due_date < now() && $this->remaining_amount > 0) {
            $this->status = 'overdue';
        } elseif ($this->status !== 'paid' && $this->remaining_amount > 0) {
            $this->status = 'pending';
        }
        
        $this->save();
    }

    public function isOverdue()
    {
        return $this->due_date && 
               $this->due_date < now() && 
               $this->remaining_amount > 0 &&
               $this->buyer_type === 'customer';
    }

    public function calculateSpecialDiscount()
    {
        if (in_array($this->buyer_type, ['employee', 'representative'])) {
            // يمكن إضافة منطق لتحديد نسبة الخصم من جدول الموظف/المندوب
            // أو استخدام القيمة المرسلة في الطلب
            $percentage = $this->special_discount_percentage ?? 0;
            $this->special_discount_amount = $this->subtotal * ($percentage / 100);
            $this->save();
            return $this->special_discount_amount;
        }
        return 0;
    }

    // Scopes
    public function scopeByRepresentative(Builder $query, $representativeId)
    {
        return $query->where('representative_id', $representativeId);
    }

    public function scopeByBuyerType(Builder $query, $buyerType)
    {
        return $query->where('buyer_type', $buyerType);
    }

    public function scopeFromOffice(Builder $query)
    {
        return $query->whereNull('representative_id');
    }
}
