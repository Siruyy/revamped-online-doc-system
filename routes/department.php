<?php

use App\Http\Controllers\Department\ClearanceController;
use App\Http\Controllers\Department\DashboardController;
use App\Http\Controllers\Department\FaqController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature');

Route::get('/clearances', [ClearanceController::class, 'index'])->name('clearances.index');
Route::get('/clearances/{clearance}', [ClearanceController::class, 'show'])->name('clearances.show');
Route::post('/clearances/{clearance}/sign', [ClearanceController::class, 'sign'])->name('clearances.sign');
Route::post('/clearances/{clearance}/deny', [ClearanceController::class, 'deny'])->name('clearances.deny');

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
