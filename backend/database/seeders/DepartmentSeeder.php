<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'general',
                'name_ar' => 'الأمانة العامة',
                'name_fr' => 'Secrétariat général',
                'sort_order' => 10,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين الأمانة العامة',
                'officer_title_fr' => 'Secrétaire général',
                'officer_bio_ar' => 'يشرف على التنسيق الإداري العام ومتابعة قرارات المكتب التنفيذي.',
                'officer_bio_fr' => 'Supervise la coordination administrative et le suivi des décisions du bureau.',
                'officer_email' => 'general@acs-rennes.fr',
            ],
            [
                'code' => 'academic',
                'name_ar' => 'الأمانة الأكاديمية',
                'name_fr' => 'Secrétariat académique',
                'sort_order' => 20,
                'officer_name_ar' => 'إبراهيم سليمان',
                'officer_name_fr' => 'Ibrahim Suleiman',
                'officer_title_ar' => 'أمين الأمانة الأكاديمية',
                'officer_title_fr' => 'Secrétaire académique',
                'officer_bio_ar' => 'يشرف على التسجيلات والبرامج التعليمية ومتابعة الطلاب والمعلمين.',
                'officer_bio_fr' => 'Supervise les inscriptions, les programmes et le suivi pédagogique.',
                'officer_email' => 'hima171221@gmail.com',
            ],
            [
                'code' => 'social',
                'name_ar' => 'الأمانة الاجتماعية',
                'name_fr' => 'Secrétariat social',
                'sort_order' => 30,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين الأمانة الاجتماعية',
                'officer_title_fr' => 'Secrétaire social',
                'officer_bio_ar' => 'يشرف على المبادرات الاجتماعية وبرامج التكافل.',
                'officer_bio_fr' => 'Supervise les initiatives sociales et les programmes de solidarité.',
                'officer_email' => 'social@acs-rennes.fr',
            ],
            [
                'code' => 'media',
                'name_ar' => 'الأمانة الإعلامية',
                'name_fr' => 'Secrétariat médias',
                'sort_order' => 40,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين الأمانة الإعلامية',
                'officer_title_fr' => 'Secrétaire médias',
                'officer_bio_ar' => 'يشرف على الأخبار والتوثيق والتواصل الإعلامي.',
                'officer_bio_fr' => 'Supervise l’actualité, la documentation et la communication.',
                'officer_email' => 'media@acs-rennes.fr',
            ],
            [
                'code' => 'women-children',
                'name_ar' => 'شؤون المرأة والطفل',
                'name_fr' => 'Femmes & Enfants',
                'sort_order' => 50,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'مسؤولة شؤون المرأة والطفل',
                'officer_title_fr' => 'Responsable Femmes & Enfants',
                'officer_bio_ar' => 'تشرف على برامج المرأة والأسرة والطفل.',
                'officer_bio_fr' => 'Supervise les programmes femmes, famille et enfants.',
                'officer_email' => 'women-children@acs-rennes.fr',
            ],
            [
                'code' => 'statistics',
                'name_ar' => 'أمانة الإحصاء',
                'name_fr' => 'Secrétariat statistiques',
                'sort_order' => 60,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين أمانة الإحصاء',
                'officer_title_fr' => 'Secrétaire statistiques',
                'officer_bio_ar' => 'يشرف على جمع البيانات وإعداد المؤشرات العامة.',
                'officer_bio_fr' => 'Supervise la collecte de données et les indicateurs publics.',
                'officer_email' => 'statistics@acs-rennes.fr',
            ],
            [
                'code' => 'external-relations',
                'name_ar' => 'الأمانة الخارجية',
                'name_fr' => 'Relations extérieures',
                'sort_order' => 70,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين الأمانة الخارجية',
                'officer_title_fr' => 'Secrétaire aux relations extérieures',
                'officer_bio_ar' => 'يشرف على الشراكات واللقاءات الخارجية.',
                'officer_bio_fr' => 'Supervise les partenariats et les rencontres externes.',
                'officer_email' => 'external@acs-rennes.fr',
            ],
            [
                'code' => 'sports',
                'name_ar' => 'الأمانة الرياضية',
                'name_fr' => 'Secrétariat sportif',
                'sort_order' => 80,
                'officer_name_ar' => 'يُعلن لاحقاً',
                'officer_name_fr' => 'À annoncer',
                'officer_title_ar' => 'أمين الأمانة الرياضية',
                'officer_title_fr' => 'Secrétaire sportif',
                'officer_bio_ar' => 'يشرف على البرامج والأنشطة الرياضية للرابطة.',
                'officer_bio_fr' => 'Supervise les programmes et activités sportives de la Rabta.',
                'officer_email' => 'sports@acs-rennes.fr',
            ],
            [
                'code' => 'shura',
                'name_ar' => 'مجلس الشورى',
                'name_fr' => 'Conseil de la Choura',
                'sort_order' => 90,
            ],
        ];

        foreach ($items as $item) {
            $code = $item['code'];
            unset($item['code']);
            Department::query()->updateOrCreate(
                ['code' => $code],
                array_merge($item, [
                    'is_active' => true,
                    'officer_is_public' => true,
                ])
            );
        }
    }
}
