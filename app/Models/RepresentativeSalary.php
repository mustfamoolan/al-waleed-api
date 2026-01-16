<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentativeSalary extends Model
{
    use HasFactory;

    protected $primaryKey = 'salary_id';

    protected $fillable = [
        'rep_id',
        'month',
        'base_salary',
        'total_bonuses',
        'total_amount',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'total_bonuses' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function representative()
    {
        return $this->belongsTo(Representative::class, 'rep_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(Manager::class, 'paid_by');
    }

    // Methods
    public function calculateTotalAmount()
    {
        $this->total_amount = $this->base_salary + ($this->total_bonuses ?? 0);
        return $this->total_amount;
    }

    public function markAsPaid($managerId)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => $managerId,
        ]);
    }
}
