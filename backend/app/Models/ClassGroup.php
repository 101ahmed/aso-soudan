<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'subject_id',
        'teacher_id',
        'name',
        'code',
        'capacity',
        'status',
    ];

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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_students')
            ->withPivot(['status', 'enrolled_on', 'left_on'])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }
}
