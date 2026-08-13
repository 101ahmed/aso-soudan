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
            ['code' => 'CONTENT_EDITOR', 'name_fr' => 'Éditeur de contenu', 'name_ar' => 'محرر محتوى'],
            ['code' => 'SHURA_COUNCIL', 'name_fr' => 'Conseil de la Choura', 'name_ar' => 'مجلس الشورى'],
            ['code' => 'SHURA_PRESIDENT', 'name_fr' => 'Président de la Choura', 'name_ar' => 'رئيس مجلس الشورى'],
            ['code' => 'SHURA_VICE_PRESIDENT', 'name_fr' => 'Vice-président Choura', 'name_ar' => 'نائب رئيس مجلس الشورى'],
            ['code' => 'SHURA_SECRETARY', 'name_fr' => 'Rapporteur Choura', 'name_ar' => 'مقرر مجلس الشورى'],
            ['code' => 'SHURA_MEMBER', 'name_fr' => 'Membre Choura', 'name_ar' => 'عضو مجلس الشورى'],
            ['code' => 'SHURA_CONTENT_EDITOR', 'name_fr' => 'Éditeur contenu Choura', 'name_ar' => 'محرر محتوى الشورى'],
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
            ['code' => 'student.delete', 'module' => 'students', 'name_fr' => 'Supprimer étudiant', 'name_ar' => 'حذف طالب'],
            ['code' => 'teacher.view', 'module' => 'teachers', 'name_fr' => 'Voir enseignants', 'name_ar' => 'عرض المعلمين'],
            ['code' => 'teacher.create', 'module' => 'teachers', 'name_fr' => 'Créer enseignant', 'name_ar' => 'إنشاء معلم'],
            ['code' => 'teacher.update', 'module' => 'teachers', 'name_fr' => 'Modifier enseignant', 'name_ar' => 'تعديل معلم'],
            ['code' => 'attendance.view', 'module' => 'attendance', 'name_fr' => 'Voir présence', 'name_ar' => 'عرض الحضور'],
            ['code' => 'attendance.create', 'module' => 'attendance', 'name_fr' => 'Saisir présence', 'name_ar' => 'تسجيل الحضور'],
            ['code' => 'report.view', 'module' => 'reports', 'name_fr' => 'Voir rapports', 'name_ar' => 'عرض التقارير'],
            ['code' => 'report.export', 'module' => 'reports', 'name_fr' => 'Exporter rapports', 'name_ar' => 'تصدير التقارير'],
            ['code' => 'statistics.view', 'module' => 'statistics', 'name_fr' => 'Voir statistiques', 'name_ar' => 'عرض الإحصاءات'],
            ['code' => 'news.view', 'module' => 'news', 'name_fr' => 'Voir actualités', 'name_ar' => 'عرض الأخبار'],
            ['code' => 'news.create', 'module' => 'news', 'name_fr' => 'Créer actualité', 'name_ar' => 'إنشاء خبر'],
            ['code' => 'news.update', 'module' => 'news', 'name_fr' => 'Modifier actualité', 'name_ar' => 'تعديل خبر'],
            ['code' => 'news.delete', 'module' => 'news', 'name_fr' => 'Supprimer actualité', 'name_ar' => 'حذف خبر'],
            ['code' => 'news.publish', 'module' => 'news', 'name_fr' => 'Publier actualité', 'name_ar' => 'نشر خبر'],
            ['code' => 'announcement.view', 'module' => 'announcements', 'name_fr' => 'Voir annonces', 'name_ar' => 'عرض الإعلانات'],
            ['code' => 'announcement.create', 'module' => 'announcements', 'name_fr' => 'Créer annonce', 'name_ar' => 'إنشاء إعلان'],
            ['code' => 'announcement.update', 'module' => 'announcements', 'name_fr' => 'Modifier annonce', 'name_ar' => 'تعديل إعلان'],
            ['code' => 'announcement.delete', 'module' => 'announcements', 'name_fr' => 'Supprimer annonce', 'name_ar' => 'حذف إعلان'],
            ['code' => 'announcement.publish', 'module' => 'announcements', 'name_fr' => 'Publier annonce', 'name_ar' => 'نشر إعلان'],
            ['code' => 'gallery.view', 'module' => 'gallery', 'name_fr' => 'Voir galerie', 'name_ar' => 'عرض المعرض'],
            ['code' => 'gallery.manage', 'module' => 'gallery', 'name_fr' => 'Gérer galerie', 'name_ar' => 'إدارة المعرض'],
            ['code' => 'gallery.publish', 'module' => 'gallery', 'name_fr' => 'Publier album', 'name_ar' => 'نشر ألبوم'],
            ['code' => 'content.review', 'module' => 'content', 'name_fr' => 'Réviser contenu', 'name_ar' => 'مراجعة المحتوى'],
            ['code' => 'event.create', 'module' => 'events', 'name_fr' => 'Créer événement', 'name_ar' => 'إنشاء فعالية'],
            ['code' => 'event.update', 'module' => 'events', 'name_fr' => 'Modifier événement', 'name_ar' => 'تعديل فعالية'],
            ['code' => 'shura.member.view', 'module' => 'shura', 'name_fr' => 'Voir membres Choura', 'name_ar' => 'عرض أعضاء الشورى'],
            ['code' => 'shura.member.manage', 'module' => 'shura', 'name_fr' => 'Gérer membres Choura', 'name_ar' => 'إدارة أعضاء الشورى'],
            ['code' => 'shura.meeting.view', 'module' => 'shura', 'name_fr' => 'Voir réunions Choura', 'name_ar' => 'عرض اجتماعات الشورى'],
            ['code' => 'shura.meeting.manage', 'module' => 'shura', 'name_fr' => 'Gérer réunions Choura', 'name_ar' => 'إدارة اجتماعات الشورى'],
            ['code' => 'shura.proposal.view', 'module' => 'shura', 'name_fr' => 'Voir propositions', 'name_ar' => 'عرض المقترحات'],
            ['code' => 'shura.proposal.create', 'module' => 'shura', 'name_fr' => 'Créer proposition', 'name_ar' => 'تقديم مقترح'],
            ['code' => 'shura.proposal.manage', 'module' => 'shura', 'name_fr' => 'Gérer propositions', 'name_ar' => 'إدارة المقترحات'],
            ['code' => 'shura.document.view', 'module' => 'shura', 'name_fr' => 'Voir documents Choura', 'name_ar' => 'عرض وثائق الشورى'],
            ['code' => 'shura.document.manage', 'module' => 'shura', 'name_fr' => 'Gérer documents Choura', 'name_ar' => 'إدارة وثائق الشورى'],
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

        $contentCodes = [
            'news.view', 'news.create', 'news.update', 'news.delete', 'news.publish',
            'announcement.view', 'announcement.create', 'announcement.update', 'announcement.delete', 'announcement.publish',
            'gallery.view', 'gallery.manage', 'gallery.publish',
            'content.review',
            'event.create', 'event.update',
        ];

        $contentOnlyIds = Permission::query()->whereIn('code', $contentCodes)->pluck('id');

        Role::query()->where('code', 'CONTENT_EDITOR')->first()?->permissions()->sync($contentOnlyIds);

        Role::query()->where('code', 'PRESIDENT')->first()?->permissions()->sync(
            Permission::query()->whereIn('code', [
                'news.view', 'announcement.view', 'gallery.view', 'report.view', 'statistics.view',
            ])->pluck('id')
        );

        $secretariatManagerCodes = array_merge($contentCodes, [
            'user.view',
            'report.view',
        ]);

        $managerRoleMap = [
            'GENERAL_SECRETARIAT' => $secretariatManagerCodes,
            'ACADEMIC_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'student.view', 'student.create', 'student.update', 'student.delete',
                'teacher.view', 'teacher.create', 'teacher.update',
                'attendance.view', 'attendance.create',
            ]),
            'SOCIAL_SECRETARIAT' => $secretariatManagerCodes,
            'MEDIA_SECRETARIAT' => $secretariatManagerCodes,
            'WOMEN_CHILDREN' => $secretariatManagerCodes,
            'STATISTICS_SECRETARIAT' => array_merge($contentCodes, [
                'statistics.view', 'report.view', 'report.export',
                'student.view', 'teacher.view', 'attendance.view',
            ]),
            'EXTERNAL_RELATIONS' => $secretariatManagerCodes,
            'SPORTS_SECRETARIAT' => $secretariatManagerCodes,
        ];

        foreach ($managerRoleMap as $roleCode => $codes) {
            Role::query()->where('code', $roleCode)->first()?->permissions()->sync(
                Permission::query()->whereIn('code', $codes)->pluck('id')
            );
        }

        $teacher = Role::query()->where('code', 'TEACHER')->first();
        $teacher?->permissions()->sync(
            Permission::query()->whereIn('code', [
                'student.view', 'student.create', 'student.update', 'student.delete',
                'attendance.view', 'attendance.create',
            ])->pluck('id')
        );

        $shuraContent = $contentCodes;
        $shuraMember = [
            'shura.member.view',
            'shura.meeting.view',
            'shura.proposal.view', 'shura.proposal.create',
            'shura.document.view',
            'news.view', 'announcement.view', 'gallery.view',
        ];
        $shuraSecretary = array_merge($shuraMember, [
            'shura.meeting.manage',
            'shura.document.manage',
            'announcement.create', 'announcement.update', 'announcement.publish',
            'gallery.manage', 'gallery.publish',
            'news.create', 'news.update',
        ]);
        $shuraVice = array_merge($shuraSecretary, [
            'news.publish', 'news.delete',
            'announcement.delete',
            'shura.proposal.manage',
            'content.review',
        ]);
        $shuraPresident = array_merge($shuraVice, [
            'shura.member.manage',
            'report.view',
        ]);

        $shuraMap = [
            'SHURA_PRESIDENT' => $shuraPresident,
            'SHURA_VICE_PRESIDENT' => $shuraVice,
            'SHURA_SECRETARY' => $shuraSecretary,
            'SHURA_MEMBER' => $shuraMember,
            'SHURA_CONTENT_EDITOR' => $shuraContent,
            'SHURA_COUNCIL' => $shuraMember,
        ];

        foreach ($shuraMap as $roleCode => $codes) {
            Role::query()->where('code', $roleCode)->first()?->permissions()->sync(
                Permission::query()->whereIn('code', $codes)->pluck('id')
            );
        }
    }
}
