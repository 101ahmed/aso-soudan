<?php

use App\Http\Controllers\Api\Admin\AdminAlbumController;
use App\Http\Controllers\Api\Admin\AdminAnnouncementController;
use App\Http\Controllers\Api\Admin\AdminDepartmentController;
use App\Http\Controllers\Api\Admin\AdminNewsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\Public\PublicContentController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('public')->group(function () {
    Route::get('/departments', [PublicContentController::class, 'departments']);
    Route::get('/secretariats/{code}/feed', [PublicContentController::class, 'secretariatFeed']);
    Route::get('/news', [PublicContentController::class, 'news']);
    Route::get('/news/{slug}', [PublicContentController::class, 'newsShow']);
    Route::get('/announcements', [PublicContentController::class, 'announcements']);
    Route::get('/albums', [PublicContentController::class, 'albums']);
    Route::get('/albums/{album}', [PublicContentController::class, 'albumShow']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

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

    Route::prefix('admin/departments/{code}')
        ->middleware('department:read')
        ->group(function () {
            Route::get('/', [AdminDepartmentController::class, 'show']);

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
        });
});
