<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeTargetItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'target_item_id' => $this->target_item_id,
            'target_id' => $this->target_id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'target_quantity' => $this->target_quantity,
            'bonus_per_unit' => $this->bonus_per_unit,
            'achieved_quantity' => $this->achieved_quantity,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
