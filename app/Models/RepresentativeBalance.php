<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentativeBalance extends Model
{
    use HasFactory;

    protected $primaryKey = 'balance_id';

    protected $fillable = [
        'rep_id',
        'current_balance',
        'total_earned',
        'total_withdrawn',
        'last_transaction_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'last_transaction_at' => 'datetime',
    ];

    // Relationships
    public function representative()
    {
        return $this->belongsTo(Representative::class, 'rep_id');
    }

    public function transactions()
    {
        return $this->hasMany(RepresentativeBalanceTransaction::class, 'rep_id', 'rep_id');
    }

    // Methods
    public function addTransaction($type, $amount, $description = null, $relatedType = null, $relatedId = null, $createdBy = null)
    {
        $balanceBefore = $this->current_balance;
        $balanceAfter = $balanceBefore + $amount;

        // Update balance
        $this->current_balance = $balanceAfter;
        
        if ($amount > 0) {
            $this->total_earned += $amount;
        } else {
            $this->total_withdrawn += abs($amount);
        }
        
        $this->last_transaction_at = now();
        $this->save();

        // Create transaction record
        return RepresentativeBalanceTransaction::create([
            'rep_id' => $this->rep_id,
            'transaction_type' => $type,
            'amount' => $amount,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'description' => $description,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'created_by' => $createdBy,
        ]);
    }

    public static function getOrCreate($repId)
    {
        return static::firstOrCreate(
            ['rep_id' => $repId],
            [
                'current_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]
        );
    }
}
