<?php
namespace App\Observers;

use App\Models\Staff;
use App\Models\Party;

class StaffObserver
{
    public function created(Staff $staff)
    {
        Party::create([
            'party_type' => $staff->staff_type ?? 'employee',
            'name' => $staff->name,
            'phone' => $staff->phone,
            'staff_id' => $staff->id,
        ]);
    }

    public function updated(Staff $staff)
    {
        Party::updateOrCreate(
            ['staff_id' => $staff->id],
            [
                'party_type' => $staff->staff_type ?? 'employee',
                'name' => $staff->name,
                'phone' => $staff->phone,
            ]
        );
    }

    public function deleted(Staff $staff)
    {
        Party::where('staff_id', $staff->id)->delete();
    }
}
