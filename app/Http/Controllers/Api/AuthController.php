<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Login with Phone & Password
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        // Check user exists, password correct, and is active
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'الحساب معطل، يرجى مراجعة الإدارة',
            ], 403);
        }

        // Create Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN',
            'note' => 'User logged in successfully',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'LOGOUT',
            'note' => 'User logged out',
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    // Get current user details
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }
}
