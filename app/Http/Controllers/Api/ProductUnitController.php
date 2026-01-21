<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductUnitController extends BaseController
{
    /**
     * Display a listing of product units for a specific product.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $units = $product->productUnits()->orderBy('is_base_unit', 'desc')->get();
        return $this->successResponse($units);
    }

    /**
     * Store a newly created product unit.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'unit_name' => 'required|string|max:255',
            'conversion_factor' => 'required|numeric|min:0.001',
            'is_base_unit' => 'boolean',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
        ]);

        // If this is set as base unit, unset other base units
        if ($validated['is_base_unit'] ?? false) {
            $product->productUnits()->update(['is_base_unit' => false]);
        }

        $unit = $product->productUnits()->create($validated);

        return $this->successResponse($unit, 'Product unit created successfully', 201);
    }

    /**
     * Display the specified product unit.
     */
    public function show(ProductUnit $productUnit): JsonResponse
    {
        $productUnit->load('product');
        return $this->successResponse($productUnit);
    }

    /**
     * Update the specified product unit.
     */
    public function update(Request $request, ProductUnit $productUnit): JsonResponse
    {
        $validated = $request->validate([
            'unit_name' => 'sometimes|string|max:255',
            'conversion_factor' => 'sometimes|numeric|min:0.001',
            'is_base_unit' => 'boolean',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
        ]);

        // If this is set as base unit, unset other base units
        if (isset($validated['is_base_unit']) && $validated['is_base_unit']) {
            ProductUnit::where('product_id', $productUnit->product_id)
                ->where('id', '!=', $productUnit->id)
                ->update(['is_base_unit' => false]);
        }

        $productUnit->update($validated);

        return $this->successResponse($productUnit, 'Product unit updated successfully');
    }

    /**
     * Remove the specified product unit.
     */
    public function destroy(ProductUnit $productUnit): JsonResponse
    {
        // Don't allow deleting base unit
        if ($productUnit->is_base_unit) {
            return $this->errorResponse('Cannot delete base unit', 422);
        }

        // Check if unit is used in purchase invoices
        if ($productUnit->purchaseInvoiceDetails()->count() > 0) {
            return $this->errorResponse('Cannot delete unit that is used in purchase invoices', 422);
        }

        $productUnit->delete();
        return $this->successResponse(null, 'Product unit deleted successfully');
    }
}
