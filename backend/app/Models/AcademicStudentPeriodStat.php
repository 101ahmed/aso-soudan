<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicStudentPeriodStat extends Model
{
    protected $fillable = [
        'academic_year_id',
        'student_id',
        'period',
        'previous_average',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_average' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
