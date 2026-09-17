<?php

use App\Http\Controllers\Api\Admin\AdminAcademicAttendanceController;
use App\Http\Controllers\Api\Admin\AdminAcademicClassStaffController;
use App\Http\Controllers\Api\Admin\AdminAcademicExamController;
use App\Http\Controllers\Api\Admin\AdminAcademicStudentController;
use App\Http\Controllers\Api\Admin\AdminAcademicTeacherController;
use App\Http\Controllers\Api\Admin\AdminAcademicTimetableController;
use App\Http\Controllers\Api\Admin\AdminAlbumController;
use App\Http\Controllers\Api\Admin\AdminAnnouncementController;
use App\Http\Controllers\Api\Admin\AdminDepartmentController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminExternalContactRequestController;
use App\Http\Controllers\Api\Admin\AdminExternalDocumentController;
use App\Http\Controllers\Api\Admin\AdminExternalPartnerController;
use App\Http\Controllers\Api\Admin\AdminFinanceController;
use App\Http\Controllers\Api\Admin\AdminFinanceDocumentController;
use App\Http\Controllers\Api\Admin\AdminLessonPreparationController;
use App\Http\Controllers\Api\Admin\AdminMediaCenterController;
use App\Http\Controllers\Api\Admin\AdminMediaDecisionController;
use App\Http\Controllers\Api\Admin\AdminNewsController;
use App\Http\Controllers\Api\Admin\AdminParentsController;
use App\Http\Controllers\Api\Admin\AdminPresidentController;
use App\Http\Controllers\Api\Admin\AdminSecretariatDirectiveController;
use App\Http\Controllers\Api\Admin\AdminSecretariatMeetingOutputController;
use App\Http\Controllers\Api\Admin\AdminSecretariatMessageController;
use App\Http\Controllers\Api\Admin\AdminSecretariatReportController;
use App\Http\Controllers\Api\Admin\AdminShuraController;
use App\Http\Controllers\Api\Admin\AdminSiteContentController;
use App\Http\Controllers\Api\Admin\AdminSocialHelpRequestController;
use App\Http\Controllers\Api\Admin\AdminSocialVisitController;
use App\Http\Controllers\Api\Admin\AdminSportsController;
use App\Http\Controllers\Api\Admin\AdminWomenMemberController;
use App\Http\Controllers\Api\Admin\AdminStatisticsMemberController;
use App\Http\Controllers\Api\Admin\AdminTeacherRegisterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\Public\PublicContactController;
use App\Http\Controllers\Api\Public\PublicContentController;
use App\Http\Controllers\Api\Public\PublicDepartmentPhotoController;
use App\Http\Controllers\Api\Public\PublicExternalController;
use App\Http\Controllers\Api\Public\PublicFinanceController;
use App\Http\Controllers\Api\Public\PublicGeocodeController;
use App\Http\Controllers\Api\Public\PublicHelpRequestController;
use App\Http\Controllers\Api\Public\PublicMemberController;
use App\Http\Controllers\Api\Public\PublicParentsController;
use App\Http\Controllers\Api\Public\PublicPresidentController;
use App\Http\Controllers\Api\Public\PublicSecretariatMeetingOutputController;
use App\Http\Controllers\Api\Public\PublicSecretariatMessageController;
use App\Http\Controllers\Api\Public\PublicShuraController;
use App\Http\Controllers\Api\Public\PublicSportsController;
use App\Http\Controllers\Api\Public\PublicStoredFileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('public')->group(function () {
    Route::get('/departments', [PublicContentController::class, 'departments']);
    Route::get('/departments/{code}/{role}-photo', [PublicDepartmentPhotoController::class, 'show'])
        ->where('role', 'officer|deputy');
    Route::get('/files/{uuid}', [PublicStoredFileController::class, 'show'])
        ->where('uuid', '[0-9a-fA-F-]{36}');
    Route::get('/secretariats/{code}/feed', [PublicContentController::class, 'secretariatFeed']);
    Route::get('/secretariats/{code}/meeting-outputs', [PublicSecretariatMeetingOutputController::class, 'index']);
    Route::post('/secretariats/{code}/messages', [PublicSecretariatMessageController::class, 'store']);
    Route::get('/news', [PublicContentController::class, 'news']);
    Route::get('/news/{slug}', [PublicContentController::class, 'newsShow']);
    Route::get('/decisions', [PublicContentController::class, 'decisions']);
    Route::get('/media-center', [PublicContentController::class, 'mediaCenter']);
    Route::get('/media-center/{slug}', [PublicContentController::class, 'mediaCenterShow']);
    Route::get('/announcements', [PublicContentController::class, 'announcements']);
    Route::get('/albums', [PublicContentController::class, 'albums']);
    Route::get('/albums/{album}', [PublicContentController::class, 'albumShow']);
    Route::get('/stats', [PublicContentController::class, 'stats']);
    Route::get('/events', [PublicContentController::class, 'events']);
    Route::get('/events/{slug}', [PublicContentController::class, 'eventShow']);
    Route::post('/events/{slug}/rate', [PublicContentController::class, 'rateEvent']);
    Route::get('/shura/members', [PublicShuraController::class, 'members']);
    Route::get('/shura/meetings', [PublicShuraController::class, 'meetings']);
    Route::get('/parents/members', [PublicParentsController::class, 'members']);
    Route::get('/parents/meetings', [PublicParentsController::class, 'meetings']);
    Route::get('/geocode', PublicGeocodeController::class)->middleware('throttle:30,1');
    Route::get('/parents/surveys', [PublicParentsController::class, 'surveys']);
    Route::post('/parents/registrations', [PublicParentsController::class, 'storeRegistration']);
    Route::post('/parents/surveys/{survey}/responses', [PublicParentsController::class, 'storeSurveyResponse']);
    Route::get('/contact', [PublicContactController::class, 'info']);
    Route::post('/contact', [PublicContactController::class, 'store']);
    Route::post('/members', [PublicMemberController::class, 'store']);
    Route::post('/help-requests', [PublicHelpRequestController::class, 'store']);
    Route::get('/sports', [PublicSportsController::class, 'overview']);
    Route::get('/sports/national', [PublicSportsController::class, 'national']);
    Route::get('/sports/teams/{team}', [PublicSportsController::class, 'team']);
    Route::post('/sports/join', [PublicSportsController::class, 'join']);
    Route::get('/external/partners', [PublicExternalController::class, 'partners']);
    Route::get('/external/documents', [PublicExternalController::class, 'documents']);
    Route::get('/president', [PublicPresidentController::class, 'show']);
    Route::get('/finance/documents', [PublicFinanceController::class, 'documents']);
    Route::get('/finance/documents/{kind}', [PublicFinanceController::class, 'document'])
        ->where('kind', 'general_report|subscriptions_announcement');
    Route::post('/external/contact-requests', [PublicExternalController::class, 'storeContact']);
    Route::get('/member-cities', [PublicMemberController::class, 'cities']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,15');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,15');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:user.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:user.view');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:user.delete');
    Route::post('/users/{user}/disable', [UserController::class, 'disable'])->middleware('permission:user.update');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.view');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:role.view');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:permission.assign');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permission.view');

    Route::get('/admin/departments', [AdminDepartmentController::class, 'index']);
    Route::get('/admin/reports', [AdminSecretariatReportController::class, 'index']);

    Route::prefix('admin/content')->group(function () {
        Route::get('/news', [AdminSiteContentController::class, 'newsIndex'])->middleware('permission:news.view');
        Route::post('/news', [AdminSiteContentController::class, 'newsStore'])->middleware('permission:news.create');
        Route::put('/news/{news}', [AdminSiteContentController::class, 'newsUpdate'])->middleware('permission:news.update');
        Route::post('/news/{news}', [AdminSiteContentController::class, 'newsUpdate'])->middleware('permission:news.update');
        Route::delete('/news/{news}', [AdminSiteContentController::class, 'newsDestroy'])->middleware('permission:news.delete');
        Route::post('/news/{news}/publish', [AdminSiteContentController::class, 'newsPublish'])->middleware('permission:news.publish');
        Route::post('/news/{news}/archive', [AdminSiteContentController::class, 'newsArchive'])->middleware('permission:news.update');

        Route::get('/announcements', [AdminSiteContentController::class, 'announcementsIndex'])->middleware('permission:announcement.view');
        Route::post('/announcements', [AdminSiteContentController::class, 'announcementsStore'])->middleware('permission:announcement.create');
        Route::put('/announcements/{announcement}', [AdminSiteContentController::class, 'announcementsUpdate'])->middleware('permission:announcement.update');
        Route::post('/announcements/{announcement}', [AdminSiteContentController::class, 'announcementsUpdate'])->middleware('permission:announcement.update');
        Route::delete('/announcements/{announcement}', [AdminSiteContentController::class, 'announcementsDestroy'])->middleware('permission:announcement.delete');
        Route::post('/announcements/{announcement}/publish', [AdminSiteContentController::class, 'announcementsPublish'])->middleware('permission:announcement.publish');
    });

    Route::prefix('admin/shura')->group(function () {
        Route::get('/overview', [AdminShuraController::class, 'overview']);
        Route::get('/members', [AdminShuraController::class, 'membersIndex']);
        Route::post('/members', [AdminShuraController::class, 'membersStore']);
        Route::put('/members/{member}', [AdminShuraController::class, 'membersUpdate']);
        Route::post('/members/{member}', [AdminShuraController::class, 'membersUpdate']);
        Route::delete('/members/{member}', [AdminShuraController::class, 'membersDestroy']);
        Route::get('/meetings', [AdminShuraController::class, 'meetingsIndex']);
        Route::post('/meetings', [AdminShuraController::class, 'meetingsStore']);
        Route::put('/meetings/{meeting}', [AdminShuraController::class, 'meetingsUpdate']);
        Route::delete('/meetings/{meeting}', [AdminShuraController::class, 'meetingsDestroy']);
        Route::post('/meetings/{meeting}/attendance', [AdminShuraController::class, 'syncAttendance']);
    });

    Route::prefix('admin/president')->group(function () {
        Route::get('/overview', [AdminPresidentController::class, 'overview']);
        Route::get('/card', [AdminPresidentController::class, 'cardShow']);
        Route::post('/card', [AdminPresidentController::class, 'cardUpdate']);
        Route::get('/secretariats', [AdminPresidentController::class, 'secretariats']);
        Route::get('/meetings', [AdminPresidentController::class, 'meetingsIndex']);
        Route::post('/meetings', [AdminPresidentController::class, 'meetingsStore']);
        Route::put('/meetings/{meeting}', [AdminPresidentController::class, 'meetingsUpdate']);
        Route::delete('/meetings/{meeting}', [AdminPresidentController::class, 'meetingsDestroy']);
        Route::get('/directives', [AdminPresidentController::class, 'directivesIndex']);
        Route::post('/directives', [AdminPresidentController::class, 'directivesStore']);
        Route::get('/archive', [AdminPresidentController::class, 'archiveIndex']);
        Route::post('/archive', [AdminPresidentController::class, 'archiveStore']);
        Route::put('/archive/{archiveItem}', [AdminPresidentController::class, 'archiveUpdate']);
        Route::delete('/archive/{archiveItem}', [AdminPresidentController::class, 'archiveDestroy']);
    });

    Route::prefix('admin/vice-president')->group(function () {
        Route::get('/overview', [AdminPresidentController::class, 'viceOverview']);
        Route::get('/card', [AdminPresidentController::class, 'cardShow']);
        Route::post('/card', [AdminPresidentController::class, 'cardUpdate']);
    });

    Route::prefix('admin/parents')->group(function () {
        Route::get('/members', [AdminParentsController::class, 'membersIndex']);
        Route::post('/members', [AdminParentsController::class, 'membersStore']);
        Route::put('/members/{member}', [AdminParentsController::class, 'membersUpdate']);
        Route::post('/members/{member}', [AdminParentsController::class, 'membersUpdate']);
        Route::delete('/members/{member}', [AdminParentsController::class, 'membersDestroy']);
        Route::get('/registrations', [AdminParentsController::class, 'registrationsIndex']);
        Route::put('/registrations/{registration}', [AdminParentsController::class, 'registrationsUpdate']);
        Route::delete('/registrations/{registration}', [AdminParentsController::class, 'registrationsDestroy']);
        Route::get('/meetings', [AdminParentsController::class, 'meetingsIndex']);
        Route::post('/meetings', [AdminParentsController::class, 'meetingsStore']);
        Route::put('/meetings/{meeting}', [AdminParentsController::class, 'meetingsUpdate']);
        Route::delete('/meetings/{meeting}', [AdminParentsController::class, 'meetingsDestroy']);
        Route::get('/surveys', [AdminParentsController::class, 'surveysIndex']);
        Route::post('/surveys', [AdminParentsController::class, 'surveysStore']);
        Route::put('/surveys/{survey}', [AdminParentsController::class, 'surveysUpdate']);
        Route::delete('/surveys/{survey}', [AdminParentsController::class, 'surveysDestroy']);
        Route::get('/surveys/{survey}/responses', [AdminParentsController::class, 'surveyResponses']);
    });

    Route::prefix('admin/academic')->group(function () {
        Route::get('/register', [AdminTeacherRegisterController::class, 'index']);
        Route::get('/register/pdf', [AdminTeacherRegisterController::class, 'pdf']);
        Route::patch('/register/{student}', [AdminTeacherRegisterController::class, 'rename']);
        Route::put('/register/{student}', [AdminTeacherRegisterController::class, 'upsert']);
        Route::get('/timetable', [AdminAcademicTimetableController::class, 'index']);
        Route::post('/timetable', [AdminAcademicTimetableController::class, 'store']);
        Route::put('/timetable/{session}', [AdminAcademicTimetableController::class, 'update']);
        Route::delete('/timetable/{session}', [AdminAcademicTimetableController::class, 'destroy']);
        Route::get('/lesson-preparations', [AdminLessonPreparationController::class, 'index']);
        Route::post('/lesson-preparations', [AdminLessonPreparationController::class, 'store']);
        Route::get('/lesson-preparations/{lessonPreparation}/pdf', [AdminLessonPreparationController::class, 'pdf']);
        Route::get('/lesson-preparations/{lessonPreparation}', [AdminLessonPreparationController::class, 'show']);
        Route::put('/lesson-preparations/{lessonPreparation}', [AdminLessonPreparationController::class, 'update']);
        Route::delete('/lesson-preparations/{lessonPreparation}', [AdminLessonPreparationController::class, 'destroy']);
        Route::get('/subjects', [AdminAcademicAttendanceController::class, 'subjects']);
        Route::get('/levels', [AdminAcademicAttendanceController::class, 'levels']);
        Route::get('/subjects/{subject}/classes', [AdminAcademicAttendanceController::class, 'classesBySubject']);
        Route::get('/levels/{level}/classes', [AdminAcademicAttendanceController::class, 'classesByLevel']);
        Route::get('/attendance/overview', [AdminAcademicAttendanceController::class, 'overview']);
        Route::get('/classes/{classGroup}/sessions', [AdminAcademicAttendanceController::class, 'sessionsIndex']);
        Route::post('/classes/{classGroup}/sessions', [AdminAcademicAttendanceController::class, 'sessionsStore']);
        Route::get('/classes/{classGroup}/roster', [AdminAcademicAttendanceController::class, 'roster']);
        Route::post('/classes/{classGroup}/students', [AdminAcademicAttendanceController::class, 'attachStudent']);
        Route::delete('/classes/{classGroup}/students/{student}', [AdminAcademicAttendanceController::class, 'detachStudent']);
        Route::put('/sessions/{session}', [AdminAcademicAttendanceController::class, 'sessionsUpdate']);
        Route::delete('/sessions/{session}', [AdminAcademicAttendanceController::class, 'sessionsDestroy']);
        Route::get('/sessions/{session}/sheet', [AdminAcademicAttendanceController::class, 'sheet']);
        Route::post('/sessions/{session}/sheet', [AdminAcademicAttendanceController::class, 'syncSheet']);
        Route::put('/sessions/{session}/students/{student}/attendance', [AdminAcademicAttendanceController::class, 'upsertAttendance']);
        Route::delete('/sessions/{session}/students/{student}/attendance', [AdminAcademicAttendanceController::class, 'destroyAttendance']);
        Route::get('/students/{student}/attendance', [AdminAcademicAttendanceController::class, 'studentReport']);

        Route::get('/catalog', [AdminAcademicStudentController::class, 'catalog']);
        Route::get('/students', [AdminAcademicStudentController::class, 'index']);
        Route::post('/students', [AdminAcademicStudentController::class, 'store']);
        Route::get('/students/{student}/pdf', [AdminAcademicStudentController::class, 'dossierPdf']);
        Route::get('/students/{student}', [AdminAcademicStudentController::class, 'show']);
        Route::put('/students/{student}', [AdminAcademicStudentController::class, 'update']);
        Route::delete('/students/{student}', [AdminAcademicStudentController::class, 'destroy']);

        Route::get('/teachers', [AdminAcademicTeacherController::class, 'index']);
        Route::post('/teachers', [AdminAcademicTeacherController::class, 'store']);
        Route::get('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'show']);
        Route::put('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'update']);
        Route::delete('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'destroy']);
        Route::get('/class-staff', [AdminAcademicClassStaffController::class, 'index']);
        Route::put('/class-staff/{level}', [AdminAcademicClassStaffController::class, 'update']);
        Route::put('/class-staff/{level}/visits', [AdminAcademicClassStaffController::class, 'upsertVisit']);
        Route::delete('/class-staff/{level}/visits/{month}', [AdminAcademicClassStaffController::class, 'destroyVisit'])
            ->where('month', '[0-9]{4}-[0-9]{2}');

        Route::get('/exams/catalog', [AdminAcademicExamController::class, 'catalog']);
        Route::get('/exams/pdf', [AdminAcademicExamController::class, 'indexPdf']);
        Route::get('/exams', [AdminAcademicExamController::class, 'index']);
        Route::post('/exams', [AdminAcademicExamController::class, 'store']);
        Route::get('/exams/{exam}/pdf', [AdminAcademicExamController::class, 'showPdf']);
        Route::get('/exams/{exam}', [AdminAcademicExamController::class, 'show']);
        Route::put('/exams/{exam}', [AdminAcademicExamController::class, 'update']);
        Route::delete('/exams/{exam}', [AdminAcademicExamController::class, 'destroy']);
        Route::post('/exams/{exam}/grades', [AdminAcademicExamController::class, 'syncGrades']);
        Route::get('/class-results', [AdminAcademicExamController::class, 'classSheet']);
        Route::get('/achievement/pdf', [AdminAcademicExamController::class, 'achievementPdf']);
        Route::get('/achievement', [AdminAcademicExamController::class, 'achievement']);
        Route::post('/achievement/grades', [AdminAcademicExamController::class, 'saveAchievementGrades']);
        Route::get('/students/{student}/academic-report/pdf', [AdminAcademicExamController::class, 'studentReportPdf']);
        Route::get('/students/{student}/academic-report', [AdminAcademicExamController::class, 'studentReport']);
    });

    Route::prefix('admin/statistics')->group(function () {
        Route::get('/members', [AdminStatisticsMemberController::class, 'index']);
        Route::post('/members', [AdminStatisticsMemberController::class, 'store']);
        Route::post('/members/message', [AdminStatisticsMemberController::class, 'message']);
        Route::post('/cities', [AdminStatisticsMemberController::class, 'storeCity']);
        Route::get('/members/{member}', [AdminStatisticsMemberController::class, 'show']);
        Route::put('/members/{member}', [AdminStatisticsMemberController::class, 'update']);
        Route::delete('/members/{member}', [AdminStatisticsMemberController::class, 'destroy']);
    });

    Route::prefix('admin/departments/{code}')
        ->middleware('department:read')
        ->group(function () {
            Route::get('/', [AdminDepartmentController::class, 'show']);
            Route::get('/report', [AdminSecretariatReportController::class, 'show']);
            Route::get('/report/pdf', [AdminSecretariatReportController::class, 'pdf']);
            Route::post('/officer', [AdminDepartmentController::class, 'updateOfficer'])->middleware('department:write');
            Route::put('/officer', [AdminDepartmentController::class, 'updateOfficer'])->middleware('department:write');
            Route::post('/deputy', [AdminDepartmentController::class, 'updateDeputy'])->middleware('department:write');
            Route::put('/deputy', [AdminDepartmentController::class, 'updateDeputy'])->middleware('department:write');

            Route::get('/news', [AdminNewsController::class, 'index'])->middleware('permission:news.view');
            Route::post('/news', [AdminNewsController::class, 'store'])->middleware(['permission:news.create', 'department:write']);
            Route::get('/news/{news}', [AdminNewsController::class, 'show'])->middleware('permission:news.view');
            Route::put('/news/{news}', [AdminNewsController::class, 'update'])->middleware(['permission:news.update', 'department:write']);
            Route::post('/news/{news}', [AdminNewsController::class, 'update'])->middleware(['permission:news.update', 'department:write']);
            Route::delete('/news/{news}', [AdminNewsController::class, 'destroy'])->middleware(['permission:news.delete', 'department:write']);
            Route::post('/news/{news}/submit', [AdminNewsController::class, 'submit'])->middleware(['permission:news.update', 'department:write']);
            Route::post('/news/{news}/publish', [AdminNewsController::class, 'publish'])->middleware(['permission:news.publish', 'department:write']);
            Route::post('/news/{news}/archive', [AdminNewsController::class, 'archive'])->middleware(['permission:news.update', 'department:write']);

            Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->middleware('permission:announcement.view');
            Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->middleware(['permission:announcement.create', 'department:write']);
            Route::get('/announcements/{announcement}', [AdminAnnouncementController::class, 'show'])->middleware('permission:announcement.view');
            Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->middleware(['permission:announcement.update', 'department:write']);
            Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->middleware(['permission:announcement.delete', 'department:write']);
            Route::post('/announcements/{announcement}/submit', [AdminAnnouncementController::class, 'submit'])->middleware(['permission:announcement.update', 'department:write']);
            Route::post('/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish'])->middleware(['permission:announcement.publish', 'department:write']);
            Route::post('/announcements/{announcement}/archive', [AdminAnnouncementController::class, 'archive'])->middleware(['permission:announcement.update', 'department:write']);

            Route::get('/albums', [AdminAlbumController::class, 'index'])->middleware('permission:gallery.view');
            Route::post('/albums', [AdminAlbumController::class, 'store'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::get('/albums/{album}', [AdminAlbumController::class, 'show'])->middleware('permission:gallery.view');
            Route::put('/albums/{album}', [AdminAlbumController::class, 'update'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::post('/albums/{album}', [AdminAlbumController::class, 'update'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::delete('/albums/{album}', [AdminAlbumController::class, 'destroy'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::post('/albums/{album}/publish', [AdminAlbumController::class, 'publish'])->middleware(['permission:gallery.publish', 'department:write']);
            Route::post('/albums/{album}/archive', [AdminAlbumController::class, 'archive'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::post('/albums/{album}/media', [AdminAlbumController::class, 'storeMedia'])->middleware(['permission:gallery.manage', 'department:write']);
            Route::delete('/albums/{album}/media/{media}', [AdminAlbumController::class, 'destroyMedia'])->middleware(['permission:gallery.manage', 'department:write']);

            Route::get('/events', [AdminEventController::class, 'index']);
            Route::post('/events', [AdminEventController::class, 'store'])->middleware(['department:write']);
            Route::get('/events/{event}', [AdminEventController::class, 'show']);
            Route::put('/events/{event}', [AdminEventController::class, 'update'])->middleware(['department:write']);
            Route::post('/events/{event}', [AdminEventController::class, 'update'])->middleware(['department:write']);
            Route::delete('/events/{event}', [AdminEventController::class, 'destroy'])->middleware(['department:write']);
            Route::post('/events/{event}/publish', [AdminEventController::class, 'publish'])->middleware(['department:write']);
            Route::post('/events/{event}/archive', [AdminEventController::class, 'archive'])->middleware(['department:write']);

            Route::get('/finance/overview', [AdminFinanceController::class, 'overview']);
            Route::put('/finance/budget', [AdminFinanceController::class, 'saveBudget'])->middleware(['department:write']);
            Route::post('/finance/budget', [AdminFinanceController::class, 'saveBudget'])->middleware(['department:write']);
            Route::get('/finance/revenues', [AdminFinanceController::class, 'revenuesIndex']);
            Route::post('/finance/revenues', [AdminFinanceController::class, 'revenuesStore'])->middleware(['department:write']);
            Route::put('/finance/revenues/{financeRevenue}', [AdminFinanceController::class, 'revenuesUpdate'])->middleware(['department:write']);
            Route::delete('/finance/revenues/{financeRevenue}', [AdminFinanceController::class, 'revenuesDestroy'])->middleware(['department:write']);
            Route::get('/finance/expenses', [AdminFinanceController::class, 'expensesIndex']);
            Route::post('/finance/expenses', [AdminFinanceController::class, 'expensesStore'])->middleware(['department:write']);
            Route::put('/finance/expenses/{financeExpense}', [AdminFinanceController::class, 'expensesUpdate'])->middleware(['department:write']);
            Route::delete('/finance/expenses/{financeExpense}', [AdminFinanceController::class, 'expensesDestroy'])->middleware(['department:write']);
            Route::get('/finance/documents', [AdminFinanceDocumentController::class, 'index']);
            Route::post('/finance/documents/{kind}/publish', [AdminFinanceDocumentController::class, 'publish'])
                ->where('kind', 'general_report|subscriptions_announcement')
                ->middleware(['department:write']);
            Route::post('/finance/documents/{kind}/unpublish', [AdminFinanceDocumentController::class, 'unpublish'])
                ->where('kind', 'general_report|subscriptions_announcement')
                ->middleware(['department:write']);
            Route::post('/finance/documents/{kind}', [AdminFinanceDocumentController::class, 'update'])
                ->where('kind', 'general_report|subscriptions_announcement')
                ->middleware(['department:write']);

            Route::get('/decisions', [AdminMediaDecisionController::class, 'index']);
            Route::post('/decisions', [AdminMediaDecisionController::class, 'store'])->middleware(['department:write']);
            Route::put('/decisions/{mediaDecision}', [AdminMediaDecisionController::class, 'update'])->middleware(['department:write']);
            Route::delete('/decisions/{mediaDecision}', [AdminMediaDecisionController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/meeting-outputs', [AdminSecretariatMeetingOutputController::class, 'index']);
            Route::post('/meeting-outputs', [AdminSecretariatMeetingOutputController::class, 'store'])->middleware(['department:write']);
            Route::get('/meeting-outputs/{meetingOutput}', [AdminSecretariatMeetingOutputController::class, 'show']);
            Route::put('/meeting-outputs/{meetingOutput}', [AdminSecretariatMeetingOutputController::class, 'update'])->middleware(['department:write']);
            Route::delete('/meeting-outputs/{meetingOutput}', [AdminSecretariatMeetingOutputController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/media-center', [AdminMediaCenterController::class, 'index']);
            Route::post('/media-center', [AdminMediaCenterController::class, 'store'])->middleware(['department:write']);
            Route::put('/media-center/{mediaCenterItem}', [AdminMediaCenterController::class, 'update'])->middleware(['department:write']);
            Route::post('/media-center/{mediaCenterItem}', [AdminMediaCenterController::class, 'update'])->middleware(['department:write']);
            Route::delete('/media-center/{mediaCenterItem}', [AdminMediaCenterController::class, 'destroy'])->middleware(['department:write']);
            Route::post('/media-center/{mediaCenterItem}/publish', [AdminMediaCenterController::class, 'publish'])->middleware(['department:write']);
            Route::post('/media-center/{mediaCenterItem}/archive', [AdminMediaCenterController::class, 'archive'])->middleware(['department:write']);

            Route::get('/help-requests', [AdminSocialHelpRequestController::class, 'index']);
            Route::post('/help-requests', [AdminSocialHelpRequestController::class, 'store'])->middleware(['department:write']);
            Route::get('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'show']);
            Route::put('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'update'])->middleware(['department:write']);
            Route::delete('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/visits', [AdminSocialVisitController::class, 'index']);
            Route::post('/visits', [AdminSocialVisitController::class, 'store'])->middleware(['department:write']);
            Route::get('/visits/{socialVisit}', [AdminSocialVisitController::class, 'show']);
            Route::put('/visits/{socialVisit}', [AdminSocialVisitController::class, 'update'])->middleware(['department:write']);
            Route::delete('/visits/{socialVisit}', [AdminSocialVisitController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/women-members', [AdminWomenMemberController::class, 'index']);
            Route::post('/women-members', [AdminWomenMemberController::class, 'store'])->middleware(['department:write']);
            Route::get('/women-members/{womenMember}', [AdminWomenMemberController::class, 'show']);
            Route::put('/women-members/{womenMember}', [AdminWomenMemberController::class, 'update'])->middleware(['department:write']);
            Route::delete('/women-members/{womenMember}', [AdminWomenMemberController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/partners', [AdminExternalPartnerController::class, 'index']);
            Route::post('/partners', [AdminExternalPartnerController::class, 'store'])->middleware(['department:write']);
            Route::get('/partners/{externalPartner}', [AdminExternalPartnerController::class, 'show']);
            Route::put('/partners/{externalPartner}', [AdminExternalPartnerController::class, 'update'])->middleware(['department:write']);
            Route::delete('/partners/{externalPartner}', [AdminExternalPartnerController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/external-documents', [AdminExternalDocumentController::class, 'index']);
            Route::post('/external-documents', [AdminExternalDocumentController::class, 'store'])->middleware(['department:write']);
            Route::post('/external-documents/{externalDocument}', [AdminExternalDocumentController::class, 'update'])->middleware(['department:write']);
            Route::put('/external-documents/{externalDocument}', [AdminExternalDocumentController::class, 'update'])->middleware(['department:write']);
            Route::delete('/external-documents/{externalDocument}', [AdminExternalDocumentController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/contact-requests', [AdminExternalContactRequestController::class, 'index']);
            Route::post('/contact-requests', [AdminExternalContactRequestController::class, 'store'])->middleware(['department:write']);
            Route::put('/contact-requests/{externalContactRequest}', [AdminExternalContactRequestController::class, 'update'])->middleware(['department:write']);
            Route::delete('/contact-requests/{externalContactRequest}', [AdminExternalContactRequestController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/messages', [AdminSecretariatMessageController::class, 'index']);
            Route::post('/messages', [AdminSecretariatMessageController::class, 'store'])->middleware(['department:write']);
            Route::put('/messages/{secretariatMessage}', [AdminSecretariatMessageController::class, 'update'])->middleware(['department:write']);
            Route::delete('/messages/{secretariatMessage}', [AdminSecretariatMessageController::class, 'destroy'])->middleware(['department:write']);

            Route::get('/presidential-directives', [AdminPresidentController::class, 'inboxIndex']);
            Route::put('/presidential-directives/{directive}', [AdminPresidentController::class, 'inboxUpdate'])->middleware(['department:write']);

            Route::get('/secretariat-directives/targets', [AdminSecretariatDirectiveController::class, 'targets']);
            Route::get('/secretariat-directives', [AdminSecretariatDirectiveController::class, 'index']);
            Route::post('/secretariat-directives', [AdminSecretariatDirectiveController::class, 'store'])->middleware(['department:write']);
            Route::put('/secretariat-directives/{secretariatDirective}', [AdminSecretariatDirectiveController::class, 'update'])->middleware(['department:write']);

            Route::get('/sports/teams', [AdminSportsController::class, 'teamsIndex']);
            Route::post('/sports/teams', [AdminSportsController::class, 'teamsStore'])->middleware(['department:write']);
            Route::get('/sports/teams/{team}', [AdminSportsController::class, 'teamsShow']);
            Route::put('/sports/teams/{team}', [AdminSportsController::class, 'teamsUpdate'])->middleware(['department:write']);
            Route::post('/sports/teams/{team}', [AdminSportsController::class, 'teamsUpdate'])->middleware(['department:write']);
            Route::delete('/sports/teams/{team}', [AdminSportsController::class, 'teamsDestroy'])->middleware(['department:write']);

            Route::get('/sports/players', [AdminSportsController::class, 'playersIndex']);
            Route::post('/sports/players', [AdminSportsController::class, 'playersStore'])->middleware(['department:write']);
            Route::put('/sports/players/{player}', [AdminSportsController::class, 'playersUpdate'])->middleware(['department:write']);
            Route::post('/sports/players/{player}', [AdminSportsController::class, 'playersUpdate'])->middleware(['department:write']);
            Route::delete('/sports/players/{player}', [AdminSportsController::class, 'playersDestroy'])->middleware(['department:write']);

            Route::get('/sports/staff', [AdminSportsController::class, 'staffIndex']);
            Route::post('/sports/staff', [AdminSportsController::class, 'staffStore'])->middleware(['department:write']);
            Route::put('/sports/staff/{staff}', [AdminSportsController::class, 'staffUpdate'])->middleware(['department:write']);
            Route::post('/sports/staff/{staff}', [AdminSportsController::class, 'staffUpdate'])->middleware(['department:write']);
            Route::delete('/sports/staff/{staff}', [AdminSportsController::class, 'staffDestroy'])->middleware(['department:write']);

            Route::get('/sports/matches', [AdminSportsController::class, 'matchesIndex']);
            Route::post('/sports/matches', [AdminSportsController::class, 'matchesStore'])->middleware(['department:write']);
            Route::put('/sports/matches/{match}', [AdminSportsController::class, 'matchesUpdate'])->middleware(['department:write']);
            Route::delete('/sports/matches/{match}', [AdminSportsController::class, 'matchesDestroy'])->middleware(['department:write']);

            Route::post('/sports/trainings', [AdminSportsController::class, 'trainingsStore'])->middleware(['department:write']);
            Route::put('/sports/trainings/{training}', [AdminSportsController::class, 'trainingsUpdate'])->middleware(['department:write']);
            Route::delete('/sports/trainings/{training}', [AdminSportsController::class, 'trainingsDestroy'])->middleware(['department:write']);

            Route::get('/sports/tournaments', [AdminSportsController::class, 'tournamentsIndex']);
            Route::post('/sports/tournaments', [AdminSportsController::class, 'tournamentsStore'])->middleware(['department:write']);
            Route::put('/sports/tournaments/{tournament}', [AdminSportsController::class, 'tournamentsUpdate'])->middleware(['department:write']);
            Route::delete('/sports/tournaments/{tournament}', [AdminSportsController::class, 'tournamentsDestroy'])->middleware(['department:write']);

            Route::get('/sports/camps', [AdminSportsController::class, 'campsIndex']);
            Route::post('/sports/camps', [AdminSportsController::class, 'campsStore'])->middleware(['department:write']);
            Route::put('/sports/camps/{camp}', [AdminSportsController::class, 'campsUpdate'])->middleware(['department:write']);
            Route::delete('/sports/camps/{camp}', [AdminSportsController::class, 'campsDestroy'])->middleware(['department:write']);

            Route::get('/sports/join-requests', [AdminSportsController::class, 'joinIndex']);
            Route::put('/sports/join-requests/{joinRequest}', [AdminSportsController::class, 'joinUpdate'])->middleware(['department:write']);
            Route::delete('/sports/join-requests/{joinRequest}', [AdminSportsController::class, 'joinDestroy'])->middleware(['department:write']);
        });
});
