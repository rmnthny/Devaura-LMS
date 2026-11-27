<?php

use App\Http\Controllers\LibraryDashboardController;
use App\Http\Controllers\LibraryReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('secure')->group(function () {
    Route::get('/dashboard', [LibraryDashboardController::class, 'index'])
        ->middleware('permission:reports.generate|history.own|borrow.self')->name('dashboard.index');
    Route::get('/my-transactions', [LibraryDashboardController::class, 'history'])
        ->middleware('permission:history.own|borrow.self')->name('history.index');
    Route::get('/reports', [LibraryReportController::class, 'index'])
        ->middleware('permission:reports.generate')->name('reports.index');
    Route::get('/audit-trail', [LibraryReportController::class, 'audit'])
        ->middleware('permission:audit.view')->name('audit.index');
});
