<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\FaqController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\RequestController;
use App\Http\Controllers\Student\ClearanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
Route::get('/requests/new', [RequestController::class, 'create'])->name('requests.create');
Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
Route::get('/requests/{documentRequest}', [RequestController::class, 'show'])->name('requests.show');
Route::post('/requests/{documentRequest}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');

Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('/payments/{payment}/upload', [PaymentController::class, 'upload'])
    ->middleware('throttle:uploads')
    ->name('payments.upload');

Route::get('/clearance', [ClearanceController::class, 'show'])->name('clearance.show');
Route::post('/clearance', [ClearanceController::class, 'submit'])
    ->middleware('throttle:uploads')
    ->name('clearance.submit');
Route::get('/clearance/pdf', [ClearanceController::class, 'downloadPdf'])->name('clearance.download-pdf');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
