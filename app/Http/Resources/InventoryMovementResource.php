<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'movement_id' => $this->movement_id,
            'product' => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'product_id' => $this->product_id,
            'movement_type' => $this->movement_type,
            'movement_type_label' => $this->getMovementTypeLabel(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'quantity' => $this->quantity,
            'stock_before' => $this->stock_before,
            'stock_after' => $this->stock_after,
            'unit_price' => $this->unit_price,
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn() => [
                'manager_id' => $this->creator->manager_id,
                'full_name' => $this->creator->full_name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
