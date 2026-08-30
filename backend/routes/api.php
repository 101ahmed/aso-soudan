<?php

use App\Http\Controllers\Api\Admin\AdminAcademicAttendanceController;
use App\Http\Controllers\Api\Admin\AdminAcademicStudentController;
use App\Http\Controllers\Api\Admin\AdminAcademicTeacherController;
use App\Http\Controllers\Api\Admin\AdminAlbumController;
use App\Http\Controllers\Api\Admin\AdminAnnouncementController;
use App\Http\Controllers\Api\Admin\AdminDepartmentController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminExternalContactRequestController;
use App\Http\Controllers\Api\Admin\AdminExternalDocumentController;
use App\Http\Controllers\Api\Admin\AdminExternalPartnerController;
use App\Http\Controllers\Api\Admin\AdminNewsController;
use App\Http\Controllers\Api\Admin\AdminSecretariatMessageController;
use App\Http\Controllers\Api\Admin\AdminShuraController;
use App\Http\Controllers\Api\Admin\AdminSiteContentController;
use App\Http\Controllers\Api\Admin\AdminSocialHelpRequestController;
use App\Http\Controllers\Api\Admin\AdminStatisticsMemberController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\Public\PublicContactController;
use App\Http\Controllers\Api\Public\PublicContentController;
use App\Http\Controllers\Api\Public\PublicExternalController;
use App\Http\Controllers\Api\Public\PublicHelpRequestController;
use App\Http\Controllers\Api\Public\PublicMemberController;
use App\Http\Controllers\Api\Public\PublicSecretariatMessageController;
use App\Http\Controllers\Api\Public\PublicShuraController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('public')->group(function () {
    Route::get('/departments', [PublicContentController::class, 'departments']);
    Route::get('/secretariats/{code}/feed', [PublicContentController::class, 'secretariatFeed']);
    Route::post('/secretariats/{code}/messages', [PublicSecretariatMessageController::class, 'store']);
    Route::get('/news', [PublicContentController::class, 'news']);
    Route::get('/news/{slug}', [PublicContentController::class, 'newsShow']);
    Route::get('/announcements', [PublicContentController::class, 'announcements']);
    Route::get('/albums', [PublicContentController::class, 'albums']);
    Route::get('/albums/{album}', [PublicContentController::class, 'albumShow']);
    Route::get('/events', [PublicContentController::class, 'events']);
    Route::get('/events/{slug}', [PublicContentController::class, 'eventShow']);
    Route::post('/events/{slug}/rate', [PublicContentController::class, 'rateEvent']);
    Route::get('/shura/members', [PublicShuraController::class, 'members']);
    Route::get('/shura/meetings', [PublicShuraController::class, 'meetings']);
    Route::get('/contact', [PublicContactController::class, 'info']);
    Route::post('/contact', [PublicContactController::class, 'store']);
    Route::post('/members', [PublicMemberController::class, 'store']);
    Route::post('/help-requests', [PublicHelpRequestController::class, 'store']);
    Route::get('/external/partners', [PublicExternalController::class, 'partners']);
    Route::get('/external/documents', [PublicExternalController::class, 'documents']);
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

    Route::prefix('admin/academic')->group(function () {
        Route::get('/attendance/overview', [AdminAcademicAttendanceController::class, 'overview']);
        Route::get('/subjects', [AdminAcademicAttendanceController::class, 'subjects']);
        Route::get('/subjects/{subject}/classes', [AdminAcademicAttendanceController::class, 'classesBySubject']);
        Route::get('/classes/{classGroup}/sessions', [AdminAcademicAttendanceController::class, 'sessionsIndex']);
        Route::post('/classes/{classGroup}/sessions', [AdminAcademicAttendanceController::class, 'sessionsStore']);
        Route::get('/sessions/{session}/sheet', [AdminAcademicAttendanceController::class, 'sheet']);
        Route::post('/sessions/{session}/sheet', [AdminAcademicAttendanceController::class, 'syncSheet']);
        Route::get('/students/{student}/attendance', [AdminAcademicAttendanceController::class, 'studentReport']);

        Route::get('/catalog', [AdminAcademicStudentController::class, 'catalog']);
        Route::get('/students', [AdminAcademicStudentController::class, 'index']);
        Route::post('/students', [AdminAcademicStudentController::class, 'store']);
        Route::get('/students/{student}', [AdminAcademicStudentController::class, 'show']);
        Route::put('/students/{student}', [AdminAcademicStudentController::class, 'update']);
        Route::delete('/students/{student}', [AdminAcademicStudentController::class, 'destroy']);

        Route::get('/teachers', [AdminAcademicTeacherController::class, 'index']);
        Route::post('/teachers', [AdminAcademicTeacherController::class, 'store']);
        Route::get('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'show']);
        Route::put('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'update']);
        Route::delete('/teachers/{teacher}', [AdminAcademicTeacherController::class, 'destroy']);
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

            Route::get('/help-requests', [AdminSocialHelpRequestController::class, 'index']);
            Route::post('/help-requests', [AdminSocialHelpRequestController::class, 'store'])->middleware(['department:write']);
            Route::get('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'show']);
            Route::put('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'update'])->middleware(['department:write']);
            Route::delete('/help-requests/{helpRequest}', [AdminSocialHelpRequestController::class, 'destroy'])->middleware(['department:write']);

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
        });
});
