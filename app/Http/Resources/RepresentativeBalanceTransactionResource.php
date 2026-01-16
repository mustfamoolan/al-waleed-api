<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeBalanceTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'rep_id' => $this->rep_id,
            'representative' => $this->whenLoaded('representative', function () {
                return [
                    'rep_id' => $this->representative->rep_id,
                    'full_name' => $this->representative->full_name,
                ];
            }),
            'transaction_type' => $this->transaction_type,
            'amount' => $this->amount,
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'description' => $this->description,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
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
