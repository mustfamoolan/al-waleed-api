<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->item_id,
            'invoice_id' => $this->invoice_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            'category_name' => $this->category_name,
            'quantity' => $this->quantity,
            'unit_type' => $this->unit_type,
            'carton_count' => $this->carton_count,
            'unit_price' => $this->unit_price,
            'cost_after_purchase' => $this->cost_after_purchase,
            'transport_cost_share' => $this->transport_cost_share,
            'retail_price' => $this->retail_price,
            'wholesale_price' => $this->wholesale_price,
            'discount_percentage' => $this->discount_percentage,
            'tax_percentage' => $this->tax_percentage,
            'total_price' => $this->total_price,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
