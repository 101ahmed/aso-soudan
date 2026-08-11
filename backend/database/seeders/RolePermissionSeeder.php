<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'SUPER_ADMIN', 'name_fr' => 'Super Admin', 'name_ar' => 'مشرف عام'],
            ['code' => 'PRESIDENT', 'name_fr' => 'Président', 'name_ar' => 'رئيس الرابطة'],
            ['code' => 'GENERAL_SECRETARIAT', 'name_fr' => 'Secrétariat général', 'name_ar' => 'الأمانة العامة'],
            ['code' => 'ACADEMIC_SECRETARIAT', 'name_fr' => 'Secrétariat académique', 'name_ar' => 'الأمانة الأكاديمية'],
            ['code' => 'SOCIAL_SECRETARIAT', 'name_fr' => 'Secrétariat social', 'name_ar' => 'الأمانة الاجتماعية'],
            ['code' => 'MEDIA_SECRETARIAT', 'name_fr' => 'Secrétariat médias', 'name_ar' => 'الأمانة الإعلامية'],
            ['code' => 'WOMEN_CHILDREN', 'name_fr' => 'Femmes & Enfants', 'name_ar' => 'شؤون المرأة والطفل'],
            ['code' => 'STATISTICS_SECRETARIAT', 'name_fr' => 'Secrétariat statistiques', 'name_ar' => 'أمانة الإحصاء'],
            ['code' => 'EXTERNAL_RELATIONS', 'name_fr' => 'Relations extérieures', 'name_ar' => 'الأمانة الخارجية'],
            ['code' => 'SPORTS_SECRETARIAT', 'name_fr' => 'Secrétariat sportif', 'name_ar' => 'الأمانة الرياضية'],
            ['code' => 'SHURA_COUNCIL', 'name_fr' => 'Conseil de la Choura', 'name_ar' => 'مجلس الشورى'],
            ['code' => 'PARENTS_COUNCIL', 'name_fr' => 'Conseil des parents', 'name_ar' => 'مجلس الآباء'],
            ['code' => 'TEACHER', 'name_fr' => 'Enseignant', 'name_ar' => 'معلم'],
            ['code' => 'PARENT', 'name_fr' => 'Parent', 'name_ar' => 'ولي أمر'],
            ['code' => 'MEMBER', 'name_fr' => 'Membre', 'name_ar' => 'عضو'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                [
                    'name_fr' => $role['name_fr'],
                    'name_ar' => $role['name_ar'],
                    'is_system' => true,
                ]
            );
        }

        $permissions = [
            ['code' => 'user.view', 'module' => 'users', 'name_fr' => 'Voir utilisateurs', 'name_ar' => 'عرض المستخدمين'],
            ['code' => 'user.create', 'module' => 'users', 'name_fr' => 'Créer utilisateur', 'name_ar' => 'إنشاء مستخدم'],
            ['code' => 'user.update', 'module' => 'users', 'name_fr' => 'Modifier utilisateur', 'name_ar' => 'تعديل مستخدم'],
            ['code' => 'user.delete', 'module' => 'users', 'name_fr' => 'Supprimer utilisateur', 'name_ar' => 'حذف مستخدم'],
            ['code' => 'role.view', 'module' => 'roles', 'name_fr' => 'Voir rôles', 'name_ar' => 'عرض الأدوار'],
            ['code' => 'role.assign', 'module' => 'roles', 'name_fr' => 'Assigner rôles', 'name_ar' => 'تعيين الأدوار'],
            ['code' => 'permission.view', 'module' => 'roles', 'name_fr' => 'Voir permissions', 'name_ar' => 'عرض الصلاحيات'],
            ['code' => 'permission.assign', 'module' => 'roles', 'name_fr' => 'Assigner permissions', 'name_ar' => 'تعيين الصلاحيات'],
            ['code' => 'student.view', 'module' => 'students', 'name_fr' => 'Voir étudiants', 'name_ar' => 'عرض الطلاب'],
            ['code' => 'student.create', 'module' => 'students', 'name_fr' => 'Créer étudiant', 'name_ar' => 'إنشاء طالب'],
            ['code' => 'student.update', 'module' => 'students', 'name_fr' => 'Modifier étudiant', 'name_ar' => 'تعديل طالب'],
            ['code' => 'teacher.view', 'module' => 'teachers', 'name_fr' => 'Voir enseignants', 'name_ar' => 'عرض المعلمين'],
            ['code' => 'teacher.create', 'module' => 'teachers', 'name_fr' => 'Créer enseignant', 'name_ar' => 'إنشاء معلم'],
            ['code' => 'attendance.view', 'module' => 'attendance', 'name_fr' => 'Voir présence', 'name_ar' => 'عرض الحضور'],
            ['code' => 'attendance.create', 'module' => 'attendance', 'name_fr' => 'Saisir présence', 'name_ar' => 'تسجيل الحضور'],
            ['code' => 'report.view', 'module' => 'reports', 'name_fr' => 'Voir rapports', 'name_ar' => 'عرض التقارير'],
            ['code' => 'report.export', 'module' => 'reports', 'name_fr' => 'Exporter rapports', 'name_ar' => 'تصدير التقارير'],
            ['code' => 'statistics.view', 'module' => 'statistics', 'name_fr' => 'Voir statistiques', 'name_ar' => 'عرض الإحصاءات'],
            ['code' => 'news.create', 'module' => 'news', 'name_fr' => 'Créer actualité', 'name_ar' => 'إنشاء خبر'],
            ['code' => 'news.publish', 'module' => 'news', 'name_fr' => 'Publier actualité', 'name_ar' => 'نشر خبر'],
            ['code' => 'event.create', 'module' => 'events', 'name_fr' => 'Créer événement', 'name_ar' => 'إنشاء فعالية'],
            ['code' => 'event.update', 'module' => 'events', 'name_fr' => 'Modifier événement', 'name_ar' => 'تعديل فعالية'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                $permission
            );
        }

        $allPermissionIds = Permission::query()->pluck('id');

        Role::query()->where('code', 'SUPER_ADMIN')
            ->first()
            ?->permissions()
            ->sync($allPermissionIds);

        $academic = Role::query()->where('code', 'ACADEMIC_SECRETARIAT')->first();
        $academic?->permissions()->sync(
            Permission::query()->whereIn('code', [
                'user.view',
                'student.view', 'student.create', 'student.update',
                'teacher.view', 'teacher.create',
                'attendance.view', 'attendance.create',
                'report.view',
            ])->pluck('id')
        );

        $stats = Role::query()->where('code', 'STATISTICS_SECRETARIAT')->first();
        $stats?->permissions()->sync(
            Permission::query()->whereIn('code', [
                'statistics.view', 'report.view', 'report.export',
                'student.view', 'teacher.view', 'attendance.view',
            ])->pluck('id')
        );

        $teacher = Role::query()->where('code', 'TEACHER')->first();
        $teacher?->permissions()->sync(
            Permission::query()->whereIn('code', [
                'student.view', 'attendance.view', 'attendance.create',
            ])->pluck('id')
        );
    }
}
