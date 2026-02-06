<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // List all users
    public function index(Request $request)
    {
        // Ensure only managers can list users (optional logic later)
        $users = User::all();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    // Create new user (Manager only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6', // Can be auto-generated later
            'role' => ['required', Rule::in(['manager', 'supervisor', 'employee'])],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'photo' => 'nullable|string', // URL or path
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'photo' => $validated['photo'],
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'CREATE_USER',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'note' => "Created user: {$user->name} ({$user->phone})",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة المستخدم بنجاح',
            'data' => $user,
        ], 201);
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => ['sometimes', Rule::in(['manager', 'supervisor', 'employee'])],
            'status' => ['sometimes', Rule::in(['active', 'disabled'])],
            'photo' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'UPDATE_USER',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'note' => "Updated user: {$user->name}",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data' => $user,
        ]);
    }

    // Delete user (Optional, usually we disable instead)
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent self-delete
        if ($user->id === $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكنك حذف حسابك الحالي',
            ], 403);
        }

        $user->delete();

        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'DELETE_USER',
            'entity_type' => 'User',
            'entity_id' => $id,
            'note' => "Deleted user: {$user->name}",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }
}
