<?php

namespace Database\Seeders;

use App\Models\CouncilMember;
use Illuminate\Database\Seeder;

class ShuraMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'position_code' => 'president',
                'position_ar' => 'رئيس مجلس الشورى',
                'position_fr' => 'Président du Conseil de la Choura',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 10,
                'is_public' => true,
                'bio_ar' => 'يشرف على إدارة جلسات المجلس وتنسيق أعماله.',
                'bio_fr' => 'Supervise les séances et la coordination des travaux du conseil.',
            ],
            [
                'position_code' => 'vice_president',
                'position_ar' => 'نائب رئيس المجلس',
                'position_fr' => 'Vice-président',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 20,
                'is_public' => true,
                'bio_ar' => 'يدعم رئاسة المجلس ويتابع بعض الملفات المحالة.',
                'bio_fr' => 'Appuie la présidence et suit certains dossiers transmis.',
            ],
            [
                'position_code' => 'secretary',
                'position_ar' => 'مقرر المجلس',
                'position_fr' => 'Rapporteur',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 30,
                'is_public' => true,
                'bio_ar' => 'يتابع إعداد الوثائق والمحاضر المسموح بنشر ملخصاتها العامة.',
                'bio_fr' => 'Suit la préparation des documents et des comptes rendus publics autorisés.',
            ],
            [
                'position_code' => 'member',
                'position_ar' => 'عضو مجلس الشورى',
                'position_fr' => 'Membre du conseil',
                'first_name' => 'عضو',
                'last_name' => 'المجلس',
                'sort_order' => 40,
                'is_public' => true,
                'bio_ar' => 'يشارك في التشاور ودراسة المقترحات والتوصيات.',
                'bio_fr' => 'Participe à la concertation et à l’étude des propositions.',
            ],
        ];

        foreach ($members as $item) {
            CouncilMember::query()->updateOrCreate(
                [
                    'council_code' => 'shura',
                    'position_code' => $item['position_code'],
                    'sort_order' => $item['sort_order'],
                ],
                array_merge($item, [
                    'council_code' => 'shura',
                    'status' => 'active',
                    'started_at' => now()->toDateString(),
                ])
            );
        }
    }
}
