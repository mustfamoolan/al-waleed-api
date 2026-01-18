<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'return_id' => $this->return_id,
            'sale_invoice_id' => $this->sale_invoice_id,
            'invoice' => new SaleInvoiceResource($this->whenLoaded('invoice')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'representative_id' => $this->representative_id,
            'representative' => new RepresentativeResource($this->whenLoaded('representative')),
            'return_type' => $this->return_type,
            'return_date' => $this->return_date,
            'return_reason' => $this->return_reason,
            'total_return_amount' => $this->total_return_amount,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_by_type' => $this->created_by_type,
            'returned_by' => $this->returned_by,
            'driver' => new \App\Http\Resources\PickerResource($this->whenLoaded('driver')),
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver'),
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'items' => SaleReturnItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
