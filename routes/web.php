<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\DocumentRequestController as PublicDocumentRequestController;
use App\Http\Controllers\Public\TrackDocumentController;
use App\Models\User;
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
    /** @var User $user */
    $user = auth()->user();

    return redirect()->route($user->roleHomeRoute());
})->name('dashboard');

Route::get('/request-document', [PublicDocumentRequestController::class, 'create'])
    ->name('public.requests.create');
Route::post('/request-document', [PublicDocumentRequestController::class, 'store'])
    ->middleware('throttle:public-requests')
    ->name('public.requests.store');
Route::get('/request-document/submitted/{reference}', [PublicDocumentRequestController::class, 'submitted'])
    ->name('public.requests.submitted');
Route::get('/track-document', [TrackDocumentController::class, 'create'])
    ->middleware('throttle:public-tracking')
    ->name('track-document');
Route::post('/track-document', [TrackDocumentController::class, 'show'])
    ->middleware('throttle:public-tracking')
    ->name('track-document.show');
Route::get('/public/files/payment-qr/{paymentProfile}', [FileController::class, 'publicPaymentQr'])
    ->name('public.files.payment-qr');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/files/payment-receipt/{payment}', [FileController::class, 'paymentReceipt'])->name('files.payment-receipt');
    Route::get('/files/payment-qr/{paymentProfile}', [FileController::class, 'paymentQr'])->name('files.payment-qr');
    Route::get('/files/clearance/{clearance}/pdf', [FileController::class, 'clearancePdf'])->name('files.clearance-pdf');
    Route::get('/files/clearance/{clearance}/supporting', [FileController::class, 'clearanceSupportingFile'])->name('files.clearance-supporting');
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

Route::middleware(['auth', 'role:teacher|dean|accounting|sao', 'approved', 'verified'])
    ->prefix('department')
    ->name('department.')
    ->group(base_path('routes/department.php'));

Route::middleware(['auth', 'role:superadmin', 'approved', 'verified'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(base_path('routes/superadmin.php'));
