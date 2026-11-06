<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;

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
            'charts' => [
                'community' => $this->series([
                    'members' => $membersActive,
                    'students' => $studentsActive,
                    'teachers' => $teachersActive,
                    'volunteers' => $volunteersActive,
                ]),
                'activity' => $this->series([
                    'events' => $eventsPublished,
                    'activities' => $activitiesPublished,
                    'initiatives' => $initiatives,
                    'classes' => $classesActive,
                ]),
                'monthly' => $this->monthlyPublishedEvents(12),
            ],
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{key: string, total: int}>
     */
    private function series(array $counts): array
    {
        return collect($counts)
            ->map(fn (int $total, string $key) => ['key' => $key, 'total' => $total])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, total: int}>
     */
    private function monthlyPublishedEvents(int $months): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $counts = Event::query()
            ->published()
            ->where(function ($query) use ($start) {
                $query->where('published_at', '>=', $start)
                    ->orWhere(function ($inner) use ($start) {
                        $inner->whereNull('published_at')->where('created_at', '>=', $start);
                    });
            })
            ->get(['published_at', 'created_at'])
            ->groupBy(function (Event $event) {
                $date = $event->published_at ?: $event->created_at;

                return $date?->format('Y-m') ?: 'unknown';
            })
            ->map->count();

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $series[] = [
                'key' => $key,
                'total' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $series;
    }
}
