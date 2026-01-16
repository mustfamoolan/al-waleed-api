<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Representative\StoreRepresentativeTargetRequest;
use App\Http\Requests\Representative\UpdateRepresentativeTargetRequest;
use App\Http\Resources\RepresentativeTargetResource;
use App\Models\Representative;
use App\Models\RepresentativeBalance;
use App\Models\RepresentativeTarget;
use App\Models\RepresentativeTargetItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepresentativeTargetController extends BaseController
{
    /**
     * Get authenticated representative's targets.
     */
    public function myTargets(Request $request): JsonResponse
    {
        $representative = $request->user();
        return $this->index($request, $representative);
    }

    /**
     * Get authenticated representative's target details.
     */
    public function showMyTarget(Request $request, RepresentativeTarget $target): JsonResponse
    {
        $representative = $request->user();
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to you', 404);
        }
        return $this->show($representative, $target);
    }

    /**
     * Display a listing of targets for a representative.
     */
    public function index(Request $request, Representative $representative): JsonResponse
    {
        $query = RepresentativeTarget::where('rep_id', $representative->rep_id)
            ->with(['category', 'supplier', 'product', 'items']);

        if ($request->has('month')) {
            $query->where('target_month', $request->get('month'));
        }

        if ($request->has('type')) {
            $query->where('target_type', $request->get('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $targets = $query->orderBy('target_month', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(RepresentativeTargetResource::collection($targets));
    }

    /**
     * Store a newly created target.
     */
    public function store(StoreRepresentativeTargetRequest $request, Representative $representative): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $targetData = [
                'rep_id' => $representative->rep_id,
                'target_type' => $validated['target_type'],
                'target_month' => $validated['target_month'],
                'target_name' => $validated['target_name'],
                'target_quantity' => $validated['target_quantity'] ?? 0,
                'bonus_per_unit' => $validated['bonus_per_unit'] ?? 0,
                'completion_percentage_required' => $validated['completion_percentage_required'] ?? 100,
                'status' => 'active',
                'created_by' => $manager->manager_id,
            ];

            // Set specific ID based on target type
            if ($validated['target_type'] === 'category' && isset($validated['category_id'])) {
                $targetData['category_id'] = $validated['category_id'];
            } elseif ($validated['target_type'] === 'supplier' && isset($validated['supplier_id'])) {
                $targetData['supplier_id'] = $validated['supplier_id'];
            } elseif ($validated['target_type'] === 'product' && isset($validated['product_id'])) {
                $targetData['product_id'] = $validated['product_id'];
            }

            $target = RepresentativeTarget::create($targetData);

            // If mixed target, create items
            if ($validated['target_type'] === 'mixed' && isset($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    RepresentativeTargetItem::create([
                        'target_id' => $target->target_id,
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'target_quantity' => $item['target_quantity'],
                        'bonus_per_unit' => $item['bonus_per_unit'],
                    ]);
                }
            }

            DB::commit();

            // Calculate initial progress
            $target->calculateProgress();

            return $this->successResponse(
                new RepresentativeTargetResource($target->load(['category', 'supplier', 'product', 'items', 'creator'])),
                'Target created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative target creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create target', 500);
        }
    }

    /**
     * Display the specified target.
     */
    public function show(Representative $representative, RepresentativeTarget $target): JsonResponse
    {
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to this representative', 404);
        }

        $target->load(['category', 'supplier', 'product', 'items', 'creator']);
        return $this->successResponse(new RepresentativeTargetResource($target));
    }

    /**
     * Update the specified target.
     */
    public function update(UpdateRepresentativeTargetRequest $request, Representative $representative, RepresentativeTarget $target): JsonResponse
    {
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to this representative', 404);
        }

        if ($target->status === 'completed') {
            return $this->errorResponse('Cannot update completed target', 422);
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            $updateData = [];

            if (isset($validated['target_name'])) {
                $updateData['target_name'] = $validated['target_name'];
            }

            if (isset($validated['target_quantity'])) {
                $updateData['target_quantity'] = $validated['target_quantity'];
            }

            if (isset($validated['bonus_per_unit'])) {
                $updateData['bonus_per_unit'] = $validated['bonus_per_unit'];
            }

            if (isset($validated['completion_percentage_required'])) {
                $updateData['completion_percentage_required'] = $validated['completion_percentage_required'];
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            $target->update($updateData);

            // Recalculate progress after update
            $target->calculateProgress();

            DB::commit();

            return $this->successResponse(
                new RepresentativeTargetResource($target->load(['category', 'supplier', 'product', 'items', 'creator'])),
                'Target updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative target update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update target', 500);
        }
    }

    /**
     * Remove the specified target.
     */
    public function destroy(Representative $representative, RepresentativeTarget $target): JsonResponse
    {
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to this representative', 404);
        }

        try {
            // Cancel instead of delete
            $target->update(['status' => 'cancelled']);

            return $this->successResponse(null, 'Target cancelled successfully');

        } catch (\Exception $e) {
            Log::error('Representative target deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to cancel target', 500);
        }
    }

    /**
     * Calculate progress for a target.
     */
    public function calculateProgress(Representative $representative, RepresentativeTarget $target): JsonResponse
    {
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to this representative', 404);
        }

        try {
            $progress = $target->calculateProgress();

            return $this->successResponse([
                'target' => new RepresentativeTargetResource($target->load(['category', 'supplier', 'product', 'items'])),
                'progress' => $progress,
            ], 'Progress calculated successfully');

        } catch (\Exception $e) {
            Log::error('Representative target progress calculation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to calculate progress', 500);
        }
    }

    /**
     * Complete target and add bonus to balance.
     */
    public function completeTarget(Representative $representative, RepresentativeTarget $target): JsonResponse
    {
        if ($target->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Target does not belong to this representative', 404);
        }

        if ($target->status === 'completed') {
            return $this->errorResponse('Target is already completed', 422);
        }

        try {
            DB::beginTransaction();

            // Calculate progress first
            $target->calculateProgress();

            // Check if target is actually completed
            if ($target->achievement_percentage < $target->completion_percentage_required) {
                return $this->errorResponse('Target has not reached the required completion percentage', 422);
            }

            // Mark as completed
            $target->update(['status' => 'completed']);

            // Add bonus to balance if not already added
            if ($target->total_bonus_earned > 0) {
                $balance = RepresentativeBalance::getOrCreate($representative->rep_id);
                
                // Check if bonus already added
                $existingTransaction = $balance->transactions()
                    ->where('transaction_type', 'bonus')
                    ->where('related_type', 'representative_target')
                    ->where('related_id', $target->target_id)
                    ->first();

                if (!$existingTransaction) {
                    $balance->addTransaction(
                        'bonus',
                        $target->total_bonus_earned,
                        "مكافأة هدف: {$target->target_name} - شهر {$target->target_month}",
                        'representative_target',
                        $target->target_id,
                        null
                    );
                }
            }

            DB::commit();

            return $this->successResponse(
                new RepresentativeTargetResource($target->load(['category', 'supplier', 'product', 'items'])),
                'Target completed successfully and bonus added to balance'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative target completion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to complete target', 500);
        }
    }
}
