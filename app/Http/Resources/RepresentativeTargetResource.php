<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeTargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'target_id' => $this->target_id,
            'rep_id' => $this->rep_id,
            'representative' => $this->whenLoaded('representative', function () {
                return [
                    'rep_id' => $this->representative->rep_id,
                    'full_name' => $this->representative->full_name,
                ];
            }),
            'target_type' => $this->target_type,
            'target_month' => $this->target_month,
            'target_name' => $this->target_name,
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'category_id' => $this->category->category_id,
                    'category_name' => $this->category->category_name,
                ] : null;
            }),
            'supplier' => $this->whenLoaded('supplier', function () {
                return $this->supplier ? [
                    'supplier_id' => $this->supplier->supplier_id,
                    'company_name' => $this->supplier->company_name,
                ] : null;
            }),
            'product' => $this->whenLoaded('product', function () {
                return $this->product ? [
                    'product_id' => $this->product->product_id,
                    'product_name' => $this->product->product_name,
                ] : null;
            }),
            'target_quantity' => $this->target_quantity,
            'bonus_per_unit' => $this->bonus_per_unit,
            'completion_percentage_required' => $this->completion_percentage_required,
            'status' => $this->status,
            'achieved_quantity' => $this->achieved_quantity,
            'achievement_percentage' => $this->achievement_percentage,
            'total_bonus_earned' => $this->total_bonus_earned,
            'items' => RepresentativeTargetItemResource::collection($this->whenLoaded('items')),
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'manager_id' => $this->creator->manager_id,
                    'full_name' => $this->creator->full_name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
