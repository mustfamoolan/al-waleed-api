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
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name,
            'name_en' => $this->name_en ?? $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'category_id' => $this->category_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'supplier_id' => $this->supplier_id,
            'default_supplier' => $this->whenLoaded('defaultSupplier', function () {
                return $this->defaultSupplier ? new SupplierResource($this->defaultSupplier->supplier) : null;
            }),
            'unit_type' => $this->unit_type,
            'pieces_per_carton' => $this->units_per_pack, // Map migration field
            'piece_weight' => $this->piece_weight,
            'weight_unit' => $this->weight_unit,
            'carton_weight' => $this->carton_weight,
            'current_stock' => $this->current_stock,
            'min_stock_alert' => $this->min_stock_alert,
            'purchase_price' => (float) $this->purchase_price,
            'wholesale_price' => (float) $this->sale_price_wholesale,
            'retail_price' => (float) $this->sale_price_retail,
            'last_purchase_date' => $this->last_purchase_date,
            'last_sale_date' => $this->last_sale_date,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'production_date' => $this->production_date instanceof \DateTime ? $this->production_date->format('Y-m-d') : $this->production_date,
            'expiry_date' => $this->expiry_date instanceof \DateTime ? $this->expiry_date->format('Y-m-d') : $this->expiry_date,
            'is_low_stock' => method_exists($this->resource, 'isLowStock') ? $this->isLowStock() : false,
            'suppliers' => ProductSupplierResource::collection($this->whenLoaded('suppliers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
