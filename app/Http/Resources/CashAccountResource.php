<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'account_id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'currency' => $this->currency,
            'is_active' => (bool) $this->is_active,
            'current_balance' => (float) ($this->current_balance ?? 0),
            'account' => $this->whenLoaded('account'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
