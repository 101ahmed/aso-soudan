<?php

namespace App\Services;

use App\Models\FinanceRevenue;
use App\Models\Member;

class MemberSubscriptionRevenueSync
{
    public function sync(Member $member, ?int $recordedBy = null): ?FinanceRevenue
    {
        $amount = round((float) ($member->amount_paid ?? 0), 2);
        $existing = FinanceRevenue::query()
            ->withTrashed()
            ->where('member_id', $member->id)
            ->first();

        if ($amount <= 0) {
            $existing?->forceDelete();

            return null;
        }

        $name = trim($member->full_name) ?: trim($member->first_name.' '.$member->last_name);
        $status = $member->subscription_status ?: 'unpaid';

        $payload = [
            'member_id' => $member->id,
            'amount' => $amount,
            'source' => 'membership',
            'title_ar' => 'اشتراك — '.$name,
            'title_fr' => 'Cotisation — '.$name,
            'notes' => $status,
            'recorded_by' => $recordedBy ?: $existing?->recorded_by,
        ];

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update($payload);

            return $existing->fresh();
        }

        return FinanceRevenue::query()->create([
            ...$payload,
            'occurred_on' => now()->toDateString(),
        ]);
    }

    public function syncAll(?int $recordedBy = null): int
    {
        $count = 0;
        Member::query()
            ->where('amount_paid', '>', 0)
            ->orderBy('id')
            ->each(function (Member $member) use ($recordedBy, &$count) {
                $this->sync($member, $recordedBy);
                $count++;
            });

        FinanceRevenue::query()
            ->whereNotNull('member_id')
            ->where('source', 'membership')
            ->whereDoesntHave('member', fn ($q) => $q->where('amount_paid', '>', 0))
            ->forceDelete();

        return $count;
    }
}
