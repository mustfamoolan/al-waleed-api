<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6', // Minimum 6 chars
            'role' => 'required|in:manager,supervisor,employee',
            'photo' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'photo' => $request->photo,
            'status' => 'active',
        ]);

        return response()->json(['message' => 'تم إضافة المستخدم بنجاح', 'user' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
            'role' => 'sometimes|in:manager,supervisor,employee',
            'photo' => 'nullable|string',
        ]);

        $user->update($request->only(['name', 'phone', 'role', 'photo']));

        return response()->json(['message' => 'تم تحديث البيانات بنجاح', 'user' => $user]);
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'disabled' : 'active';
        $user->save();

        return response()->json([
            'message' => 'تم تغيير حالة المستخدم بنجاح',
            'status' => $user->status
        ]);
    }

    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }
}
