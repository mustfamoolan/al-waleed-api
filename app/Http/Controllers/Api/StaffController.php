<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::with('user');

        if ($request->has('type')) {
            $query->where('staff_type', $request->type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'staff_type' => 'required|in:employee,agent,driver,picker,manager,supervisor',
            'salary_monthly' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'password' => 'nullable|string|min:6', // Add password validation
            'phone' => 'nullable|string', // Add phone validation if creating user
        ]);

        $data = $request->all();

        // Create User if password is provided (mainly for Drivers/Pickers)
        if ($request->has('password') && !empty($request->password)) {
            $user = \App\Models\User::create([
                'name' => $request->name ?? 'Staff User',
                'phone' => $request->phone,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'role' => $request->staff_type ?? 'staff', // Use staff_type as role
                'status' => 'active',
            ]);
            $data['user_id'] = $user->id;
        }

        $staff = Staff::create($data);

        return response()->json(['message' => 'Staff created', 'staff' => $staff], 201);
    }

    public function show(Staff $staff)
    {
        return response()->json($staff->load('user'));
    }

    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'staff_type' => 'sometimes|in:employee,agent,driver,picker,manager,supervisor',
            'salary_monthly' => 'numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $staff->update($request->all());

        return response()->json(['message' => 'Staff updated', 'staff' => $staff]);
    }
}
