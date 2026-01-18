<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleReturn extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'sale_invoice_id',
        'customer_id',
        'representative_id',
        'created_by',
        'created_by_type',
        'returned_by',
        'return_type',
        'return_date',
        'return_reason',
        'total_return_amount',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_return_amount' => 'decimal:2',
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

    public function representative()
    {
        return $this->belongsTo(Representative::class, 'representative_id');
    }

    public function driver()
    {
        return $this->belongsTo(Picker::class, 'returned_by');
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class, 'return_id');
    }

    public function approver()
    {
        return $this->belongsTo(Manager::class, 'approved_by');
    }

    // Methods
    public function calculateTotalReturnAmount(): float
    {
        $total = $this->items()->sum('total_return_price');
        $this->total_return_amount = $total;
        $this->save();
        return $total;
    }

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

            // Update inventory and customer balance
            $this->complete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale return approval error: ' . $e->getMessage());
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
            $existingNotes = $this->notes ?? '';
            $this->notes = ($existingNotes ? $existingNotes . "\n" : '') . "Rejection reason: " . $reason;
        }
        $this->save();

        return true;
    }

    public function complete(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        try {
            DB::beginTransaction();

            // Update inventory for each returned item (convert to pieces)
            foreach ($this->items as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    // Increase stock (returning products back) - convert to pieces
                    $quantityInPieces = $item->getQuantityInPieces();
                    $product->updateStock($quantityInPieces, 'sale_return');
                }
            }

            // Update customer balance (decrease debt)
            $customer = $this->customer;
            if ($customer) {
                $balance = CustomerBalance::getOrCreate($customer->customer_id);
                $balance->recordTransaction(
                    'refund',
                    -$this->total_return_amount, // Negative to decrease debt
                    "إرجاع فاتورة: " . ($this->invoice->invoice_number ?? 'غير محدد'),
                    'sale_return',
                    $this->return_id,
                    $this->approved_by
                );

                // Update customer totals
                $customer->total_debt = max(0, $customer->total_debt - $this->total_return_amount);
                $customer->save();
            }

            // Update invoice if full return
            if ($this->return_type === 'full') {
                $invoice = $this->invoice;
                if ($invoice) {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }
            }

            $this->status = 'completed';
            $this->save();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale return completion error: ' . $e->getMessage());
            return false;
        }
    }
}
