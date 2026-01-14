<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('category_name', 'like', "%{$search}%");
        }

        // Load products count if requested
        if ($request->boolean('with_products_count')) {
            $query->withCount('products');
        }

        $categories = $query->orderBy('category_name')->get();
        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $category = Category::create([
            'category_name' => $validated['category_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->successResponse(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        if ($request->boolean('with_products')) {
            $category->load('products');
        }
        
        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();
        
        $category->update($validated);

        return $this->successResponse(
            new CategoryResource($category),
            'Category updated successfully'
        );
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return $this->errorResponse('Cannot delete category with existing products', 422);
        }

        $category->delete();
        return $this->successResponse(null, 'Category deleted successfully');
    }

    /**
     * Get products for a category.
     */
    public function products(Request $request, Category $category): JsonResponse
    {
        $products = $category->products()
            ->where('is_active', true)
            ->get();

        return $this->successResponse(\App\Http\Resources\ProductResource::collection($products));
    }
}
