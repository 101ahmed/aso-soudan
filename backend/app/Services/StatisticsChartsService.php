<?php

namespace App\Services;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StatisticsChartsService
{
    public function dashboard(int $months = 12, int $cityLimit = 10): array
    {
        $public = app(PublicStatsService::class)->snapshot();

        return [
            'kpis' => [
                'members_total' => Member::query()->count(),
                'members_active' => $public['members'],
                'students_active' => $public['students'],
                'teachers_and_volunteers' => $public['teachers_and_volunteers'],
                'events_published' => $public['events'],
                'initiatives' => $public['initiatives'],
            ],
            'by_gender' => $this->countsBy('gender'),
            'by_type' => $this->countsBy('membership_type'),
            'by_subscription' => $this->countsBy('subscription_status'),
            'by_status' => $this->countsBy('status'),
            'by_city' => $this->cities($cityLimit),
            'monthly' => $this->monthly($months),
        ];
    }

    /**
     * @return list<array{key: string, total: int}>
     */
    private function countsBy(string $column): array
    {
        if (! Schema::hasColumn('members', $column)) {
            return [];
        }

        return Member::query()
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key' => $row->{$column} ?: 'unknown',
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, total: int}>
     */
    private function cities(int $limit): array
    {
        $rows = $this->countsBy('city');
        if (count($rows) <= $limit) {
            return $rows;
        }

        $top = array_slice($rows, 0, $limit);
        $rest = array_slice($rows, $limit);
        $top[] = [
            'key' => 'other',
            'total' => (int) array_sum(array_column($rest, 'total')),
        ];

        return $top;
    }

    /**
     * @return list<array{key: string, total: int}>
     */
    private function monthly(int $months): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $counts = Member::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (Member $member) => $member->created_at?->format('Y-m') ?: 'unknown')
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
