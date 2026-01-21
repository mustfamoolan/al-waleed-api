<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'productUnits', 'inventoryBatches']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $warehouseId = $request->get('warehouse_id');
            $query->where(function($q) use ($warehouseId) {
                // This will be filtered after loading
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name_ar');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        // Filter low stock after loading
        if ($request->boolean('low_stock')) {
            $warehouseId = $request->get('warehouse_id');
            $products->getCollection()->transform(function($product) use ($warehouseId) {
                $product->current_stock = $product->getCurrentStock($warehouseId);
                return $product;
            })->filter(function($product) {
                return $product->isLowStock();
            });
        }

        return $this->successResponse(ProductResource::collection($products));
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $product = Product::create($validated);

            return $this->successResponse(
                new ProductResource($product->load(['category', 'productUnits'])),
                'Product created successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('Product creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create product', 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'productUnits', 'inventoryBatches']);
        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $product->update($validated);

            return $this->successResponse(
                new ProductResource($product->load(['category', 'productUnits'])),
                'Product updated successfully'
            );

        } catch (\Exception $e) {
            Log::error('Product update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update product', 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Check if product has inventory batches
        if ($product->inventoryBatches()->count() > 0) {
            return $this->errorResponse('Cannot delete product with inventory batches', 422);
        }

        // Delete product image if exists
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        return $this->successResponse(null, 'Product deleted successfully');
    }

    /**
     * Upload product image.
     */
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        try {
            $request->validate([
                'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);

            // Delete old image if exists
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            $path = $request->file('image')->store('products', 'public');
            
            $product->update([
                'image_path' => $path
            ]);

            return $this->successResponse([
                'image_path' => $path,
                'image_url' => asset('storage/' . $path)
            ], 'Image uploaded successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Product image upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload image', 500);
        }
    }

    /**
     * Get product stock information.
     */
    public function stock(Request $request, Product $product): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $currentStock = $product->getCurrentStock($warehouseId);
        $isLowStock = $product->isLowStock($warehouseId);

        return $this->successResponse([
            'product_id' => $product->product_id,
            'product_name' => $product->name_ar,
            'current_stock' => $currentStock,
            'is_low_stock' => $isLowStock,
            'min_stock_alert' => $product->min_stock_alert,
            'batches' => $product->inventoryBatches()
                ->where('status', 'active')
                ->where('quantity_current', '>', 0)
                ->when($warehouseId, function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->orderBy('expiry_date', 'asc')
                ->get(),
        ]);
    }

    /**
     * Get product batches.
     */
    public function batches(Request $request, Product $product): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $status = $request->get('status', 'active');

        $batches = $product->inventoryBatches()
            ->with('warehouse')
            ->when($warehouseId, function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->when($status, function($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('expiry_date', 'asc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($batches);
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        
        $products = Product::where('is_active', true)
            ->with(['category', 'productUnits'])
            ->get()
            ->filter(function($product) use ($warehouseId) {
                return $product->isLowStock($warehouseId);
            })
            ->map(function($product) use ($warehouseId) {
                $product->current_stock = $product->getCurrentStock($warehouseId);
                return $product;
            })
            ->sortBy('current_stock')
            ->values();

        return $this->successResponse(ProductResource::collection($products));
    }
}
