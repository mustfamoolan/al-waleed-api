<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'staff_id',
        'date',
        'status',
        'minutes_late',
        'note',
        'created_by',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
