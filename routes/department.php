<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentProfileController;
use App\Http\Controllers\Department\ClearanceController;
use App\Http\Controllers\Department\DashboardController;
use App\Http\Controllers\Department\FaqController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature');
});

Route::get('/clearances', [ClearanceController::class, 'index'])->name('clearances.index');
Route::get('/clearances/{clearance}', [ClearanceController::class, 'show'])->name('clearances.show');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/clearances/{clearance}/sign', [ClearanceController::class, 'sign'])->name('clearances.sign');
    Route::post('/clearances/{clearance}/deny', [ClearanceController::class, 'deny'])->name('clearances.deny');
});

Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/deny', [PaymentController::class, 'deny'])->name('payments.deny');
});

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');

Route::middleware('role:accounting')->group(function () {
    Route::get('/settings/payment-profile', [PaymentProfileController::class, 'index'])->name('settings.payment-profile.index');
    Route::middleware('throttle:sensitive-actions')->group(function () {
        Route::post('/settings/payment-profile', [PaymentProfileController::class, 'store'])->name('settings.payment-profile.store');
        Route::patch('/settings/payment-profile/{paymentProfile}', [PaymentProfileController::class, 'update'])->name('settings.payment-profile.update');
        Route::patch('/settings/payment-profile/{paymentProfile}/toggle', [PaymentProfileController::class, 'toggle'])->name('settings.payment-profile.toggle');
        Route::delete('/settings/payment-profile/{paymentProfile}', [PaymentProfileController::class, 'destroy'])->name('settings.payment-profile.destroy');
        Route::delete('/settings/payment-profile/{paymentProfile}/qr', [PaymentProfileController::class, 'removeQr'])->name('settings.payment-profile.remove-qr');
    });
});

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::middleware('throttle:sensitive-actions')->group(function () {
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});
