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
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
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
            'min_stock_alert' => $this->min_stock_alert,
            'purchase_price' => $this->purchase_price,
            'wholesale_price' => $this->wholesale_price,
            'retail_price' => $this->retail_price,
            'last_purchase_date' => $this->last_purchase_date,
            'last_sale_date' => $this->last_sale_date,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'production_date' => $this->production_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'is_low_stock' => $this->isLowStock(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
