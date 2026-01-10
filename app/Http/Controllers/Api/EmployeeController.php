<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeController extends BaseController
{
    /**
     * Display a listing of employees.
     */
    public function index(): JsonResponse
    {
        $employees = Employee::all();
        return $this->successResponse(EmployeeResource::collection($employees));
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $employee = Employee::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'job_role' => $validated['job_role'],
            'profile_image' => $validated['profile_image'] ?? null,
        ]);

        return $this->successResponse(new EmployeeResource($employee), 'Employee created successfully', 201);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): JsonResponse
    {
        return $this->successResponse(new EmployeeResource($employee));
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $validated = $request->validated();
        
        $updateData = [
            'full_name' => $validated['full_name'] ?? $employee->full_name,
            'phone_number' => $validated['phone_number'] ?? $employee->phone_number,
            'job_role' => $validated['job_role'] ?? $employee->job_role,
            'profile_image' => $validated['profile_image'] ?? $employee->profile_image,
        ];

        if (isset($validated['password']) && $validated['password']) {
            $updateData['password_hash'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $employee->update($updateData);

        return $this->successResponse(new EmployeeResource($employee), 'Employee updated successfully');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();
        return $this->successResponse(null, 'Employee deleted successfully');
    }

    /**
     * Upload profile image for employee.
     */
    public function uploadImage(Request $request, Employee $employee): JsonResponse
    {
        try {
            $request->validate([
                'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // 2MB max
            ]);

            // حذف الصورة القديمة إذا كانت موجودة
            if ($employee->profile_image && Storage::disk('public')->exists($employee->profile_image)) {
                Storage::disk('public')->delete($employee->profile_image);
            }

            // رفع الصورة الجديدة
            $path = $request->file('profile_image')->store('employees', 'public');
            
            // تحديث المسار في قاعدة البيانات
            $employee->update([
                'profile_image' => $path
            ]);

            return $this->successResponse([
                'profile_image' => $path,
                'profile_image_url' => asset('storage/' . $path)
            ], 'Image uploaded successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Employee image upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload image', 500);
        }
    }
}
