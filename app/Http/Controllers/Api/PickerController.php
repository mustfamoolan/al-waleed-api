<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Picker\StorePickerRequest;
use App\Http\Requests\Picker\UpdatePickerRequest;
use App\Http\Resources\PickerResource;
use App\Models\Picker;
use Illuminate\Http\JsonResponse;

class PickerController extends BaseController
{
    /**
     * Display a listing of pickers.
     */
    public function index(): JsonResponse
    {
        $pickers = Picker::all();
        return $this->successResponse(PickerResource::collection($pickers));
    }

    /**
     * Store a newly created picker.
     */
    public function store(StorePickerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $picker = Picker::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'profile_image' => $validated['profile_image'] ?? null,
        ]);

        return $this->successResponse(new PickerResource($picker), 'Picker created successfully', 201);
    }

    /**
     * Display the specified picker.
     */
    public function show(Picker $picker): JsonResponse
    {
        return $this->successResponse(new PickerResource($picker));
    }

    /**
     * Update the specified picker.
     */
    public function update(UpdatePickerRequest $request, Picker $picker): JsonResponse
    {
        $validated = $request->validated();
        
        $updateData = [
            'full_name' => $validated['full_name'] ?? $picker->full_name,
            'phone_number' => $validated['phone_number'] ?? $picker->phone_number,
            'profile_image' => $validated['profile_image'] ?? $picker->profile_image,
        ];

        if (isset($validated['password']) && $validated['password']) {
            $updateData['password_hash'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $picker->update($updateData);

        return $this->successResponse(new PickerResource($picker), 'Picker updated successfully');
    }

    /**
     * Remove the specified picker.
     */
    public function destroy(Picker $picker): JsonResponse
    {
        $picker->delete();
        return $this->successResponse(null, 'Picker deleted successfully');
    }
}
