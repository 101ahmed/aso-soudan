<?php

namespace App\Support;

use App\Models\Department;
use App\Models\PresidentialArchiveItem;
use App\Models\PresidentialDirective;
use App\Models\PresidentialMeeting;
use App\Models\User;
use Illuminate\Support\Carbon;

class PresidentialWorkspace
{
    public static function nextReference(string $modelClass, string $prefix): string
    {
        $year = now()->year;
        $pattern = $prefix.'-'.$year.'-';
        $last = $modelClass::query()
            ->withTrashed()
            ->where('reference', 'like', $pattern.'%')
            ->orderByDesc('id')
            ->value('reference');

        $n = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches)) {
            $n = ((int) $matches[1]) + 1;
        }

        return $pattern.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    public static function nextDecisionNumber(): string
    {
        $year = now()->year;
        $pattern = 'ق-'.$year.'-';
        $last = PresidentialArchiveItem::query()
            ->withTrashed()
            ->where('decision_number', 'like', $pattern.'%')
            ->orderByDesc('id')
            ->value('decision_number');

        $n = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches)) {
            $n = ((int) $matches[1]) + 1;
        }

        return $pattern.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    public static function resolveManager(Department $department, ?int $userId = null): ?User
    {
        $department->loadMissing('users');

        if ($userId) {
            $match = $department->users->firstWhere('id', $userId);
            if ($match) {
                return $match;
            }
        }

        $primary = $department->users->first(fn (User $user) => (bool) ($user->pivot?->is_primary));

        return $primary ?: $department->users->first();
    }

    public static function syncMeetingArchive(PresidentialMeeting $meeting, ?int $userId = null): void
    {
        $date = $meeting->scheduled_at?->toDateString() ?: now()->toDateString();

        if (filled($meeting->minutes_ar) || filled($meeting->minutes_fr)) {
            self::upsertArchive([
                'category' => 'minutes',
                'title_ar' => 'محضر: '.$meeting->title_ar,
                'title_fr' => 'Procès-verbal : '.$meeting->title_fr,
                'body_ar' => $meeting->minutes_ar,
                'body_fr' => $meeting->minutes_fr,
                'decision_number' => $meeting->reference,
                'document_date' => $date,
                'source_type' => 'meeting',
                'source_id' => $meeting->id,
                'created_by' => $userId ?: $meeting->created_by,
            ]);
        }

        if (filled($meeting->decisions_ar) || filled($meeting->decisions_fr)) {
            $number = $meeting->decision_number ?: self::nextDecisionNumber();
            if (! $meeting->decision_number) {
                $meeting->forceFill(['decision_number' => $number])->saveQuietly();
            }

            self::upsertArchive([
                'category' => 'decision',
                'title_ar' => 'قرار: '.$meeting->title_ar,
                'title_fr' => 'Décision : '.$meeting->title_fr,
                'body_ar' => $meeting->decisions_ar,
                'body_fr' => $meeting->decisions_fr,
                'decision_number' => $meeting->decision_number ?: $number,
                'document_date' => $date,
                'source_type' => 'meeting',
                'source_id' => $meeting->id,
                'created_by' => $userId ?: $meeting->created_by,
            ]);
        }
    }

    public static function syncDirectiveArchive(PresidentialDirective $directive): void
    {
        $directive->loadMissing('department');
        $deptAr = $directive->department?->name_ar ?: '';
        $deptFr = $directive->department?->name_fr ?: '';

        self::upsertArchive([
            'category' => 'directive',
            'title_ar' => ($directive->title ?: 'توجيه رئاسي').($deptAr ? ' — '.$deptAr : ''),
            'title_fr' => ($directive->title ?: 'Directive présidentielle').($deptFr ? ' — '.$deptFr : ''),
            'body_ar' => $directive->body,
            'body_fr' => $directive->body,
            'decision_number' => $directive->reference,
            'document_date' => Carbon::parse($directive->created_at ?: now())->toDateString(),
            'source_type' => 'directive',
            'source_id' => $directive->id,
            'created_by' => $directive->sender_id,
        ]);
    }

    private static function upsertArchive(array $data): PresidentialArchiveItem
    {
        $existing = PresidentialArchiveItem::query()
            ->where('source_type', $data['source_type'])
            ->where('source_id', $data['source_id'])
            ->where('category', $data['category'])
            ->first();

        if ($existing) {
            $existing->update($data);

            return $existing->fresh();
        }

        return PresidentialArchiveItem::query()->create($data);
    }
}
