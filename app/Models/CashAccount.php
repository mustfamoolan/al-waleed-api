<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'account_id',
        'currency',
        'is_active',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
