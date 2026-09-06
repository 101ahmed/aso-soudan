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
            ['code' => 'VICE_PRESIDENT', 'name_fr' => 'Vice-président', 'name_ar' => 'نائب رئيس الرابطة'],
            ['code' => 'GENERAL_SECRETARIAT', 'name_fr' => 'Secrétariat général', 'name_ar' => 'الأمانة العامة'],
            ['code' => 'ACADEMIC_SECRETARIAT', 'name_fr' => 'Secrétariat académique', 'name_ar' => 'الأمانة الأكاديمية'],
            ['code' => 'SOCIAL_SECRETARIAT', 'name_fr' => 'Secrétariat social', 'name_ar' => 'الأمانة الاجتماعية'],
            ['code' => 'FINANCE_SECRETARIAT', 'name_fr' => 'Secrétariat financier', 'name_ar' => 'الأمانة المالية'],
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
            ['code' => 'attendance.update', 'module' => 'attendance', 'name_fr' => 'Modifier présence', 'name_ar' => 'تعديل الحضور'],
            ['code' => 'attendance.delete', 'module' => 'attendance', 'name_fr' => 'Supprimer présence', 'name_ar' => 'حذف الحضور'],
            ['code' => 'report.view', 'module' => 'reports', 'name_fr' => 'Voir rapports', 'name_ar' => 'عرض التقارير'],
            ['code' => 'report.export', 'module' => 'reports', 'name_fr' => 'Exporter rapports', 'name_ar' => 'تصدير التقارير'],
            ['code' => 'statistics.view', 'module' => 'statistics', 'name_fr' => 'Voir statistiques', 'name_ar' => 'عرض الإحصاءات'],
            ['code' => 'member.view', 'module' => 'members', 'name_fr' => 'Voir adhérents', 'name_ar' => 'عرض الأعضاء'],
            ['code' => 'member.create', 'module' => 'members', 'name_fr' => 'Créer adhérent', 'name_ar' => 'إنشاء عضو'],
            ['code' => 'member.update', 'module' => 'members', 'name_fr' => 'Modifier adhérent', 'name_ar' => 'تعديل عضو'],
            ['code' => 'member.delete', 'module' => 'members', 'name_fr' => 'Supprimer adhérent', 'name_ar' => 'حذف عضو'],
            ['code' => 'member.message', 'module' => 'members', 'name_fr' => 'Envoyer un message aux adhérents', 'name_ar' => 'إرسال رسالة للأعضاء'],
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
            ['code' => 'event.delete', 'module' => 'events', 'name_fr' => 'Supprimer événement', 'name_ar' => 'حذف فعالية'],
            ['code' => 'event.publish', 'module' => 'events', 'name_fr' => 'Publier événement', 'name_ar' => 'نشر فعالية'],
            ['code' => 'help.view', 'module' => 'social', 'name_fr' => 'Voir demandes d’aide', 'name_ar' => 'عرض طلبات المساعدة'],
            ['code' => 'help.create', 'module' => 'social', 'name_fr' => 'Créer demande d’aide', 'name_ar' => 'إنشاء طلب مساعدة'],
            ['code' => 'help.update', 'module' => 'social', 'name_fr' => 'Modifier demande d’aide', 'name_ar' => 'تعديل طلب مساعدة'],
            ['code' => 'help.delete', 'module' => 'social', 'name_fr' => 'Supprimer demande d’aide', 'name_ar' => 'حذف طلب مساعدة'],
            ['code' => 'partner.view', 'module' => 'external', 'name_fr' => 'Voir partenaires', 'name_ar' => 'عرض الشركاء'],
            ['code' => 'partner.create', 'module' => 'external', 'name_fr' => 'Créer partenaire', 'name_ar' => 'إضافة شريك'],
            ['code' => 'partner.update', 'module' => 'external', 'name_fr' => 'Modifier partenaire', 'name_ar' => 'تعديل شريك'],
            ['code' => 'partner.delete', 'module' => 'external', 'name_fr' => 'Supprimer partenaire', 'name_ar' => 'حذف شريك'],
            ['code' => 'extcontact.view', 'module' => 'external', 'name_fr' => 'Voir demandes de contact', 'name_ar' => 'عرض طلبات التواصل'],
            ['code' => 'extcontact.create', 'module' => 'external', 'name_fr' => 'Créer demande de contact', 'name_ar' => 'إنشاء طلب تواصل'],
            ['code' => 'extcontact.update', 'module' => 'external', 'name_fr' => 'Modifier demande de contact', 'name_ar' => 'تعديل طلب تواصل'],
            ['code' => 'extcontact.delete', 'module' => 'external', 'name_fr' => 'Supprimer demande de contact', 'name_ar' => 'حذف طلب تواصل'],
            ['code' => 'inbox.view', 'module' => 'inbox', 'name_fr' => 'Voir messages de l’amanah', 'name_ar' => 'عرض رسائل الأمانة'],
            ['code' => 'inbox.create', 'module' => 'inbox', 'name_fr' => 'Créer message amanah', 'name_ar' => 'إنشاء رسالة أمانة'],
            ['code' => 'inbox.update', 'module' => 'inbox', 'name_fr' => 'Modifier message amanah', 'name_ar' => 'تعديل رسالة أمانة'],
            ['code' => 'inbox.delete', 'module' => 'inbox', 'name_fr' => 'Supprimer message amanah', 'name_ar' => 'حذف رسالة أمانة'],
            ['code' => 'finance.view', 'module' => 'finance', 'name_fr' => 'Voir finances', 'name_ar' => 'عرض المالية'],
            ['code' => 'finance.create', 'module' => 'finance', 'name_fr' => 'Créer opération financière', 'name_ar' => 'إنشاء عملية مالية'],
            ['code' => 'finance.update', 'module' => 'finance', 'name_fr' => 'Modifier opération financière', 'name_ar' => 'تعديل عملية مالية'],
            ['code' => 'finance.delete', 'module' => 'finance', 'name_fr' => 'Supprimer opération financière', 'name_ar' => 'حذف عملية مالية'],
            ['code' => 'decision.view', 'module' => 'media', 'name_fr' => 'Voir décisions', 'name_ar' => 'عرض القرارات'],
            ['code' => 'decision.create', 'module' => 'media', 'name_fr' => 'Créer décision', 'name_ar' => 'إنشاء قرار'],
            ['code' => 'decision.update', 'module' => 'media', 'name_fr' => 'Modifier décision', 'name_ar' => 'تعديل قرار'],
            ['code' => 'decision.delete', 'module' => 'media', 'name_fr' => 'Supprimer décision', 'name_ar' => 'حذف قرار'],
            ['code' => 'press.view', 'module' => 'media', 'name_fr' => 'Voir centre médias', 'name_ar' => 'عرض مركز الإعلام'],
            ['code' => 'press.create', 'module' => 'media', 'name_fr' => 'Créer contenu médias', 'name_ar' => 'إنشاء محتوى إعلامي'],
            ['code' => 'press.update', 'module' => 'media', 'name_fr' => 'Modifier contenu médias', 'name_ar' => 'تعديل محتوى إعلامي'],
            ['code' => 'press.delete', 'module' => 'media', 'name_fr' => 'Supprimer contenu médias', 'name_ar' => 'حذف محتوى إعلامي'],
            ['code' => 'shura.member.view', 'module' => 'shura', 'name_fr' => 'Voir membres Choura', 'name_ar' => 'عرض أعضاء الشورى'],
            ['code' => 'shura.member.manage', 'module' => 'shura', 'name_fr' => 'Gérer membres Choura', 'name_ar' => 'إدارة أعضاء الشورى'],
            ['code' => 'shura.meeting.view', 'module' => 'shura', 'name_fr' => 'Voir réunions Choura', 'name_ar' => 'عرض اجتماعات الشورى'],
            ['code' => 'shura.meeting.manage', 'module' => 'shura', 'name_fr' => 'Gérer réunions Choura', 'name_ar' => 'إدارة اجتماعات الشورى'],
            ['code' => 'shura.proposal.view', 'module' => 'shura', 'name_fr' => 'Voir propositions', 'name_ar' => 'عرض المقترحات'],
            ['code' => 'shura.proposal.create', 'module' => 'shura', 'name_fr' => 'Créer proposition', 'name_ar' => 'تقديم مقترح'],
            ['code' => 'shura.proposal.manage', 'module' => 'shura', 'name_fr' => 'Gérer propositions', 'name_ar' => 'إدارة المقترحات'],
            ['code' => 'shura.document.view', 'module' => 'shura', 'name_fr' => 'Voir documents Choura', 'name_ar' => 'عرض وثائق الشورى'],
            ['code' => 'shura.document.manage', 'module' => 'shura', 'name_fr' => 'Gérer documents Choura', 'name_ar' => 'إدارة وثائق الشورى'],
            ['code' => 'parents.registration.view', 'module' => 'parents', 'name_fr' => 'Voir inscriptions parents', 'name_ar' => 'عرض تسجيلات أولياء الأمور'],
            ['code' => 'parents.registration.manage', 'module' => 'parents', 'name_fr' => 'Gérer inscriptions parents', 'name_ar' => 'إدارة تسجيلات أولياء الأمور'],
            ['code' => 'parents.meeting.view', 'module' => 'parents', 'name_fr' => 'Voir réunions parents', 'name_ar' => 'عرض اجتماعات مجلس الآباء'],
            ['code' => 'parents.meeting.manage', 'module' => 'parents', 'name_fr' => 'Gérer réunions parents', 'name_ar' => 'إدارة اجتماعات مجلس الآباء'],
            ['code' => 'parents.survey.view', 'module' => 'parents', 'name_fr' => 'Voir enquêtes parents', 'name_ar' => 'عرض استبيانات مجلس الآباء'],
            ['code' => 'parents.survey.manage', 'module' => 'parents', 'name_fr' => 'Publier enquêtes parents', 'name_ar' => 'نشر استبيانات مجلس الآباء'],
            ['code' => 'president.meeting.view', 'module' => 'president', 'name_fr' => 'Voir réunions présidentielles', 'name_ar' => 'عرض اجتماعات الرئيس'],
            ['code' => 'president.meeting.manage', 'module' => 'president', 'name_fr' => 'Gérer réunions présidentielles', 'name_ar' => 'إدارة اجتماعات الرئيس'],
            ['code' => 'president.directive.view', 'module' => 'president', 'name_fr' => 'Voir directives présidentielles', 'name_ar' => 'عرض التوجيهات الرئاسية'],
            ['code' => 'president.directive.manage', 'module' => 'president', 'name_fr' => 'Envoyer des directives présidentielles', 'name_ar' => 'إرسال التوجيهات الرئاسية'],
            ['code' => 'president.archive.view', 'module' => 'president', 'name_fr' => 'Voir l’archive présidentielle', 'name_ar' => 'عرض الأرشيف الرئاسي'],
            ['code' => 'president.archive.manage', 'module' => 'president', 'name_fr' => 'Gérer l’archive présidentielle', 'name_ar' => 'إدارة الأرشيف الرئاسي'],
            ['code' => 'president.directive.inbox', 'module' => 'president', 'name_fr' => 'Boîte des directives présidentielles', 'name_ar' => 'صندوق توجيهات الرئيس'],
            ['code' => 'secretariat.directive.view', 'module' => 'secretariat', 'name_fr' => 'Voir les directives entre amanahs', 'name_ar' => 'عرض توجيهات الأمانات'],
            ['code' => 'secretariat.directive.send', 'module' => 'secretariat', 'name_fr' => 'Envoyer une directive aux amanahs', 'name_ar' => 'إرسال توجيه إلى الأمانات'],
            ['code' => 'lesson_prep.view', 'module' => 'lesson_prep', 'name_fr' => 'Voir les préparations de cours', 'name_ar' => 'عرض تحضير الحصص'],
            ['code' => 'lesson_prep.create', 'module' => 'lesson_prep', 'name_fr' => 'Créer une préparation de cours', 'name_ar' => 'إنشاء تحضير حصة'],
            ['code' => 'lesson_prep.update', 'module' => 'lesson_prep', 'name_fr' => 'Modifier une préparation de cours', 'name_ar' => 'تعديل تحضير حصة'],
            ['code' => 'lesson_prep.delete', 'module' => 'lesson_prep', 'name_fr' => 'Supprimer une préparation de cours', 'name_ar' => 'حذف تحضير حصة'],
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
            'event.create', 'event.update', 'event.delete', 'event.publish',
        ];

        $contentOnlyIds = Permission::query()->whereIn('code', $contentCodes)->pluck('id');

        Role::query()->where('code', 'CONTENT_EDITOR')->first()?->permissions()->sync($contentOnlyIds);

        $presidencyPermissions = Permission::query()->whereIn('code', [
            'news.view', 'announcement.view', 'gallery.view', 'report.view', 'report.export', 'statistics.view', 'member.view', 'help.view', 'partner.view', 'extcontact.view', 'inbox.view', 'finance.view', 'decision.view', 'press.view',
            'president.meeting.view', 'president.meeting.manage',
            'president.directive.view', 'president.directive.manage',
            'president.archive.view', 'president.archive.manage',
            'president.directive.inbox',
        ])->pluck('id');

        Role::query()->where('code', 'PRESIDENT')->first()?->permissions()->sync($presidencyPermissions);
        Role::query()->where('code', 'VICE_PRESIDENT')->first()?->permissions()->sync($presidencyPermissions);

        $secretariatManagerCodes = array_merge($contentCodes, [
            'user.view',
            'report.view', 'report.export',
            'inbox.view', 'inbox.create', 'inbox.update', 'inbox.delete',
            'president.directive.inbox',
            'secretariat.directive.view', 'secretariat.directive.send',
        ]);

        $managerRoleMap = [
            'GENERAL_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'decision.view', 'decision.create', 'decision.update', 'decision.delete',
            ]),
            'ACADEMIC_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'student.view', 'student.create', 'student.update', 'student.delete',
                'teacher.view', 'teacher.create', 'teacher.update',
                'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete',
                'lesson_prep.view', 'lesson_prep.create', 'lesson_prep.update', 'lesson_prep.delete',
            ]),
            'SOCIAL_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'help.view', 'help.create', 'help.update', 'help.delete',
            ]),
            'FINANCE_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'finance.view', 'finance.create', 'finance.update', 'finance.delete',
            ]),
            'MEDIA_SECRETARIAT' => array_merge($secretariatManagerCodes, [
                'decision.view', 'decision.create', 'decision.update', 'decision.delete',
                'press.view', 'press.create', 'press.update', 'press.delete',
            ]),
            'WOMEN_CHILDREN' => $secretariatManagerCodes,
            'STATISTICS_SECRETARIAT' => array_merge($contentCodes, [
                'statistics.view', 'report.view', 'report.export',
                'member.view', 'member.create', 'member.update', 'member.delete', 'member.message',
                'student.view', 'teacher.view', 'attendance.view',
                'inbox.view', 'inbox.create', 'inbox.update', 'inbox.delete',
                'president.directive.inbox',
                'secretariat.directive.view', 'secretariat.directive.send',
            ]),
            'EXTERNAL_RELATIONS' => array_merge($secretariatManagerCodes, [
                'partner.view', 'partner.create', 'partner.update', 'partner.delete',
                'extcontact.view', 'extcontact.create', 'extcontact.update', 'extcontact.delete',
            ]),
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
                'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete',
                'lesson_prep.view', 'lesson_prep.create', 'lesson_prep.update', 'lesson_prep.delete',
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

        $parentsCodes = [
            'parents.registration.view', 'parents.registration.manage',
            'parents.meeting.view', 'parents.meeting.manage',
            'parents.survey.view', 'parents.survey.manage',
        ];
        Role::query()->where('code', 'PARENTS_COUNCIL')->first()?->permissions()->sync(
            Permission::query()->whereIn('code', $parentsCodes)->pluck('id')
        );
    }
}
