<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisitDay;
use App\Models\SiteVisitor;
use Illuminate\Http\JsonResponse;

class AdminSiteVisitController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();
        $todayRow = SiteVisitDay::query()->where('visited_on', $today)->first();
        $weekFrom = now()->subDays(6)->startOfDay();
        $weekViews = (int) SiteVisitDay::query()
            ->where('visited_on', '>=', $weekFrom->toDateString())
            ->sum('page_views');

        return response()->json([
            'today_visitors' => (int) ($todayRow?->unique_visitors ?? 0),
            'today_views' => (int) ($todayRow?->page_views ?? 0),
            'week_visitors' => (int) SiteVisitor::query()
                ->where('last_seen_at', '>=', $weekFrom)
                ->count(),
            'week_views' => $weekViews,
            'total_visitors' => (int) SiteVisitor::query()->count(),
            'total_views' => (int) SiteVisitor::query()->sum('page_views'),
        ]);
    }
}
