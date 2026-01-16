<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'balance_id' => $this->balance_id,
            'rep_id' => $this->rep_id,
            'representative' => $this->whenLoaded('representative', function () {
                return [
                    'rep_id' => $this->representative->rep_id,
                    'full_name' => $this->representative->full_name,
                ];
            }),
            'current_balance' => $this->current_balance,
            'total_earned' => $this->total_earned,
            'total_withdrawn' => $this->total_withdrawn,
            'last_transaction_at' => $this->last_transaction_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
