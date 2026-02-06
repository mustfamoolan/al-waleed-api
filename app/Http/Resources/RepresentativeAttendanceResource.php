<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentativeAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'attendance_id' => $this->attendance_id,
            'rep_id' => $this->rep_id,
            'representative' => $this->whenLoaded('representative', function () {
                return [
                    'rep_id' => $this->representative->rep_id,
                    'full_name' => $this->representative->full_name,
                ];
            }),
            'attendance_date' => $this->attendance_date?->format('Y-m-d'),
            'status' => $this->status,
            'check_in_time' => $this->check_in_time?->format('H:i'),
            'check_out_time' => $this->check_out_time?->format('H:i'),
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'manager_id' => $this->creator->manager_id,
                    'full_name' => $this->creator->full_name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
