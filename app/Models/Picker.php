<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Picker extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'picker_id';

    protected $fillable = [
        'full_name',
        'phone_number',
        'password_hash',
        'profile_image',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationships
    public function assignedInvoices()
    {
        return $this->hasMany(SaleInvoice::class, 'assigned_to_driver');
    }

    public function deliveredInvoices()
    {
        return $this->hasMany(SaleInvoice::class, 'delivered_by');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'returned_by')
            ->where('created_by_type', 'driver');
    }

    public function payments()
    {
        return $this->hasMany(DriverPayment::class, 'driver_id');
    }
}
