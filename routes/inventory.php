<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogLookupController;
use App\Http\Controllers\InventoryAuditController;
use App\Http\Controllers\LibraryImportController;
use App\Http\Controllers\PersonnelScheduleController;
use App\Services\CatalogLookups;
use Illuminate\Support\Facades\Route;

Route::middleware('secure')->group(function () {
    Route::get('/books', [BookController::class, 'index'])->middleware('permission:books.view')->name('books.index');
    Route::middleware('role:superadmin|admin')->group(function () {
        Route::get('/books/import-template', [LibraryImportController::class, 'template'])->defaults('kind', 'books')->name('books.import-template');
        Route::post('/books/import', [LibraryImportController::class, 'store'])->defaults('kind', 'books')->name('books.import');
    });
    Route::middleware(['role:superadmin|admin', 'permission:books.manage'])->group(function () {
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
    });
    Route::middleware('permission:books.update')->group(function () {
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    });
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->middleware(['role:superadmin', 'permission:books.delete'])->name('books.destroy');
    Route::middleware('permission:books.qr')->group(function () {
        Route::post('/books/qr-sheet', [BookController::class, 'qrSheet'])->name('books.qr-sheet');
        Route::get('/books/{book}/label', [BookController::class, 'label'])->name('books.label');
        Route::get('/books/{book}/qr.png', [BookController::class, 'png'])->name('books.png');
    });

    Route::prefix('/catalog/{lookup}')->where(['lookup' => implode('|', array_keys(CatalogLookups::TYPES))])->name('catalog.')->group(function () {
        Route::get('/', [CatalogLookupController::class, 'index'])->middleware('permission:books.view')->name('index');
        Route::middleware(['role:superadmin|admin', 'permission:books.manage'])->group(function () {
            Route::post('/', [CatalogLookupController::class, 'store'])->name('store');
        });
        Route::put('/{record}', [CatalogLookupController::class, 'update'])->whereNumber('record')
            ->middleware('permission:books.update')->name('update');
        Route::delete('/{record}', [CatalogLookupController::class, 'destroy'])->whereNumber('record')
            ->middleware('permission:books.delete')->name('destroy');
    });

    Route::middleware('permission:inventory.audit')->prefix('/inventory/audit')->name('inventory.')->group(function () {
        Route::get('/', [InventoryAuditController::class, 'index'])->name('index');
        Route::post('/', [InventoryAuditController::class, 'store'])->name('store');
    });

    Route::prefix('/personnel/schedules')->name('schedules.')->group(function () {
        Route::get('/', [PersonnelScheduleController::class, 'index'])
            ->middleware('permission:schedules.view-own')->name('index');
        Route::middleware('permission:schedules.manage-staff|schedules.manage-admin')->group(function () {
            Route::post('/', [PersonnelScheduleController::class, 'store'])->name('store');
            Route::put('/{personnel}/{day}/{period}', [PersonnelScheduleController::class, 'update'])
                ->whereNumber(['personnel', 'day', 'period'])->name('update');
            Route::delete('/{personnel}/{day}/{period}', [PersonnelScheduleController::class, 'destroy'])
                ->whereNumber(['personnel', 'day', 'period'])->name('destroy');
        });
    });

    Route::middleware('role:superadmin')->prefix('/archive')->name('archive.')->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index');
        Route::get('/members/{record}/delete', [ArchiveController::class, 'confirmMemberPurge'])
            ->whereNumber('record')->name('members.confirm-purge');
        Route::delete('/members/{record}', [ArchiveController::class, 'purgeMember'])
            ->whereNumber('record')->name('members.purge');
        Route::post('/{type}/{record}/restore', [ArchiveController::class, 'restore'])
            ->whereIn('type', ['books', 'members', 'personnel', 'accounts'])->whereNumber('record')->name('restore');
    });
    Route::middleware(['role:superadmin', 'permission:personnel.delete'])->group(function () {
        Route::delete('/archive/personnel/{personnel}', [ArchiveController::class, 'archivePersonnel'])->name('archive.personnel');
        Route::delete('/archive/accounts/{account}', [ArchiveController::class, 'archiveAccount'])->name('archive.accounts');
    });
});
