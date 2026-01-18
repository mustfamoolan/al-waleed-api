<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverPayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'sale_invoice_id',
        'customer_id',
        'driver_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(Picker::class, 'driver_id');
    }

    public function approver()
    {
        return $this->belongsTo(Manager::class, 'approved_by');
    }

    // Methods
    public function approve($managerId): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        try {
            DB::beginTransaction();

            $this->status = 'approved';
            $this->approved_by = $managerId;
            $this->approved_at = now();
            $this->save();

            // Update customer balance
            $customer = $this->customer;
            if ($customer) {
                $balance = CustomerBalance::getOrCreate($customer->customer_id);
                $balance->recordTransaction(
                    'payment',
                    -$this->amount, // Negative for payment
                    "دفعة من السائق: " . ($this->driver->full_name ?? 'غير محدد') . " على فاتورة: " . ($this->invoice->invoice_number ?? 'غير محددة'),
                    'driver_payment',
                    $this->payment_id,
                    $managerId
                );

                // Update customer total paid
                $customer->total_paid += $this->amount;
                $customer->last_payment_date = $this->payment_date;
                $customer->save();
            }

            // Update invoice if linked
            if ($this->invoice) {
                $invoice = $this->invoice;
                $invoice->paid_amount += $this->amount;
                $invoice->calculateRemaining();
                $invoice->updateStatus();
            }

            // Create CustomerPayment record
            \App\Models\CustomerPayment::create([
                'customer_id' => $this->customer_id,
                'invoice_id' => $this->sale_invoice_id,
                'payment_date' => $this->payment_date,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'notes' => "تم تسجيلها من قبل السائق: " . (isset($this->notes) ? $this->notes : ''),
                'created_by' => $managerId,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Driver payment approval error: ' . $e->getMessage());
            return false;
        }
    }

    public function reject($managerId, $reason = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'rejected';
        $this->approved_by = $managerId;
        $this->approved_at = now();
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Rejection reason: " . $reason;
        }
        $this->save();

        return true;
    }
}
