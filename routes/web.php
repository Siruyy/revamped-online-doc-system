<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();

    return redirect()->route($user->roleHomeRoute());
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:student', 'approved', 'verified'])
    ->prefix('student')
    ->name('student.')
    ->group(base_path('routes/student.php'));

Route::middleware(['auth', 'role:admin', 'approved', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

Route::middleware(['auth', 'role:teacher,dean,accounting,sao', 'approved', 'verified'])
    ->prefix('department')
    ->name('department.')
    ->group(base_path('routes/department.php'));

Route::middleware(['auth', 'role:superadmin', 'approved', 'verified'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(base_path('routes/superadmin.php'));
