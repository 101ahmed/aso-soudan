<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\Student;
use App\Models\Teacher;

class PublicStatsService
{
    public const INITIATIVE_DEPARTMENTS = ['social', 'women-children'];

    /**
     * Aggregated public KPIs without personal data.
     *
     * @return array<string, int>
     */
    public function snapshot(): array
    {
        $membersActive = Member::query()->where('status', 'active')->count();
        $studentsActive = Student::query()->where('status', 'active')->count();
        $teachersActive = Teacher::query()->where('status', 'active')->count();
        $volunteersActive = Member::query()
            ->where('status', 'active')
            ->where('membership_type', 'volunteer')
            ->count();
        $eventsPublished = Event::query()->published()->count();
        $activitiesPublished = Event::query()->published()->where('type', 'activity')->count();
        $classesActive = ClassGroup::query()->where('status', 'active')->count();

        $initiativeDepartmentIds = Department::query()
            ->whereIn('code', self::INITIATIVE_DEPARTMENTS)
            ->pluck('id');

        $initiatives = $initiativeDepartmentIds->isEmpty()
            ? 0
            : Event::query()
                ->published()
                ->whereIn('department_id', $initiativeDepartmentIds)
                ->count();

        $academicDepartmentId = Department::query()->where('code', 'academic')->value('id');
        $academicEvents = $academicDepartmentId
            ? Event::query()->published()->where('department_id', $academicDepartmentId)->count()
            : 0;

        $teachersAndVolunteers = $teachersActive + $volunteersActive;

        return [
            'members' => $membersActive,
            'students' => $studentsActive,
            'teachers' => $teachersActive,
            'volunteers' => $volunteersActive,
            'teachers_and_volunteers' => $teachersAndVolunteers,
            'events' => $eventsPublished,
            'activities' => $activitiesPublished,
            'initiatives' => $initiatives,
            'programs' => $initiatives,
            'academic_events' => $academicEvents,
            'classes' => $classesActive,
        ];
    }
}
