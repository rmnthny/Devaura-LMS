<?php

use App\Http\Controllers\CirculationController;
use App\Http\Controllers\LibraryNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['secure', 'permission:circulation.process'])->prefix('circulation')->name('circulation.')->group(function () {
    Route::get('/borrow', [CirculationController::class, 'borrowForm'])->name('borrow.form');
    Route::post('/borrow', [CirculationController::class, 'borrow'])->name('borrow');
    Route::get('/return', [CirculationController::class, 'returnForm'])->name('return.form');
    Route::post('/return', [CirculationController::class, 'checkIn'])->name('return');
});

Route::middleware('secure')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [LibraryNotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [LibraryNotificationController::class, 'read'])->whereUuid('notification')->name('read');
});
