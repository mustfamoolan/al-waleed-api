<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rep_id' => $this->rep_id,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'profile_image' => $this->profile_image,
            'profile_image_url' => $this->profile_image ? asset('storage/' . $this->profile_image) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
