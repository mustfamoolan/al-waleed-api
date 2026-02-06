<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'job_title',
        'salary',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
