<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::post('/bulk-approve', [UserController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/pending', [UserController::class, 'pending'])->name('pending');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    Route::post('/{user}/approve', [UserController::class, 'approve'])->name('approve');
    Route::post('/{user}/reject', [UserController::class, 'reject'])->name('reject');
    Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('suspend');
    Route::post('/{user}/reactivate', [UserController::class, 'reactivate'])->name('reactivate');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});
