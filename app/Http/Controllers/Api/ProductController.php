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
        $product = Product::create($request->validated());

        return response()->json(['message' => 'تم إنشاء المنتج بنجاح', 'product' => $product], 201);
    }

    public function update(Request $request, Product $product)
    {
        // Simple validation for update for brevity, real app might use separate request class
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'purchase_price' => 'numeric|min:0',
        ]);

        $product->update($request->all());

        return response()->json(['message' => 'تم تحديث المنتج', 'product' => $product]);
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
