<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStudentAttendance extends Model
{
    public const STATUSES = [
        StudentAttendance::STATUS_PRESENT,
        StudentAttendance::STATUS_ABSENT,
        StudentAttendance::STATUS_LATE,
        StudentAttendance::STATUS_EXCUSED,
    ];

    protected $fillable = [
        'student_id',
        'attendance_date',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
