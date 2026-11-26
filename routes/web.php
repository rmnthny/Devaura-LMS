<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\LibraryImportController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/inventory.php';
require __DIR__.'/attendance.php';

Route::get('/', fn () => request()->user()?->cannot('books.view')
    ? to_route('profile.edit') : to_route('books.index'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/verify-email', 'auth.verify')->name('otp.form');
});

Route::middleware('secure')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->middleware('permission:profile.update')->name('profile.edit');
    Route::get('/members', [MemberController::class, 'index'])->middleware('permission:members.manage')->name('members.index');
    Route::post('/members/qr-sheet', [MemberController::class, 'qrSheet'])->middleware('permission:members.manage')->name('members.qr-sheet');
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::get('/members/import-template', [LibraryImportController::class, 'template'])->defaults('kind', 'members')->name('members.import-template');
        Route::post('/members/import', [LibraryImportController::class, 'store'])->defaults('kind', 'members')->name('members.import');
    });
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->middleware('permission:members.manage')->name('members.edit');
    Route::get('/my-qr-card', [MemberController::class, 'myQrCard'])->middleware('permission:qr.own')->name('members.my-qr-card');
    Route::get('/accounts', [UserRoleController::class, 'index'])->middleware('role:superadmin|admin')->name('accounts.index');
});

Route::get('/api-csrf', fn () => response()
    ->json(['csrf_token' => csrf_token()])
    ->header('Cache-Control', 'no-store'))
    ->name('api.csrf');

Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

        Route::post('/verify-otp', [OtpVerificationController::class, 'verify'])->middleware('throttle:otp-verify')->name('otp.verify');
        Route::post('/verify-otp/resend', [OtpVerificationController::class, 'resend'])
            ->middleware('throttle:otp-resend')
            ->name('otp.resend');
    });

    Route::middleware('secure')->group(function () {
        Route::get('/me', [ProfileController::class, 'me'])->middleware('permission:profile.update')->name('me');
        Route::post('/logout', [LoginController::class, 'logout'])->middleware('permission:profile.update')->name('logout');

        Route::put('/profile', [ProfileController::class, 'update'])->middleware('permission:profile.update')->name('profile.update');

        Route::get('/my-qr-card', [MemberController::class, 'myQrCard'])
            ->middleware('permission:qr.own')
            ->name('members.my-qr-card');

        Route::get('/members/{member}/qr-card', [MemberController::class, 'qrCard'])
            ->middleware('permission:qr.own|members.manage')
            ->name('members.qr-card');

        Route::middleware('role:superadmin')->prefix('members')->name('members.')->group(function () {
            Route::post('/', [MemberController::class, 'store'])->name('store');
        });

        Route::middleware(['role:superadmin', 'permission:members.delete'])->prefix('members')->name('members.')->group(function () {
            Route::get('/archive', [MemberController::class, 'archive'])->name('archive');
            Route::post('/{member}/restore', [MemberController::class, 'restore'])->name('restore');
            Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('permission:members.manage')->prefix('members')->name('members.')->group(function () {
            Route::get('/', [MemberController::class, 'index'])->name('index');
            Route::get('/{member}', [MemberController::class, 'show'])->name('show');
            Route::put('/{member}', [MemberController::class, 'update'])->name('update');
        });

        Route::middleware('role:superadmin|admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}/role', [UserRoleController::class, 'updateRole'])->name('users.role');
        });
        Route::middleware(['role:superadmin', 'permission:roles.manage'])->prefix('admin')->name('admin.')->group(function () {
            Route::patch('/users/{user}/status', [UserRoleController::class, 'updateStatus'])->name('users.status');
            Route::post('/users/{user}/transfer-superadmin', [UserRoleController::class, 'transfer'])->name('users.transfer');
        });
    });
});
