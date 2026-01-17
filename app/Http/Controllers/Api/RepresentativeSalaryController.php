<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Representative\StoreRepresentativeSalaryRequest;
use App\Http\Requests\Representative\UpdateRepresentativeSalaryRequest;
use App\Http\Resources\RepresentativeSalaryResource;
use App\Models\Representative;
use App\Models\RepresentativeBalance;
use App\Models\RepresentativeSalary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepresentativeSalaryController extends BaseController
{
    /**
     * Get authenticated representative's salary for a month.
     */
    public function mySalary(Request $request, ?string $month = null): JsonResponse
    {
        $representative = $request->user();
        $month = $month ?? date('Y-m');

        $salary = \App\Models\RepresentativeSalary::where('rep_id', $representative->rep_id)
            ->where('month', $month)
            ->with(['paidBy'])
            ->first();

        if (!$salary) {
            return $this->errorResponse('Salary not found for this month', 404);
        }

        return $this->successResponse(new \App\Http\Resources\RepresentativeSalaryResource($salary));
    }

    /**
     * Display a listing of salaries for a representative.
     */
    public function index(Request $request, Representative $representative): JsonResponse
    {
        $query = RepresentativeSalary::where('rep_id', $representative->rep_id);

        if ($request->has('month')) {
            $query->where('month', $request->get('month'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $salaries = $query->orderBy('month', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(RepresentativeSalaryResource::collection($salaries));
    }

    /**
     * Store a newly created salary.
     */
    public function store(StoreRepresentativeSalaryRequest $request, Representative $representative): JsonResponse
    {
        try {
            $validated = $request->validated();
            $manager = $request->user();

            $salary = RepresentativeSalary::create([
                'rep_id' => $representative->rep_id,
                'month' => $validated['month'],
                'base_salary' => $validated['base_salary'],
                'total_bonuses' => $validated['total_bonuses'] ?? 0,
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            $salary->calculateTotalAmount();
            $salary->save();

            return $this->successResponse(
                new RepresentativeSalaryResource($salary->load(['representative', 'paidBy'])),
                'Salary created successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('Representative salary creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create salary', 500);
        }
    }

    /**
     * Display the specified salary.
     */
    public function show(Representative $representative, RepresentativeSalary $salary): JsonResponse
    {
        if ($salary->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Salary does not belong to this representative', 404);
        }

        $salary->load(['representative', 'paidBy']);
        return $this->successResponse(new RepresentativeSalaryResource($salary));
    }

    /**
     * Update the specified salary.
     */
    public function update(UpdateRepresentativeSalaryRequest $request, Representative $representative, RepresentativeSalary $salary): JsonResponse
    {
        if ($salary->rep_id !== $representative->rep_id) {
            return $this->errorResponse('Salary does not belong to this representative', 404);
        }

        try {
            $validated = $request->validated();
            $manager = $request->user();

            $updateData = [];

            if (isset($validated['base_salary'])) {
                $updateData['base_salary'] = $validated['base_salary'];
            }

            if (isset($validated['total_bonuses'])) {
                $updateData['total_bonuses'] = $validated['total_bonuses'];
            }

            if (isset($validated['notes'])) {
                $updateData['notes'] = $validated['notes'];
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];

                // If marking as paid, update paid_at and paid_by
                if ($validated['status'] === 'paid' && $salary->status !== 'paid') {
                    $updateData['paid_at'] = now();
                    $updateData['paid_by'] = $manager->manager_id;

                    // Add to balance
                    $balance = RepresentativeBalance::getOrCreate($representative->rep_id);
                    $balance->addTransaction(
                        'salary_payment',
                        $salary->total_amount,
                        "راتب شهر {$salary->month}",
                        'representative_salary',
                        $salary->salary_id,
                        $manager->manager_id
                    );
                }
            }

            $salary->update($updateData);
            $salary->calculateTotalAmount();
            $salary->save();

            return $this->successResponse(
                new RepresentativeSalaryResource($salary->load(['representative', 'paidBy'])),
                'Salary updated successfully'
            );

        } catch (\Exception $e) {
            Log::error('Representative salary update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update salary', 500);
        }
    }

    /**
     * Calculate salary for a month (base + bonuses from completed targets).
     */
    public function calculate(Request $request, Representative $representative): JsonResponse
    {
        try {
            $month = $request->get('month', date('Y-m'));

            // Get base salary from request, or from last salary, or use default
            if ($request->has('base_salary') && $request->get('base_salary') !== null) {
                $baseSalary = $request->get('base_salary');
            } else {
                // Get from last salary if exists
                $lastSalary = RepresentativeSalary::where('rep_id', $representative->rep_id)
                    ->orderBy('month', 'desc')
                    ->first();

                $baseSalary = $lastSalary ? $lastSalary->base_salary : 1000000; // Default 1,000,000 IQD
            }

            // Calculate total bonuses from completed targets in this month
            $totalBonuses = $representative->targets()
                ->where('target_month', $month)
                ->where('status', 'completed')
                ->sum('total_bonus_earned');

            // Check if salary already exists
            $salary = RepresentativeSalary::where('rep_id', $representative->rep_id)
                ->where('month', $month)
                ->first();

            if ($salary) {
                $salary->update([
                    'base_salary' => $baseSalary,
                    'total_bonuses' => $totalBonuses,
                ]);
                $salary->calculateTotalAmount();
                $salary->save();
            } else {
                $salary = RepresentativeSalary::create([
                    'rep_id' => $representative->rep_id,
                    'month' => $month,
                    'base_salary' => $baseSalary,
                    'total_bonuses' => $totalBonuses,
                    'status' => 'pending',
                ]);
                $salary->calculateTotalAmount();
                $salary->save();
            }

            return $this->successResponse(
                new RepresentativeSalaryResource($salary->load(['representative'])),
                'Salary calculated successfully'
            );

        } catch (\Exception $e) {
            Log::error('Representative salary calculation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to calculate salary', 500);
        }
    }
}
