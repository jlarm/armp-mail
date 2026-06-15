<?php

use App\Http\Controllers\ListsController;
use App\Http\Controllers\ListSubscriberController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('lists', [ListsController::class, 'index'])->name('lists.index');
    Route::post('lists', [ListsController::class, 'store'])->name('lists.store');
    Route::get('lists/{list:slug}', [ListsController::class, 'show'])->name('lists.show');
    Route::get('lists/{list:slug}/edit', [ListsController::class, 'edit'])->name('lists.edit');
    Route::put('lists/{list:slug}', [ListsController::class, 'update'])->name('lists.update');
    Route::post('lists/{list:slug}/subscribers', [ListSubscriberController::class, 'store'])->name('lists.subscribers.store');
    Route::post('lists/{list:slug}/subscribers/import', [ListSubscriberController::class, 'import'])->name('lists.subscribers.import');

    Route::scopeBindings()->group(function (): void {
        Route::get('lists/{list:slug}/subscribers/{subscriber}/edit', [ListSubscriberController::class, 'edit'])->name('lists.subscribers.edit');
        Route::put('lists/{list:slug}/subscribers/{subscriber}', [ListSubscriberController::class, 'update'])->name('lists.subscribers.update');
        Route::post('lists/{list:slug}/subscribers/{subscriber}/unsubscribe', [ListSubscriberController::class, 'unsubscribe'])->name('lists.subscribers.unsubscribe');
        Route::delete('lists/{list:slug}/subscribers/{subscriber}', [ListSubscriberController::class, 'destroy'])->name('lists.subscribers.destroy');
    });
});

require __DIR__.'/settings.php';
