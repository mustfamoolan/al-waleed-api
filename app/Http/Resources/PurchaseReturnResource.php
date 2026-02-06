<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'return_invoice_id' => $this->return_invoice_id,
            'original_invoice_id' => $this->original_invoice_id,
            'original_invoice' => new PurchaseInvoiceResource($this->whenLoaded('originalInvoice')),
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'return_invoice_number' => $this->return_invoice_number,
            'return_date' => $this->return_date,
            'total_amount' => $this->total_amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
