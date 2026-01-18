<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleInvoiceResource;

class CustomerPaymentResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'invoice_id' => $this->invoice_id,
            'invoice' => new SaleInvoiceResource($this->whenLoaded('invoice')),
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
