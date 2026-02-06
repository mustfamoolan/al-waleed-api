<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'currency',
        'exchange_rate',
        'account_id',
        'opening_balance',
        'is_active',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
