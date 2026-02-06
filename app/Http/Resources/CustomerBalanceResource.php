<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomerResource;

class CustomerBalanceResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'current_balance' => $this->current_balance,
            'total_debt' => $this->total_debt,
            'total_paid' => $this->total_paid,
            'last_transaction_at' => $this->last_transaction_at,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
