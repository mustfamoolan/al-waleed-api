<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'name',
        'type',
        'parent_id',
        'is_postable',
        'current_balance',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'current_balance' => 'decimal:2',
    ];

    // Parent Account
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    // Child Accounts
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    // Children Recursive
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope for Postable Accounts
    public function scopePostable($query)
    {
        return $query->where('is_postable', true);
    }

    // Scope for Root Accounts
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
