<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'photo_path',
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

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        $years = $this->birth_date->age;

        return $years >= 0 ? $years : null;
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

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    public function enrollInActiveLevelClasses(array $subjectIds = []): void
    {
        if (! $this->level_id || ! $this->academic_year_id) {
            return;
        }

        $otherClassIds = ClassGroup::query()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('level_id', '!=', $this->level_id)
            ->pluck('id');
        if ($otherClassIds->isNotEmpty()) {
            DB::table('class_students')
                ->where('student_id', $this->id)
                ->whereIn('class_group_id', $otherClassIds)
                ->update(['status' => 'inactive', 'updated_at' => now()]);
        }

        $classes = ClassGroup::query()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('level_id', $this->level_id)
            ->where('status', 'active')
            ->when($subjectIds !== [], fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->get();

        foreach ($classes as $class) {
            $this->classGroups()->syncWithoutDetaching([
                $class->id => [
                    'status' => 'active',
                    'enrolled_on' => now()->toDateString(),
                ],
            ]);
        }
    }
}
