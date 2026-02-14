<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RepresentativeResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->id,
            'customer_name' => $this->name,
            'phone_number' => $this->phone,
            'address' => $this->address,
            'agent_id' => $this->agent_id,
            'total_debt' => (float) $this->total_debt,
            'total_paid' => (float) $this->total_paid,
            'last_payment_date' => $this->last_payment_date,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'balance' => new CustomerBalanceResource($this->whenLoaded('balance')),
            'representatives' => RepresentativeResource::collection($this->whenLoaded('representatives')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
