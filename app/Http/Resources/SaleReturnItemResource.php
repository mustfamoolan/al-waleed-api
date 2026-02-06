<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleInvoiceItemResource;

class SaleReturnItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'return_item_id' => $this->return_item_id,
            'return_id' => $this->return_id,
            'sale_invoice_item_id' => $this->sale_invoice_item_id,
            'invoice_item' => new SaleInvoiceItemResource($this->whenLoaded('invoiceItem')),
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity_returned' => $this->quantity_returned,
            'unit_type' => $this->unit_type,
            'carton_count' => $this->carton_count,
            'unit_price' => $this->unit_price,
            'total_return_price' => $this->total_return_price,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
