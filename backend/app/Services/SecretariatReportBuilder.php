<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Event;
use App\Models\ExternalContactRequest;
use App\Models\ExternalDocument;
use App\Models\ExternalPartner;
use App\Models\FinanceBudget;
use App\Models\FinanceExpense;
use App\Models\FinanceRevenue;
use App\Models\HelpRequest;
use App\Models\MediaCenterItem;
use App\Models\MediaDecision;
use App\Models\Member;
use App\Models\News;
use App\Models\SecretariatMessage;
use App\Models\SocialVisit;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SecretariatReportBuilder
{
    public function build(Department $department, int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        return [
            'year' => $year,
            'generated_at' => now()->toIso8601String(),
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name_ar' => $department->name_ar,
                'name_fr' => $department->name_fr,
                'officer_name_ar' => $department->officer_name_ar,
                'officer_name_fr' => $department->officer_name_fr,
                'deputy_name_ar' => $department->deputy_name_ar,
                'deputy_name_fr' => $department->deputy_name_fr,
            ],
            'activity' => $this->activity($department->id, $from, $to),
            'specific' => $this->specific($department->code, $year, $from, $to),
        ];
    }

    public function summaries(iterable $departments, int $year): array
    {
        return collect($departments)->map(function (Department $department) use ($year) {
            $report = $this->build($department, $year);
            $activity = $report['activity'];

            return [
                'code' => $department->code,
                'name_ar' => $department->name_ar,
                'name_fr' => $department->name_fr,
                'news' => $activity['news_total'],
                'events' => $activity['events_total'],
                'announcements' => $activity['announcements_total'],
                'messages' => $activity['messages_total'],
                'highlight' => $this->highlight($report),
            ];
        })->values()->all();
    }

    private function activity(int $departmentId, Carbon $from, Carbon $to): array
    {
        $news = News::query()->where('department_id', $departmentId)->whereBetween('created_at', [$from, $to]);
        $events = Event::query()->where('department_id', $departmentId)->where(function ($q) use ($from, $to) {
            $q->whereBetween('starts_at', [$from, $to])->orWhereBetween('created_at', [$from, $to]);
        });
        $announcements = Announcement::query()->where('department_id', $departmentId)->whereBetween('created_at', [$from, $to]);
        $albums = Album::query()->where('department_id', $departmentId)->whereBetween('created_at', [$from, $to]);
        $messages = SecretariatMessage::query()->where('department_id', $departmentId)->whereBetween('created_at', [$from, $to]);

        return [
            'news_total' => (clone $news)->count(),
            'news_published' => (clone $news)->where('status', 'published')->count(),
            'events_total' => (clone $events)->count(),
            'events_published' => (clone $events)->where('status', 'published')->count(),
            'announcements_total' => (clone $announcements)->count(),
            'albums_total' => (clone $albums)->count(),
            'messages_total' => (clone $messages)->count(),
            'messages_new' => (clone $messages)->where('status', 'new')->count(),
            'recent_news' => News::query()
                ->where('department_id', $departmentId)
                ->whereBetween('created_at', [$from, $to])
                ->latest('id')
                ->limit(8)
                ->get(['id', 'title_ar', 'title_fr', 'status', 'published_at', 'created_at'])
                ->map(fn (News $item) => [
                    'title_ar' => $item->title_ar,
                    'title_fr' => $item->title_fr,
                    'status' => $item->status,
                    'date' => ($item->published_at ?? $item->created_at)?->toDateString(),
                ])
                ->all(),
            'recent_events' => Event::query()
                ->where('department_id', $departmentId)
                ->where(function ($q) use ($from, $to) {
                    $q->whereBetween('starts_at', [$from, $to])->orWhereBetween('created_at', [$from, $to]);
                })
                ->latest('id')
                ->limit(8)
                ->get(['id', 'title_ar', 'title_fr', 'type', 'status', 'starts_at'])
                ->map(fn (Event $item) => [
                    'title_ar' => $item->title_ar,
                    'title_fr' => $item->title_fr,
                    'type' => $item->type,
                    'status' => $item->status,
                    'date' => $item->starts_at?->toDateString(),
                ])
                ->all(),
        ];
    }

    private function specific(string $code, int $year, Carbon $from, Carbon $to): array
    {
        return match ($code) {
            'academic' => $this->academic(),
            'social' => $this->social($from, $to),
            'finance' => $this->finance($year),
            'media' => $this->media($from, $to),
            'statistics' => $this->statistics(),
            'external-relations' => $this->external($from, $to),
            default => ['kind' => 'activity'],
        };
    }

    private function academic(): array
    {
        $students = Student::query();
        $teachers = Teacher::query()->where('status', 'active');
        $attendance = StudentAttendance::query();
        $present = (clone $attendance)->where('status', StudentAttendance::STATUS_PRESENT)->count();
        $late = (clone $attendance)->where('status', StudentAttendance::STATUS_LATE)->count();
        $absent = (clone $attendance)->where('status', StudentAttendance::STATUS_ABSENT)->count();
        $excused = (clone $attendance)->where('status', StudentAttendance::STATUS_EXCUSED)->count();
        $recorded = $present + $late + $absent + $excused;

        return [
            'kind' => 'academic',
            'students_total' => (clone $students)->count(),
            'students_active' => (clone $students)->where('status', 'active')->count(),
            'teachers_active' => $teachers->count(),
            'attendance_present' => $present,
            'attendance_late' => $late,
            'attendance_absent' => $absent,
            'attendance_excused' => $excused,
            'attendance_rate' => $recorded > 0 ? round((($present + $late) / $recorded) * 100, 1) : null,
        ];
    }

    private function social(Carbon $from, Carbon $to): array
    {
        $query = HelpRequest::query()->whereBetween('created_at', [$from, $to]);
        $visits = SocialVisit::query()->whereBetween('visited_on', [$from->toDateString(), $to->toDateString()]);

        return [
            'kind' => 'social',
            'total' => (clone $query)->count(),
            'by_status' => $this->countsBy((clone $query), 'status'),
            'by_type' => $this->countsBy((clone $query), 'help_type'),
            'visits_total' => (clone $visits)->count(),
            'visits_by_status' => $this->countsBy((clone $visits), 'status'),
            'visits_by_type' => $this->countsBy((clone $visits), 'visit_type'),
        ];
    }

    private function finance(int $year): array
    {
        $budget = FinanceBudget::query()->where('year', $year)->first();
        $approved = (float) ($budget?->amount ?? 0);
        $revenues = (float) FinanceRevenue::query()->forYear($year)->sum('amount');
        $expenses = (float) FinanceExpense::query()->forYear($year)->sum('amount');

        return [
            'kind' => 'finance',
            'approved_budget' => $approved,
            'total_revenues' => $revenues,
            'total_expenses' => $expenses,
            'balance' => $revenues - $expenses,
            'remaining_budget' => $approved - $expenses,
            'revenues_by_source' => FinanceRevenue::query()
                ->forYear($year)
                ->selectRaw('source, SUM(amount) as total, COUNT(*) as operations')
                ->groupBy('source')
                ->get()
                ->map(fn ($row) => [
                    'key' => $row->source,
                    'total' => (float) $row->total,
                    'operations' => (int) $row->operations,
                ])
                ->all(),
            'expenses_by_category' => FinanceExpense::query()
                ->forYear($year)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as operations')
                ->groupBy('category')
                ->get()
                ->map(fn ($row) => [
                    'key' => $row->category,
                    'total' => (float) $row->total,
                    'operations' => (int) $row->operations,
                ])
                ->all(),
        ];
    }

    private function media(Carbon $from, Carbon $to): array
    {
        $decisions = MediaDecision::query()->whereBetween('created_at', [$from, $to]);
        $press = MediaCenterItem::query()->whereBetween('created_at', [$from, $to]);

        return [
            'kind' => 'media',
            'decisions_total' => (clone $decisions)->count(),
            'decisions_by_status' => $this->countsBy((clone $decisions), 'status'),
            'decisions_by_kind' => $this->countsBy((clone $decisions), 'kind'),
            'press_total' => (clone $press)->count(),
            'press_published' => (clone $press)->where('status', 'published')->count(),
            'press_by_kind' => $this->countsBy((clone $press), 'kind'),
        ];
    }

    private function statistics(): array
    {
        $members = Member::query();
        $public = app(PublicStatsService::class)->snapshot();

        return [
            'kind' => 'statistics',
            'members_total' => (clone $members)->count(),
            'members_active' => $public['members'],
            'students_active' => $public['students'],
            'teachers_and_volunteers' => $public['teachers_and_volunteers'],
            'events_published' => $public['events'],
            'initiatives' => $public['initiatives'],
            'members_by_status' => $this->countsBy((clone $members), 'status'),
            'members_by_type' => $this->countsBy((clone $members), 'membership_type'),
        ];
    }

    private function external(Carbon $from, Carbon $to): array
    {
        $contacts = ExternalContactRequest::query()->whereBetween('created_at', [$from, $to]);

        return [
            'kind' => 'external',
            'partners_total' => ExternalPartner::query()->count(),
            'partners_by_status' => $this->countsBy(ExternalPartner::query(), 'partnership_status'),
            'documents_total' => ExternalDocument::query()->count(),
            'contacts_total' => (clone $contacts)->count(),
            'contacts_by_status' => $this->countsBy((clone $contacts), 'status'),
        ];
    }

    private function countsBy($query, string $column): array
    {
        return $query
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->get()
            ->map(fn ($row) => [
                'key' => $row->{$column} ?: 'unknown',
                'total' => (int) $row->total,
            ])
            ->all();
    }

    private function highlight(array $report): string
    {
        $specific = $report['specific'] ?? [];

        return match ($specific['kind'] ?? 'activity') {
            'academic' => (string) ($specific['students_active'] ?? 0),
            'social' => (string) ($specific['total'] ?? 0),
            'finance' => (string) ($specific['balance'] ?? 0),
            'media' => (string) ($specific['press_published'] ?? 0),
            'statistics' => (string) ($specific['members_total'] ?? 0),
            'external' => (string) ($specific['partners_total'] ?? 0),
            default => (string) ($report['activity']['news_total'] ?? 0),
        };
    }
}
