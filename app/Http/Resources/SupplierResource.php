<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AccountResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'supplier_id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'currency' => $this->currency ?? 'IQD',
            'exchange_rate' => (float) ($this->exchange_rate ?? 1),
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'opening_balance' => (float) ($this->opening_balance ?? 0),
            'current_balance' => (float) ($this->current_balance ?? 0),
            'profile_image' => $this->profile_image ?? null,
            'profile_image_url' => $this->profile_image ? asset('storage/' . $this->profile_image) : null,
            'notes' => $this->notes,
            'is_active' => (bool) ($this->is_active ?? true),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
