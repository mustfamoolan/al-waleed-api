<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return response()->json(Warehouse::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse created successfully',
            'warehouse' => $warehouse
        ], 201);
    }

    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'nullable|string',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse updated successfully',
            'warehouse' => $warehouse
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse deleted successfully'
        ]);
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        $warehouse->update(['is_active' => !$warehouse->is_active]);
        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير حالة المخزن بنجاح',
            'is_active' => $warehouse->is_active
        ]);
    }
}
