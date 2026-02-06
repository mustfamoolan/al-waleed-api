<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'sales_type',
        'credit_limit',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
