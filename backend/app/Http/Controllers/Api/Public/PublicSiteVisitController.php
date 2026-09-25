<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteVisitDay;
use App\Models\SiteVisitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicSiteVisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($this->isBot((string) $request->userAgent())) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $hash = hash('sha256', implode('|', [
            (string) $request->ip(),
            substr((string) $request->userAgent(), 0, 180),
            (string) config('app.key'),
        ]));
        $now = now();
        $today = $now->toDateString();

        DB::transaction(function () use ($hash, $now, $today) {
            $visitor = SiteVisitor::query()->where('visitor_hash', $hash)->lockForUpdate()->first();
            $isNewToday = ! $visitor || $visitor->last_seen_at === null
                || $visitor->last_seen_at->toDateString() !== $today;

            if ($visitor) {
                $visitor->update([
                    'page_views' => (int) $visitor->page_views + 1,
                    'last_seen_at' => $now,
                ]);
            } else {
                SiteVisitor::query()->create([
                    'visitor_hash' => $hash,
                    'page_views' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }

            $day = SiteVisitDay::query()->where('visited_on', $today)->lockForUpdate()->first();
            if (! $day) {
                SiteVisitDay::query()->create([
                    'visited_on' => $today,
                    'unique_visitors' => 1,
                    'page_views' => 1,
                ]);

                return;
            }

            $day->update([
                'page_views' => (int) $day->page_views + 1,
                'unique_visitors' => (int) $day->unique_visitors + ($isNewToday ? 1 : 0),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    private function isBot(string $agent): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram/i', $agent);
    }
}
