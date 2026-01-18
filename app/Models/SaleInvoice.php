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
        'request_type',
        'request_status',
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
        'delivery_status',
        'prepared_by',
        'prepared_at',
        'assigned_to_driver',
        'assigned_at',
        'delivered_by',
        'delivered_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'prepared_at' => 'datetime',
        'assigned_at' => 'datetime',
        'delivered_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function preparer()
    {
        return $this->belongsTo(Employee::class, 'prepared_by');
    }

    public function driver()
    {
        return $this->belongsTo(Picker::class, 'assigned_to_driver');
    }

    public function deliverer()
    {
        return $this->belongsTo(Picker::class, 'delivered_by');
    }

    public function approver()
    {
        return $this->belongsTo(Manager::class, 'approved_by');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_invoice_id');
    }

    public function driverPayments()
    {
        return $this->hasMany(DriverPayment::class, 'sale_invoice_id');
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

    // Delivery status methods
    public function canChangeDeliveryStatus($newStatus): bool
    {
        return $this->isValidDeliveryStatusTransition($this->delivery_status ?? 'not_prepared', $newStatus);
    }

    public function changeDeliveryStatus($newStatus, $userId = null, $userType = null): bool
    {
        if (!$this->canChangeDeliveryStatus($newStatus)) {
            return false;
        }

        $this->delivery_status = $newStatus;

        // Set appropriate fields based on status
        switch ($newStatus) {
            case 'preparing':
                if ($userType === 'employee' && $userId) {
                    $this->prepared_by = $userId;
                }
                break;
            case 'prepared':
                if ($userType === 'employee' && $userId) {
                    $this->prepared_by = $userId;
                    $this->prepared_at = now();
                }
                break;
            case 'assigned_to_driver':
                // This should be set by assignToDriver method
                break;
            case 'delivered':
                if ($userType === 'driver' && $userId) {
                    $this->delivered_by = $userId;
                    $this->delivered_at = now();
                    // Update payment status if cash
                    if ($this->payment_method === 'cash') {
                        $this->status = 'paid';
                        $this->paid_amount = $this->total_amount;
                        $this->remaining_amount = 0;
                    }
                }
                break;
            case 'cancelled':
                // Can be cancelled from any status before delivered
                break;
        }

        $this->save();
        return true;
    }

    public function assignToDriver($driverId): bool
    {
        if ($this->delivery_status !== 'prepared') {
            return false;
        }

        $this->assigned_to_driver = $driverId;
        $this->assigned_at = now();
        $this->delivery_status = 'assigned_to_driver';
        $this->save();

        return true;
    }

    public function markAsDelivered($driverId): bool
    {
        if ($this->delivery_status !== 'in_delivery' || $this->assigned_to_driver != $driverId) {
            return false;
        }

        return $this->changeDeliveryStatus('delivered', $driverId, 'driver');
    }

    public function isAssignedToMe($driverId): bool
    {
        return $this->assigned_to_driver == $driverId;
    }

    public function canBeReturned(): bool
    {
        return $this->delivery_status === 'delivered';
    }

    private function isValidDeliveryStatusTransition($current, $new): bool
    {
        $validTransitions = [
            'not_prepared' => ['preparing', 'cancelled'],
            'preparing' => ['prepared', 'cancelled'],
            'prepared' => ['assigned_to_driver', 'cancelled'],
            'assigned_to_driver' => ['in_delivery', 'cancelled'],
            'in_delivery' => ['delivered', 'cancelled'],
            'delivered' => [], // لا يمكن تغييرها بعد التسليم
            'cancelled' => [], // لا يمكن تغييرها بعد الإلغاء
        ];

        return in_array($new, $validTransitions[$current] ?? []);
    }
}
