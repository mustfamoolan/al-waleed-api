<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Representative extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'rep_id';

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
    public function salaries()
    {
        return $this->hasMany(RepresentativeSalary::class, 'rep_id');
    }

    public function targets()
    {
        return $this->hasMany(RepresentativeTarget::class, 'rep_id');
    }

    public function balance()
    {
        return $this->hasOne(RepresentativeBalance::class, 'rep_id');
    }

    public function transactions()
    {
        return $this->hasMany(RepresentativeBalanceTransaction::class, 'rep_id');
    }

    public function sales()
    {
        return $this->hasMany(ProductSale::class, 'representative_id');
    }
}
