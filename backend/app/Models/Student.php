<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'active', 'inactive', 'archived'];

    public const GENDERS = ['male', 'female'];

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'academic_year_id',
        'education_stage_id',
        'level_id',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'reviewed_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function educationStage(): BelongsTo
    {
        return $this->belongsTo(EducationStage::class, 'education_stage_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_students')
            ->withPivot(['status', 'enrolled_on', 'left_on'])
            ->withTimestamps();
    }
}
