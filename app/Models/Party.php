<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_type', // customer, employee, agent, driver, picker, walk_in
        'name',
        'phone',
        'customer_id',
        'staff_id',
        'agent_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function agent()
    {
        return $this->belongsTo(SalesAgent::class, 'agent_id');
    }
}
