<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'baseUnit', 'packUnit'])->get();
        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'baseUnit', 'packUnit', 'suppliers', 'balances']));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        if (isset($data['name_ar']) && !isset($data['name']))
            $data['name'] = $data['name_ar'];
        if (isset($data['retail_price']) && !isset($data['sale_price_retail']))
            $data['sale_price_retail'] = $data['retail_price'];
        if (isset($data['wholesale_price']) && !isset($data['sale_price_wholesale']))
            $data['sale_price_wholesale'] = $data['wholesale_price'];
        if (isset($data['pieces_per_carton']) && !isset($data['units_per_pack']))
            $data['units_per_pack'] = $data['pieces_per_carton'];

        // Calculate carton weight automatically
        if (isset($data['piece_weight']) && isset($data['units_per_pack'])) {
            $data['carton_weight'] = $data['piece_weight'] * $data['units_per_pack'];
        }

        $product = Product::create($data);

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->all();
        if (isset($data['name_ar']) && !isset($data['name']))
            $data['name'] = $data['name_ar'];
        if (isset($data['retail_price']) && !isset($data['sale_price_retail']))
            $data['sale_price_retail'] = $data['retail_price'];
        if (isset($data['wholesale_price']) && !isset($data['sale_price_wholesale']))
            $data['sale_price_wholesale'] = $data['wholesale_price'];
        if (isset($data['pieces_per_carton']) && !isset($data['units_per_pack']))
            $data['units_per_pack'] = $data['pieces_per_carton'];

        // Calculate carton weight automatically
        if (isset($data['piece_weight']) && isset($data['units_per_pack'])) {
            $data['carton_weight'] = $data['piece_weight'] * $data['units_per_pack'];
        }

        $product->update($data);

        return new ProductResource($product);
    }

    public function syncSuppliers(Request $request, Product $product)
    {
        $request->validate([
            'suppliers' => 'required|array',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,id',
            'suppliers.*.last_price' => 'numeric',
            'suppliers.*.currency' => 'in:IQD,USD',
        ]);

        // Sync logic is a bit manual for pivot extra fields
        $product->suppliers()->delete(); // Clear old

        foreach ($request->suppliers as $s) {
            $product->suppliers()->create([
                'supplier_id' => $s['supplier_id'],
                'last_price' => $s['last_price'] ?? 0,
                'currency' => $s['currency'] ?? 'IQD',
            ]);
        }

        return response()->json(['message' => 'تم تحديث الموردين للمنتج']);
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير حالة المنتج بنجاح',
            'is_active' => $product->is_active
        ]);
    }
}
