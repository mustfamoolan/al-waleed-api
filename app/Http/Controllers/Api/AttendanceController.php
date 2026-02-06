<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        // $request->validate...
        $att = Attendance::updateOrCreate(
            ['staff_id' => $request->staff_id, 'date' => $request->date],
            array_merge($request->all(), ['created_by' => auth()->id()])
        );
        return response()->json($att, 201);
    }

    public function index(Request $request)
    {
        $query = Attendance::query();
        if ($request->has('staff_id'))
            $query->where('staff_id', $request->staff_id);
        if ($request->has('month'))
            $query->where('date', 'like', $request->month . '%');
        return response()->json($query->get());
    }
}
