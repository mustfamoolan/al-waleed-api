<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'title' => $this->title,
            'address_text' => $this->address_text,
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
