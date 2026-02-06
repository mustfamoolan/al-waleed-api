<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTarget;
use App\Models\AgentTargetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentTargetController extends Controller
{
    public function store(Request $request)
    {
        // Validation...
        $target = DB::transaction(function () use ($request) {
            $target = AgentTarget::create($request->except('items'));

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $item['agent_target_id'] = $target->id;
                    AgentTargetItem::create($item);
                }
            }
            return $target;
        });

        return response()->json(['message' => 'Target created', 'target' => $target->load('items')], 201);
    }

    public function index(Request $request)
    {
        $query = AgentTarget::with('items');
        if ($request->has('staff_id'))
            $query->where('staff_id', $request->staff_id);
        if ($request->has('period_month'))
            $query->where('period_month', $request->period_month);
        return response()->json($query->get());
    }
}
