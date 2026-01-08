<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Manager\ManagerLoginRequest;
use App\Models\Manager;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ManagerAuthController extends BaseController
{
    /**
     * Login for Manager or Employee.
     *
     * @param ManagerLoginRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(ManagerLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Try to find as Manager first
        $user = Manager::where('phone_number', $validated['phone_number'])->first();

        // If not found, try as Employee
        if (!$user) {
            $user = Employee::where('phone_number', $validated['phone_number'])->first();
        }

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'phone_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $userType = $user instanceof Manager ? 'manager' : 'employee';

        return $this->successResponse([
            'user' => $user,
            'user_type' => $userType,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Logout user (Revoke the token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $user instanceof Manager ? 'manager' : 'employee';

        return $this->successResponse([
            'user' => $user,
            'user_type' => $userType,
        ]);
    }
}
