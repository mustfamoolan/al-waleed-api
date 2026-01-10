<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Representative\StoreRepresentativeRequest;
use App\Http\Requests\Representative\UpdateRepresentativeRequest;
use App\Http\Resources\RepresentativeResource;
use App\Models\Representative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RepresentativeController extends BaseController
{
    /**
     * Display a listing of representatives.
     */
    public function index(): JsonResponse
    {
        $representatives = Representative::all();
        return $this->successResponse(RepresentativeResource::collection($representatives));
    }

    /**
     * Store a newly created representative.
     */
    public function store(StoreRepresentativeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $representative = Representative::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'profile_image' => $validated['profile_image'] ?? null,
        ]);

        return $this->successResponse(new RepresentativeResource($representative), 'Representative created successfully', 201);
    }

    /**
     * Display the specified representative.
     */
    public function show(Representative $representative): JsonResponse
    {
        return $this->successResponse(new RepresentativeResource($representative));
    }

    /**
     * Update the specified representative.
     */
    public function update(UpdateRepresentativeRequest $request, Representative $representative): JsonResponse
    {
        $validated = $request->validated();
        
        $updateData = [
            'full_name' => $validated['full_name'] ?? $representative->full_name,
            'phone_number' => $validated['phone_number'] ?? $representative->phone_number,
            'profile_image' => $validated['profile_image'] ?? $representative->profile_image,
        ];

        if (isset($validated['password']) && $validated['password']) {
            $updateData['password_hash'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $representative->update($updateData);

        return $this->successResponse(new RepresentativeResource($representative), 'Representative updated successfully');
    }

    /**
     * Remove the specified representative.
     */
    public function destroy(Representative $representative): JsonResponse
    {
        $representative->delete();
        return $this->successResponse(null, 'Representative deleted successfully');
    }

    /**
     * Upload profile image for representative.
     */
    public function uploadImage(Request $request, Representative $representative): JsonResponse
    {
        try {
            $request->validate([
                'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // 2MB max
            ]);

            // حذف الصورة القديمة إذا كانت موجودة
            if ($representative->profile_image && Storage::disk('public')->exists($representative->profile_image)) {
                Storage::disk('public')->delete($representative->profile_image);
            }

            // رفع الصورة الجديدة
            $path = $request->file('profile_image')->store('representatives', 'public');
            
            // تحديث المسار في قاعدة البيانات
            $representative->update([
                'profile_image' => $path
            ]);

            return $this->successResponse([
                'profile_image' => $path,
                'profile_image_url' => asset('storage/' . $path)
            ], 'Image uploaded successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Representative image upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload image', 500);
        }
    }
}
