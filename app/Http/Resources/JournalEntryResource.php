<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entry_id' => $this->entry_id,
            'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date,
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'is_posted' => $this->is_posted,
            'posted_at' => $this->posted_at,
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
