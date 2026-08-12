<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class_group_id',
        'teacher_id',
        'session_date',
        'starts_at',
        'ends_at',
        'status',
        'room',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }
}
