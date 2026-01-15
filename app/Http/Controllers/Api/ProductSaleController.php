<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ProductSale\StoreProductSaleRequest;
use App\Http\Resources\ProductSaleResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSaleController extends BaseController
{
    /**
     * Display a listing of product sales.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductSale::with(['product', 'creator']);

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('sale_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('sale_date', '<=', $request->get('to_date'));
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(ProductSaleResource::collection($sales));
    }

    /**
     * Store a newly created product sale.
     */
    public function store(StoreProductSaleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $product = Product::findOrFail($validated['product_id']);

            // Check if enough stock available
            if ($product->current_stock < $validated['quantity']) {
                return $this->errorResponse('Insufficient stock available', 422);
            }

            $totalPrice = $validated['quantity'] * $validated['unit_price'];
            $purchasePriceAtSale = $product->purchase_price;
            $profitAmount = ($validated['unit_price'] - $purchasePriceAtSale) * $validated['quantity'];
            $profitPercentage = $purchasePriceAtSale > 0 
                ? (($validated['unit_price'] - $purchasePriceAtSale) / $purchasePriceAtSale) * 100 
                : 0;

            // Create sale record
            $sale = ProductSale::create([
                'product_id' => $product->product_id,
                'sale_date' => $validated['sale_date'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'total_price' => $totalPrice,
                'purchase_price_at_sale' => $purchasePriceAtSale,
                'profit_amount' => $profitAmount,
                'profit_percentage' => $profitPercentage,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Update product stock
            $stockBefore = $product->current_stock;
            $product->updateStock(-$validated['quantity'], 'sale');
            $stockAfter = $product->current_stock;

            // Update last sale date
            $product->updateLastSaleDate($validated['sale_date']);

            // Create inventory movement
            InventoryMovement::create([
                'product_id' => $product->product_id,
                'movement_type' => 'sale',
                'reference_type' => 'product_sale',
                'reference_id' => $sale->sale_id,
                'quantity' => -$validated['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_price' => $validated['unit_price'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            DB::commit();

            return $this->successResponse(
                new ProductSaleResource($sale->load(['product', 'creator'])),
                'Product sale recorded successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product sale creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to record product sale', 500);
        }
    }

    /**
     * Display the specified product sale.
     */
    public function show(ProductSale $product_sale): JsonResponse
    {
        $product_sale->load(['product', 'creator']);
        return $this->successResponse(new ProductSaleResource($product_sale));
    }

    /**
     * Get profit report.
     */
    public function profitReport(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $productId = $request->get('product_id');

        $query = ProductSale::query();
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        if ($fromDate) {
            $query->where('sale_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('sale_date', '<=', $toDate);
        }

        $totalProfit = $query->sum('profit_amount');
        $totalSales = $query->sum('quantity');
        $totalRevenue = $query->sum('total_price');
        $salesCount = $query->count();

        return $this->successResponse([
            'total_profit' => $totalProfit,
            'total_sales_quantity' => $totalSales,
            'total_revenue' => $totalRevenue,
            'sales_count' => $salesCount,
            'average_profit' => $salesCount > 0 ? $totalProfit / $salesCount : 0,
            'profit_margin' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
        ]);
    }
}
