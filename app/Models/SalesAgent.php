<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'salary',
        'commission_rate',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
