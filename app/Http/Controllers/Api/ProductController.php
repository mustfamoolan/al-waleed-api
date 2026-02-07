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
        $products = Product::with(['category', 'baseUnit', 'packUnit', 'defaultSupplier.supplier'])->get();
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

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        $product = Product::create($data);

        // Link default supplier (MANDATORY)
        if (isset($data['supplier_id'])) {
            ProductSupplier::create([
                'product_id' => $product->id,
                'supplier_id' => $data['supplier_id'],
                'is_default' => true,
                'last_purchase_price' => $data['purchase_price'] ?? 0,
                'currency' => 'IQD',
            ]);
        }

        return new ProductResource($product->load('defaultSupplier.supplier'));
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path && \Storage::disk('public')->exists($product->image_path)) {
                \Storage::disk('public')->delete($product->image_path);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        $product->update($data);

        // Update default supplier if changed
        if (isset($data['supplier_id'])) {
            // Remove old default
            ProductSupplier::where('product_id', $product->id)->update(['is_default' => false]);

            // Set new default or create if not exists
            ProductSupplier::updateOrCreate(
                ['product_id' => $product->id, 'supplier_id' => $data['supplier_id']],
                ['is_default' => true, 'last_purchase_price' => $data['purchase_price'] ?? 0]
            );
        }

        return new ProductResource($product->load('defaultSupplier.supplier'));
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
