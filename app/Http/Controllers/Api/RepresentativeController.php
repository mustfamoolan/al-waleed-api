<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Representative\StoreRepresentativeRequest;
use App\Http\Requests\Representative\UpdateRepresentativeRequest;
use App\Http\Resources\RepresentativeResource;
use App\Models\Representative;
use Illuminate\Http\JsonResponse;

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
}
