<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_id' => $this->payment_id,
            'sale_invoice_id' => $this->sale_invoice_id,
            'invoice' => new SaleInvoiceResource($this->whenLoaded('invoice')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'driver_id' => $this->driver_id,
            'driver' => new \App\Http\Resources\PickerResource($this->whenLoaded('driver')),
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver'),
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
