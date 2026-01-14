<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\AdjustStockRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'supplier']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $threshold = $request->get('low_stock_threshold', 10);
            $query->where('current_stock', '<=', $threshold);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'product_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));
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

            // Auto-calculate carton weight
            if ($product->unit_type === 'carton' && $product->pieces_per_carton && $product->piece_weight) {
                $product->carton_weight = $product->pieces_per_carton * $product->piece_weight;
                $product->save();
            }

            return $this->successResponse(
                new ProductResource($product->load(['category', 'supplier'])),
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
        $product->load(['category', 'supplier']);
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

            // Auto-calculate carton weight if needed
            if ($product->unit_type === 'carton' && $product->pieces_per_carton && $product->piece_weight) {
                $product->carton_weight = $product->pieces_per_carton * $product->piece_weight;
                $product->save();
            }

            return $this->successResponse(
                new ProductResource($product->load(['category', 'supplier'])),
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
        // Check if product has inventory movements
        if ($product->inventoryMovements()->count() > 0) {
            return $this->errorResponse('Cannot delete product with inventory history', 422);
        }

        // Delete product image if exists
        if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
            Storage::disk('public')->delete($product->product_image);
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
                'product_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);

            // Delete old image if exists
            if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
                Storage::disk('public')->delete($product->product_image);
            }

            $path = $request->file('product_image')->store('products', 'public');
            
            $product->update([
                'product_image' => $path
            ]);

            return $this->successResponse([
                'product_image' => $path,
                'product_image_url' => asset('storage/' . $path)
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
    public function stock(Product $product): JsonResponse
    {
        return $this->successResponse([
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'current_stock' => $product->current_stock,
            'is_low_stock' => $product->isLowStock(),
            'last_movement' => $product->inventoryMovements()->latest()->first(),
        ]);
    }

    /**
     * Get product inventory movements.
     */
    public function movements(Request $request, Product $product): JsonResponse
    {
        $movements = $product->inventoryMovements()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(InventoryMovementResource::collection($movements));
    }

    /**
     * Get product sales.
     */
    public function sales(Request $request, Product $product): JsonResponse
    {
        $sales = $product->sales()
            ->with('creator')
            ->orderBy('sale_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(ProductSaleResource::collection($sales));
    }

    /**
     * Get product profit information.
     */
    public function profit(Request $request, Product $product): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = $product->sales();
        
        if ($fromDate) {
            $query->where('sale_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('sale_date', '<=', $toDate);
        }

        $totalProfit = $query->sum('profit_amount');
        $totalSales = $query->sum('quantity');
        $averageProfit = $query->count() > 0 ? $totalProfit / $query->count() : 0;

        return $this->successResponse([
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'total_profit' => $totalProfit,
            'total_sales_quantity' => $totalSales,
            'average_profit' => $averageProfit,
            'sales_count' => $query->count(),
        ]);
    }

    /**
     * Adjust product stock manually.
     */
    public function adjustStock(AdjustStockRequest $request, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $stockBefore = $product->current_stock;
            $quantity = $validated['quantity'];
            $product->updateStock($quantity, $validated['movement_type']);
            $stockAfter = $product->current_stock;

            // Create inventory movement
            $movement = InventoryMovement::create([
                'product_id' => $product->product_id,
                'movement_type' => $validated['movement_type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            DB::commit();

            return $this->successResponse([
                'product' => new ProductResource($product),
                'movement' => new InventoryMovementResource($movement),
            ], 'Stock adjusted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock adjustment error: ' . $e->getMessage());
            return $this->errorResponse('Failed to adjust stock', 500);
        }
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 10);
        
        $products = Product::where('is_active', true)
            ->where('current_stock', '<=', $threshold)
            ->with(['category', 'supplier'])
            ->orderBy('current_stock', 'asc')
            ->get();

        return $this->successResponse(ProductResource::collection($products));
    }

    /**
     * Get stock report.
     */
    public function stockReport(Request $request): JsonResponse
    {
        $query = Product::where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        $totalProducts = $query->count();
        $totalStockValue = $query->sum(DB::raw('current_stock * purchase_price'));
        $lowStockCount = $query->where('current_stock', '<=', 10)->count();
        $outOfStockCount = $query->where('current_stock', '=', 0)->count();

        return $this->successResponse([
            'total_products' => $totalProducts,
            'total_stock_value' => $totalStockValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ]);
    }

    /**
     * Get profit report.
     */
    public function profitReport(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = \App\Models\ProductSale::query();
        
        if ($fromDate) {
            $query->where('sale_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('sale_date', '<=', $toDate);
        }

        $totalProfit = $query->sum('profit_amount');
        $totalSales = $query->sum('quantity');
        $totalRevenue = $query->sum('total_price');

        return $this->successResponse([
            'total_profit' => $totalProfit,
            'total_sales_quantity' => $totalSales,
            'total_revenue' => $totalRevenue,
            'profit_margin' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
        ]);
    }

    /**
     * Get sales report.
     */
    public function salesReport(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $productId = $request->get('product_id');

        $query = \App\Models\ProductSale::with('product');
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        if ($fromDate) {
            $query->where('sale_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('sale_date', '<=', $toDate);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        return $this->successResponse(ProductSaleResource::collection($sales));
    }
}
