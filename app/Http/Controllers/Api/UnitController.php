<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return response()->json(Unit::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:units,name',
            'is_base' => 'boolean',
        ]);

        $unit = Unit::create($request->all());
        return response()->json($unit, 201);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => 'required|string|unique:units,name,' . $unit->id,
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $unit->update($request->all());
        return response()->json($unit);
    }

    public function toggleStatus(Unit $unit)
    {
        $unit->update(['is_active' => !$unit->is_active]);
        return response()->json(['message' => 'تم تحديث حالة الوحدة بنجاح', 'is_active' => $unit->is_active]);
    }
}
