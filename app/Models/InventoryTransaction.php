<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'trans_date',
        'trans_type',
        'warehouse_id',
        'reference_type',
        'reference_id',
        'created_by',
        'note',
    ];

    public function lines()
    {
        return $this->hasMany(InventoryTransactionLine::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
