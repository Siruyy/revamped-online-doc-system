<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ClearanceMonitorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentProfileController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('/requests/{documentRequest}', [RequestController::class, 'show'])->name('requests.show');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/requests/{documentRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{documentRequest}/deny', [RequestController::class, 'deny'])->name('requests.deny');
    Route::post('/requests/{documentRequest}/stage', [RequestController::class, 'updateStage'])->name('requests.stage');
    Route::post('/requests/{documentRequest}/requirements/{requirement}/validate', [RequestController::class, 'validateRequirement'])->name('requests.requirements.validate');
    Route::post('/requests/{documentRequest}/requirements/{requirement}/reject', [RequestController::class, 'rejectRequirement'])->name('requests.requirements.reject');
    Route::post('/requests/{documentRequest}/sla/pause', [RequestController::class, 'pauseSla'])->name('requests.sla.pause');
    Route::post('/requests/{documentRequest}/sla/resume', [RequestController::class, 'resumeSla'])->name('requests.sla.resume');
    Route::post('/requests/{documentRequest}/release', [RequestController::class, 'release'])->name('requests.release');
    Route::post('/requests/{documentRequest}/honorable-dismissal', [ReleaseController::class, 'markHonorableDismissal'])->name('requests.hd');
});

Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.index');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/releases/{claimSlip}/release', [ReleaseController::class, 'release'])->name('releases.release');
    Route::post('/releases/{claimSlip}/void', [ReleaseController::class, 'void'])->name('releases.void');
});

Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/deny', [PaymentController::class, 'deny'])->name('payments.deny');
});

Route::get('/clearances', [ClearanceMonitorController::class, 'index'])->name('clearances.index');
Route::get('/clearances/{clearance}', [ClearanceMonitorController::class, 'show'])->name('clearances.show');

Route::get('/document-types', [DocumentTypeController::class, 'index'])->name('document-types.index');
Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
Route::patch('/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
Route::delete('/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
Route::patch('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

Route::get('/settings/payment-profile', [PaymentProfileController::class, 'index'])->name('settings.payment-profile.index');
Route::post('/settings/payment-profile', [PaymentProfileController::class, 'store'])->name('settings.payment-profile.store');
Route::patch('/settings/payment-profile/{paymentProfile}', [PaymentProfileController::class, 'update'])->name('settings.payment-profile.update');
Route::patch('/settings/payment-profile/{paymentProfile}/toggle', [PaymentProfileController::class, 'toggle'])->name('settings.payment-profile.toggle');
Route::delete('/settings/payment-profile/{paymentProfile}', [PaymentProfileController::class, 'destroy'])->name('settings.payment-profile.destroy');
Route::delete('/settings/payment-profile/{paymentProfile}/qr', [PaymentProfileController::class, 'removeQr'])->name('settings.payment-profile.remove-qr');
// Legacy alias so existing code/tests don't break
Route::post('/settings/payment-profile/upsert', [PaymentProfileController::class, 'store'])->name('settings.payment-profile.upsert');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
