<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPreparation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'created_by_user_id',
        'subject_id',
        'level_id',
        'lesson_date',
        'title',
        'unit',
        'objectives',
        'skills',
        'concepts',
        'intro',
        'explanation',
        'activities',
        'group_work',
        'assessment',
        'conclusion',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
