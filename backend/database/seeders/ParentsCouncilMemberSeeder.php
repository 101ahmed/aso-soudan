<?php

namespace Database\Seeders;

use App\Models\CouncilMember;
use Illuminate\Database\Seeder;

class ParentsCouncilMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'position_code' => 'president',
                'position_ar' => 'رئيس مجلس الآباء',
                'position_fr' => 'Président du Conseil des parents',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 10,
                'bio_ar' => 'يقود المجلس المستقل وينسق أعمال المكتب.',
                'bio_fr' => 'Dirige le conseil indépendant et coordonne le bureau.',
            ],
            [
                'position_code' => 'vice_president',
                'position_ar' => 'نائب رئيس المجلس',
                'position_fr' => 'Vice-président',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 20,
                'bio_ar' => 'ينوب عن الرئيس ويتابع الملفات المحالة.',
                'bio_fr' => 'Assure l’intérim et suit les dossiers transmis.',
            ],
            [
                'position_code' => 'treasurer',
                'position_ar' => 'أمين مال المجلس',
                'position_fr' => 'Trésorier',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 30,
                'bio_ar' => 'يتابع الجوانب المالية الخاصة بمجلس الآباء.',
                'bio_fr' => 'Suit les questions financières propres au Conseil des parents.',
            ],
            [
                'position_code' => 'secretary',
                'position_ar' => 'مقرر المجلس',
                'position_fr' => 'Rapporteur',
                'first_name' => 'يُعلن',
                'last_name' => 'لاحقاً',
                'sort_order' => 40,
                'bio_ar' => 'يتابع الاجتماعات والمحاضر والوثائق العامة.',
                'bio_fr' => 'Suit les réunions, les comptes rendus et les documents publics.',
            ],
        ];

        foreach ($members as $item) {
            CouncilMember::query()->updateOrCreate(
                [
                    'council_code' => 'parents',
                    'position_code' => $item['position_code'],
                ],
                array_merge($item, [
                    'council_code' => 'parents',
                    'status' => 'active',
                    'is_public' => true,
                    'started_at' => now()->toDateString(),
                ])
            );
        }
    }
}
