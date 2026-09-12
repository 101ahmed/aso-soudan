<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSupervisorVisit extends Model
{
    protected $fillable = [
        'academic_year_id',
        'level_id',
        'visit_month',
        'visited_on',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_month' => 'date',
            'visited_on' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
