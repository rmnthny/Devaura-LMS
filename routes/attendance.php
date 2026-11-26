<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Middleware\EnsureStaffAppIsAvailable;
use Illuminate\Support\Facades\Route;

Route::get('/kiosk', [AttendanceController::class, 'kiosk'])->middleware(EnsureStaffAppIsAvailable::class)->name('kiosk.index');
Route::post('/kiosk', [AttendanceController::class, 'kioskRecord'])
    ->middleware(['throttle:30,1', EnsureStaffAppIsAvailable::class])->name('kiosk.record');

Route::middleware(['secure', 'role:superadmin|admin|staff|maintenance'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::post('/own', [AttendanceController::class, 'own'])->name('own');
});
Route::middleware(['secure', 'permission:attendance.view'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::post('/', [AttendanceController::class, 'assistedRecord'])->name('record');
    Route::post('/{visit}/time-out', [AttendanceController::class, 'force'])
        ->whereNumber('visit')->middleware('role:superadmin|admin')->name('force');
});
