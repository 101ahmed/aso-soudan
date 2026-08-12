<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'general', 'name_ar' => 'الأمانة العامة', 'name_fr' => 'Secrétariat général', 'sort_order' => 10],
            ['code' => 'academic', 'name_ar' => 'الأمانة الأكاديمية', 'name_fr' => 'Secrétariat académique', 'sort_order' => 20],
            ['code' => 'social', 'name_ar' => 'الأمانة الاجتماعية', 'name_fr' => 'Secrétariat social', 'sort_order' => 30],
            ['code' => 'media', 'name_ar' => 'الأمانة الإعلامية', 'name_fr' => 'Secrétariat médias', 'sort_order' => 40],
            ['code' => 'women-children', 'name_ar' => 'شؤون المرأة والطفل', 'name_fr' => 'Femmes & Enfants', 'sort_order' => 50],
            ['code' => 'statistics', 'name_ar' => 'أمانة الإحصاء', 'name_fr' => 'Secrétariat statistiques', 'sort_order' => 60],
            ['code' => 'external-relations', 'name_ar' => 'الأمانة الخارجية', 'name_fr' => 'Relations extérieures', 'sort_order' => 70],
            ['code' => 'sports', 'name_ar' => 'الأمانة الرياضية', 'name_fr' => 'Secrétariat sportif', 'sort_order' => 80],
        ];

        foreach ($items as $item) {
            Department::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name_ar' => $item['name_ar'],
                    'name_fr' => $item['name_fr'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}
