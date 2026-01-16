<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentativeTargetItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'target_item_id';

    protected $fillable = [
        'target_id',
        'item_type',
        'item_id',
        'target_quantity',
        'bonus_per_unit',
        'achieved_quantity',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2',
        'bonus_per_unit' => 'decimal:2',
        'achieved_quantity' => 'decimal:2',
    ];

    // Relationships
    public function target()
    {
        return $this->belongsTo(RepresentativeTarget::class, 'target_id');
    }

    // Methods
    public function calculateAchievedQuantity($repId, $targetMonth)
    {
        $query = ProductSale::where('representative_id', $repId)
            ->whereYear('sale_date', substr($targetMonth, 0, 4))
            ->whereMonth('sale_date', substr($targetMonth, 5, 2));

        if ($this->item_type === 'category') {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->item_id);
            });
        } elseif ($this->item_type === 'supplier') {
            $query->whereHas('product', function ($q) {
                $q->where('supplier_id', $this->item_id);
            });
        } elseif ($this->item_type === 'product') {
            $query->where('product_id', $this->item_id);
        }

        return $query->sum('quantity');
    }
}
