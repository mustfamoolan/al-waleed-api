<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Manager\StoreManagerRequest;
use App\Http\Requests\Manager\UpdateManagerRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ManagerController extends BaseController
{
    /**
     * Display a listing of managers.
     */
    public function index(): JsonResponse
    {
        $managers = Manager::all();
        return $this->successResponse(ManagerResource::collection($managers));
    }

    /**
     * Store a newly created manager.
     */
    public function store(StoreManagerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $manager = Manager::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'profile_image' => $validated['profile_image'] ?? null,
        ]);

        return $this->successResponse(new ManagerResource($manager), 'Manager created successfully', 201);
    }

    /**
     * Display the specified manager.
     */
    public function show(Manager $manager): JsonResponse
    {
        return $this->successResponse(new ManagerResource($manager));
    }

    /**
     * Update the specified manager.
     */
    public function update(UpdateManagerRequest $request, Manager $manager): JsonResponse
    {
        $validated = $request->validated();
        
        $updateData = [
            'full_name' => $validated['full_name'] ?? $manager->full_name,
            'phone_number' => $validated['phone_number'] ?? $manager->phone_number,
            'profile_image' => $validated['profile_image'] ?? $manager->profile_image,
        ];

        if (isset($validated['password']) && $validated['password']) {
            $updateData['password_hash'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $manager->update($updateData);

        return $this->successResponse(new ManagerResource($manager), 'Manager updated successfully');
    }

    /**
     * Remove the specified manager.
     */
    public function destroy(Manager $manager): JsonResponse
    {
        $manager->delete();
        return $this->successResponse(null, 'Manager deleted successfully');
    }

    /**
     * Upload profile image for manager.
     */
    public function uploadImage(Request $request, Manager $manager): JsonResponse
    {
        try {
            $request->validate([
                'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // 2MB max
            ]);

            // حذف الصورة القديمة إذا كانت موجودة
            if ($manager->profile_image && Storage::disk('public')->exists($manager->profile_image)) {
                Storage::disk('public')->delete($manager->profile_image);
            }

            // رفع الصورة الجديدة
            $path = $request->file('profile_image')->store('managers', 'public');
            
            // تحديث المسار في قاعدة البيانات
            $manager->update([
                'profile_image' => $path
            ]);

            return $this->successResponse([
                'profile_image' => $path,
                'profile_image_url' => asset('storage/' . $path)
            ], 'Image uploaded successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Manager image upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload image', 500);
        }
    }
}
