<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sale_id' => $this->sale_id,
            'product' => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'product_id' => $this->product_id,
            'sale_invoice_id' => $this->sale_invoice_id,
            'sale_date' => $this->sale_date,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'purchase_price_at_sale' => $this->purchase_price_at_sale,
            'profit_amount' => $this->profit_amount,
            'profit_percentage' => $this->profit_percentage,
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn() => [
                'manager_id' => $this->creator->manager_id,
                'full_name' => $this->creator->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
