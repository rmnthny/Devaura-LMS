<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
Route::get('/api-csrf', fn () => response()
    ->json(['csrf_token' => csrf_token()])
    ->header('Cache-Control', 'no-store'))
    ->name('api.csrf');

Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

        Route::post('/register', [RegisterController::class, 'register'])->name('register.store');

        Route::post('/verify-otp', [OtpVerificationController::class, 'verify'])->name('otp.verify');
        Route::post('/verify-otp/resend', [OtpVerificationController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('otp.resend');
    });

    Route::middleware('secure')->group(function () {
        Route::get('/me', [ProfileController::class, 'me'])->name('me');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware('permission:profile.update')->group(function () {
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::get('/my-qr-card', [MemberController::class, 'myQrCard'])
        ->middleware('permission:qr.own')
        ->name('members.my-qr-card');

    Route::get('/members/{member}/qr-card', [MemberController::class, 'qrCard'])
        ->name('members.qr-card');

    Route::middleware('permission:members.delete')->prefix('members')->name('members.')->group(function () {
        Route::get('/archive', [MemberController::class, 'archive'])->name('archive');
        Route::post('/{member}/restore', [MemberController::class, 'restore'])->name('restore');
        Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('permission:members.manage')->prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/{member}', [MemberController::class, 'show'])->name('show');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::put('/{member}', [MemberController::class, 'update'])->name('update');
    });

        Route::middleware(['role:superadmin', 'permission:roles.manage'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserRoleController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/status', [UserRoleController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/transfer-superadmin', [UserRoleController::class, 'transfer'])
            ->middleware('role:superadmin')->name('users.transfer');
        });
    });
});
