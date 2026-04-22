<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ClearanceMonitorController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('/requests/{documentRequest}', [RequestController::class, 'show'])->name('requests.show');
Route::post('/requests/{documentRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
Route::post('/requests/{documentRequest}/deny', [RequestController::class, 'deny'])->name('requests.deny');
Route::post('/requests/{documentRequest}/stage', [RequestController::class, 'updateStage'])->name('requests.stage');

Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
Route::post('/payments/{payment}/deny', [PaymentController::class, 'deny'])->name('payments.deny');

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

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
