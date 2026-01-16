<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentativeBalanceTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'rep_id',
        'transaction_type',
        'amount',
        'related_type',
        'related_id',
        'description',
        'balance_before',
        'balance_after',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relationships
    public function representative()
    {
        return $this->belongsTo(Representative::class, 'rep_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function getRelatedModel()
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }

        switch ($this->related_type) {
            case 'representative_salary':
                return RepresentativeSalary::find($this->related_id);
            case 'representative_target':
                return RepresentativeTarget::find($this->related_id);
            default:
                return null;
        }
    }
}
