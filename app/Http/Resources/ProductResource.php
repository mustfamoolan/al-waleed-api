<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'sku' => $this->sku,
            'product_image' => $this->product_image,
            'product_image_url' => $this->product_image ? asset('storage/' . $this->product_image) : null,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'category_id' => $this->category_id,
            'supplier' => $this->whenLoaded('supplier', fn() => new SupplierResource($this->supplier)),
            'supplier_id' => $this->supplier_id,
            'unit_type' => $this->unit_type,
            'pieces_per_carton' => $this->pieces_per_carton,
            'piece_weight' => $this->piece_weight,
            'weight_unit' => $this->weight_unit,
            'carton_weight' => $this->carton_weight,
            'current_stock' => $this->current_stock,
            'purchase_price' => $this->purchase_price,
            'wholesale_price' => $this->wholesale_price,
            'retail_price' => $this->retail_price,
            'last_purchase_date' => $this->last_purchase_date,
            'last_sale_date' => $this->last_sale_date,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'is_low_stock' => $this->isLowStock(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
