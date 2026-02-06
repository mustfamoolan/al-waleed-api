<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'return_item_id' => $this->return_item_id,
            'return_invoice_id' => $this->return_invoice_id,
            'original_item_id' => $this->original_item_id,
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            'quantity' => $this->quantity,
            'unit_type' => $this->unit_type,
            'carton_count' => $this->carton_count,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
