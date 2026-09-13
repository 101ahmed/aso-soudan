<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicExamGrade extends Model
{
    protected $fillable = [
        'academic_exam_id',
        'student_id',
        'score',
        'is_absent',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'is_absent' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(AcademicExam::class, 'academic_exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function percent(?float $maxScore = null): ?float
    {
        if ($this->is_absent || $this->score === null) {
            return null;
        }

        $max = $maxScore ?? (float) ($this->relationLoaded('exam') ? $this->exam?->max_score : 0);
        if ($max <= 0) {
            return null;
        }

        return round(((float) $this->score / $max) * 100, 2);
    }

    public function passed(?float $passScore = null): ?bool
    {
        if ($this->is_absent || $this->score === null) {
            return false;
        }

        $pass = $passScore ?? (float) ($this->relationLoaded('exam') ? $this->exam?->pass_score : 0);

        return (float) $this->score >= $pass;
    }
}
