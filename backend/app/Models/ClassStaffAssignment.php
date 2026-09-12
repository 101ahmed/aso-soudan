<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ClassStaffAssignment extends Model
{
    protected $fillable = [
        'academic_year_id',
        'level_id',
        'supervisor_teacher_id',
        'counselor_teacher_id',
        'supervisor_name',
        'counselor_name',
    ];

    public function counselorDisplayName(): ?string
    {
        return $this->typedOrTeacherName($this->counselor_name, $this->counselor);
    }

    public function supervisorDisplayName(): ?string
    {
        return $this->typedOrTeacherName($this->supervisor_name, $this->supervisor);
    }

    private function typedOrTeacherName(?string $typed, $teacher): ?string
    {
        $name = trim((string) $typed);
        if ($name !== '') {
            return $name;
        }

        $fromTeacher = trim((string) ($teacher?->full_name ?? ''));

        return $fromTeacher !== '' ? $fromTeacher : null;
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'supervisor_teacher_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'counselor_teacher_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ClassSupervisorVisit::class, 'level_id', 'level_id');
    }

    public static function currentYear(): ?AcademicYear
    {
        return AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();
    }

    public static function yearIdFor(?Student $student): ?int
    {
        return $student?->academic_year_id ?: static::currentYear()?->id;
    }

    public static function attachToStudents(iterable $students): void
    {
        $items = Collection::make($students)->filter();
        if ($items->isEmpty()) {
            return;
        }

        $yearIds = $items->map(fn (Student $student) => static::yearIdFor($student))->filter()->unique()->values();
        $levelIds = $items->pluck('level_id')->filter()->unique()->values();

        $rows = ($yearIds->isEmpty() || $levelIds->isEmpty())
            ? collect()
            : static::query()
                ->with(['supervisor', 'counselor'])
                ->whereIn('academic_year_id', $yearIds)
                ->whereIn('level_id', $levelIds)
                ->get();

        $visits = ($yearIds->isEmpty() || $levelIds->isEmpty())
            ? collect()
            : ClassSupervisorVisit::query()
                ->whereIn('academic_year_id', $yearIds)
                ->whereIn('level_id', $levelIds)
                ->orderByDesc('visited_on')
                ->get();

        foreach ($items as $student) {
            $yearId = static::yearIdFor($student);
            $match = ($student->level_id && $yearId)
                ? $rows->first(fn (self $row) => (int) $row->academic_year_id === (int) $yearId
                    && (int) $row->level_id === (int) $student->level_id)
                : null;
            $lastVisit = ($student->level_id && $yearId)
                ? $visits->first(fn (ClassSupervisorVisit $visit) => (int) $visit->academic_year_id === (int) $yearId
                    && (int) $visit->level_id === (int) $student->level_id)
                : null;
            $student->setRelation('classStaffAssignment', $match);
            $student->setRelation('supervisorLastVisit', $lastVisit);
        }
    }
}
