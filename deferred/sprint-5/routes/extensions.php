<?php

use App\Http\Controllers\BookRepairController;
use App\Http\Controllers\InventorySessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['secure', 'role:superadmin', 'permission:inventory.audit'])
    ->prefix('/inventory/audit/sessions')->name('inventory.sessions.')->group(function () {
        Route::get('/', [InventorySessionController::class, 'index'])->name('index');
        Route::post('/', [InventorySessionController::class, 'store'])->name('store');
        Route::get('/{session}', [InventorySessionController::class, 'show'])->whereNumber('session')->name('show');
        Route::patch('/{session}/items/{item}', [InventorySessionController::class, 'update'])
            ->whereNumber(['session', 'item'])->name('items.update');
        Route::patch('/{session}', [InventorySessionController::class, 'finish'])->whereNumber('session')->name('finish');
    });

Route::middleware(['secure', 'permission:circulation.process'])
    ->prefix('/circulation/repairs')->name('circulation.repairs.')->group(function () {
        Route::get('/', [BookRepairController::class, 'index'])->name('index');
        Route::patch('/{repair}', [BookRepairController::class, 'complete'])->whereNumber('repair')->name('complete');
    });
