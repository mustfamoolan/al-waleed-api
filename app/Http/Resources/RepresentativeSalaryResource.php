<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeSalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'salary_id' => $this->salary_id,
            'rep_id' => $this->rep_id,
            'representative' => $this->whenLoaded('representative', function () {
                return [
                    'rep_id' => $this->representative->rep_id,
                    'full_name' => $this->representative->full_name,
                ];
            }),
            'month' => $this->month,
            'base_salary' => $this->base_salary,
            'attendance_days' => $this->attendance_days,
            'absent_days' => $this->absent_days,
            'daily_salary' => $this->daily_salary,
            'deductions' => $this->deductions,
            'total_bonuses' => $this->total_bonuses,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'paid_by' => $this->whenLoaded('paidBy', function () {
                return $this->paidBy ? [
                    'manager_id' => $this->paidBy->manager_id,
                    'full_name' => $this->paidBy->full_name,
                ] : null;
            }),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
