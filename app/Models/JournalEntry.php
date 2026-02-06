<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'created_by',
        'approved_by',
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isBalanced()
    {
        $debits = $this->lines->sum('debit_amount');
        $credits = $this->lines->sum('credit_amount');
        return $debits == $credits;
    }
}
