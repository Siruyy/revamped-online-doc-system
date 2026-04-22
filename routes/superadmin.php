<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/pending', [UserController::class, 'pending'])->name('pending');
    Route::post('/{user}/approve', [UserController::class, 'approve'])->name('approve');
    Route::post('/{user}/reject', [UserController::class, 'reject'])->name('reject');
});
