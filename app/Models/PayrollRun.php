<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_month',
        'status',
        'created_by',
        'approved_by',
        'journal_entry_id',
    ];

    public function lines()
    {
        return $this->hasMany(PayrollRunLine::class);
    }
}
