<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerBalance extends Model
{
    use HasFactory;

    protected $primaryKey = 'balance_id';

    protected $fillable = [
        'customer_id',
        'current_balance',
        'total_debt',
        'total_paid',
        'last_transaction_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'total_debt' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'last_transaction_at' => 'datetime',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function transactions()
    {
        return $this->hasMany(CustomerBalanceTransaction::class, 'customer_id', 'customer_id');
    }

    // Methods
    public function recordTransaction(
        string $type,
        float $amount,
        string $description = null,
        string $relatedType = null,
        int $relatedId = null,
        int $createdBy = null
    ): CustomerBalanceTransaction {
        return DB::transaction(function () use ($type, $amount, $description, $relatedType, $relatedId, $createdBy) {
            $balanceBefore = $this->current_balance;
            $this->current_balance += $amount;

            if ($amount > 0) {
                $this->total_debt += $amount;
            } elseif ($amount < 0) {
                $this->total_paid += abs($amount);
            }

            $this->last_transaction_at = now();
            $this->save();

            return $this->transactions()->create([
                'transaction_type' => $type,
                'amount' => $amount,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'description' => $description,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->current_balance,
                'created_by' => $createdBy,
            ]);
        });
    }

    public static function getOrCreate(int $customerId): self
    {
        $balance = self::where('customer_id', $customerId)->first();
        
        if (!$balance) {
            $balance = self::create([
                'customer_id' => $customerId,
                'current_balance' => 0,
                'total_debt' => 0,
                'total_paid' => 0,
            ]);
        }
        
        return $balance;
    }
}
