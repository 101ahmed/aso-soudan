<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicExam extends Model
{
    use SoftDeletes;

    public const PERIODS = ['term1', 'term2', 'term3', 'annual'];

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'subject_id',
        'title',
        'period',
        'exam_date',
        'max_score',
        'pass_score',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'max_score' => 'decimal:2',
            'pass_score' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(AcademicExamGrade::class);
    }

    public function scopeFiltered($query, array $filters)
    {
        return $query
            ->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($filters['level_id'] ?? null, fn ($q, $id) => $q->where('level_id', $id))
            ->when($filters['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id))
            ->when($filters['period'] ?? null, fn ($q, $period) => $q->where('period', $period))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('title', 'like', '%'.$search.'%');
            });
    }
}
