<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentativeTarget extends Model
{
    use HasFactory;

    protected $primaryKey = 'target_id';

    protected $fillable = [
        'rep_id',
        'target_type',
        'target_month',
        'target_name',
        'category_id',
        'supplier_id',
        'product_id',
        'target_quantity',
        'bonus_per_unit',
        'completion_percentage_required',
        'status',
        'achieved_quantity',
        'achievement_percentage',
        'total_bonus_earned',
        'created_by',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2',
        'bonus_per_unit' => 'decimal:2',
        'completion_percentage_required' => 'decimal:2',
        'achieved_quantity' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'total_bonus_earned' => 'decimal:2',
    ];

    // Relationships
    public function representative()
    {
        return $this->belongsTo(Representative::class, 'rep_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items()
    {
        return $this->hasMany(RepresentativeTargetItem::class, 'target_id');
    }

    public function creator()
    {
        return $this->belongsTo(Manager::class, 'created_by');
    }

    // Methods
    public function calculateProgress()
    {
        $this->refresh();
        
        if ($this->target_type === 'mixed') {
            return $this->calculateMixedTargetProgress();
        }

        // Calculate for single target (category/supplier/product)
        $achieved = $this->calculateAchievedQuantity();
        $this->achieved_quantity = $achieved;
        
        if ($this->target_quantity > 0) {
            $this->achievement_percentage = ($achieved / $this->target_quantity) * 100;
        } else {
            $this->achievement_percentage = 0;
        }

        // Check if target is completed
        if ($this->achievement_percentage >= $this->completion_percentage_required && $this->status === 'active') {
            $this->calculateBonus();
        }

        $this->save();
        
        return [
            'achieved_quantity' => $this->achieved_quantity,
            'achievement_percentage' => $this->achievement_percentage,
            'total_bonus_earned' => $this->total_bonus_earned,
        ];
    }

    protected function calculateAchievedQuantity()
    {
        $query = ProductSale::where('representative_id', $this->rep_id)
            ->whereYear('sale_date', substr($this->target_month, 0, 4))
            ->whereMonth('sale_date', substr($this->target_month, 5, 2));

        if ($this->target_type === 'category' && $this->category_id) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->category_id);
            });
        } elseif ($this->target_type === 'supplier' && $this->supplier_id) {
            $query->whereHas('product', function ($q) {
                $q->where('supplier_id', $this->supplier_id);
            });
        } elseif ($this->target_type === 'product' && $this->product_id) {
            $query->where('product_id', $this->product_id);
        }

        return $query->sum('quantity');
    }

    protected function calculateMixedTargetProgress()
    {
        $totalAchieved = 0;
        $totalBonus = 0;

        foreach ($this->items as $item) {
            $achieved = $item->calculateAchievedQuantity($this->rep_id, $this->target_month);
            $item->achieved_quantity = $achieved;
            $item->save();

            $totalAchieved += $achieved;

            if ($item->target_quantity > 0) {
                $itemPercentage = ($achieved / $item->target_quantity) * 100;
                // Calculate bonus for this item if achieved
                if ($achieved >= $item->target_quantity) {
                    $itemBonus = $achieved * $item->bonus_per_unit;
                    $totalBonus += $itemBonus;
                }
            }
        }

        $this->achieved_quantity = $totalAchieved;
        $this->total_bonus_earned = $totalBonus;

        if ($this->target_quantity > 0) {
            $this->achievement_percentage = ($totalAchieved / $this->target_quantity) * 100;
        }

        return [
            'achieved_quantity' => $totalAchieved,
            'achievement_percentage' => $this->achievement_percentage,
            'total_bonus_earned' => $totalBonus,
        ];
    }

    public function calculateBonus()
    {
        if ($this->target_type === 'mixed') {
            // Already calculated in calculateMixedTargetProgress
            return $this->total_bonus_earned;
        }

        if ($this->achievement_percentage >= $this->completion_percentage_required) {
            $this->total_bonus_earned = $this->achieved_quantity * $this->bonus_per_unit;
            $this->status = 'completed';
            $this->save();
            return $this->total_bonus_earned;
        }

        return 0;
    }
}
