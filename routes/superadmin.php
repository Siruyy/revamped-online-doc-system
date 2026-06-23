<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\ActivityLogExportController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\UserExportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
});

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::get('/logs/export', ActivityLogExportController::class)->name('logs.export');
Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('/requests/{documentRequest}', [RequestController::class, 'show'])->name('requests.show');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/exports/requests', [ReportExportController::class, 'requests'])->name('reports.exports.requests');
Route::get('/reports/exports/payments', [ReportExportController::class, 'payments'])->name('reports.exports.payments');
Route::get('/document-types', [DocumentTypeController::class, 'index'])->name('document-types.index');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');

Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/requests/{documentRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{documentRequest}/deny', [RequestController::class, 'deny'])->name('requests.deny');
    Route::post('/requests/{documentRequest}/approve-with-payment', [RequestController::class, 'approveWithPayment'])->name('requests.approve-with-payment');
    Route::post('/requests/{documentRequest}/deny-with-payment', [RequestController::class, 'denyWithPayment'])->name('requests.deny-with-payment');
    Route::post('/requests/{documentRequest}/stage', [RequestController::class, 'updateStage'])->name('requests.stage');
    Route::post('/requests/{documentRequest}/requirements/{requirement}/validate', [RequestController::class, 'validateRequirement'])->name('requests.requirements.validate');
    Route::post('/requests/{documentRequest}/requirements/{requirement}/reject', [RequestController::class, 'rejectRequirement'])->name('requests.requirements.reject');
    Route::post('/requests/{documentRequest}/sla/pause', [RequestController::class, 'pauseSla'])->name('requests.sla.pause');
    Route::post('/requests/{documentRequest}/sla/resume', [RequestController::class, 'resumeSla'])->name('requests.sla.resume');
    Route::post('/requests/{documentRequest}/release', [RequestController::class, 'release'])->name('requests.release');
    Route::post('/requests/{documentRequest}/honorable-dismissal', [ReleaseController::class, 'markHonorableDismissal'])->name('requests.hd');

    Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
    Route::patch('/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
    Route::delete('/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');

    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::patch('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
});

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/export', UserExportController::class)->name('export');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::get('/pending', [UserController::class, 'pending'])->name('pending');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::middleware('throttle:sensitive-actions')->group(function () {
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('/bulk-approve', [UserController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/approve', [UserController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [UserController::class, 'reject'])->name('reject');
        Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/reactivate', [UserController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
