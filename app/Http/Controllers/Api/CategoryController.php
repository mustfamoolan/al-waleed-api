<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(ProductCategory::with('parent')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:product_categories,name',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        $category = ProductCategory::create($request->all());
        return response()->json($category, 201);
    }

    public function show(ProductCategory $category)
    {
        return response()->json($category->load('parent', 'children'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        $request->validate([
            'name' => 'required|string|unique:product_categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update($request->all());
        return response()->json($category);
    }

    public function toggleStatus(ProductCategory $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        return response()->json(['message' => 'تم تحديث حالة الصنف بنجاح', 'is_active' => $category->is_active]);
    }
}
