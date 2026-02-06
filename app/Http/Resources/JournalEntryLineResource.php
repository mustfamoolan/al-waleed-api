<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'line_id' => $this->line_id,
            'entry_id' => $this->entry_id,
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
