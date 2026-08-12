<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouncilMeetingAttendance extends Model
{
    protected $fillable = [
        'council_meeting_id',
        'council_member_id',
        'status',
        'note',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(CouncilMeeting::class, 'council_meeting_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CouncilMember::class, 'council_member_id');
    }
}
