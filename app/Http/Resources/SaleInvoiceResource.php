<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RepresentativeResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SaleInvoiceItemResource;
use App\Http\Resources\CustomerPaymentResource;

class SaleInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this->invoice_id,
            'representative_id' => $this->representative_id,
            'representative' => new RepresentativeResource($this->whenLoaded('representative')),
            'buyer_type' => $this->buyer_type,
            'buyer_id' => $this->buyer_id,
            'buyer_name' => $this->buyer_name,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'buyer_employee' => $this->when($this->buyer_type === 'employee' && $this->relationLoaded('buyerEmployee'), function () {
                return new EmployeeResource($this->buyerEmployee);
            }),
            'buyer_representative' => $this->when($this->buyer_type === 'representative' && $this->relationLoaded('buyerRepresentative'), function () {
                return new RepresentativeResource($this->buyerRepresentative);
            }),
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'special_discount_percentage' => $this->special_discount_percentage,
            'special_discount_amount' => $this->special_discount_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'buyer_display_name' => $this->getBuyerName(),
            'is_overdue' => $this->isOverdue(),
            'notes' => $this->notes,
            'items' => SaleInvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => CustomerPaymentResource::collection($this->whenLoaded('payments')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
