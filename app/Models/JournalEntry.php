<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $primaryKey = 'entry_id';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'description',
        'reference_type',
        'reference_id',
        'total_debit',
        'total_credit',
        'is_posted',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    // Relationships
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'entry_id');
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'entry_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }
}
