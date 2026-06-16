<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignTrackingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ListsController;
use App\Http\Controllers\ListSegmentController;
use App\Http\Controllers\ListSubscriberController;
use App\Http\Controllers\ListTagController;
use App\Http\Controllers\MailgunWebhookController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public email tracking endpoints (hit by recipients, no auth).
Route::middleware('throttle:120,1')->group(function () {
    Route::get('e/o/{send:uuid}', [CampaignTrackingController::class, 'open'])->name('campaigns.track.open');
    Route::get('e/c/{send:uuid}', [CampaignTrackingController::class, 'click'])->name('campaigns.track.click');
    Route::get('e/u/{send:uuid}', [CampaignTrackingController::class, 'unsubscribe'])->name('campaigns.track.unsubscribe');
});

// Mailgun webhook — signature verified inside the controller.
Route::post('webhooks/mailgun', [MailgunWebhookController::class, 'handle'])->name('webhooks.mailgun');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('campaigns', CampaignController::class)->except(['show']);
    Route::post('campaigns/{campaign}/test', [CampaignController::class, 'test'])->name('campaigns.test');
    Route::post('campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])->name('campaigns.send-now');

    Route::post('templates/images', [TemplateController::class, 'uploadImage'])->name('templates.images');
    Route::resource('templates', TemplateController::class)->except(['show']);
    Route::post('templates/{template}/campaign', [TemplateController::class, 'campaign'])->name('templates.campaign');
    Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::post('templates/{template}/test', [TemplateController::class, 'test'])->name('templates.test');

    Route::get('lists', [ListsController::class, 'index'])->name('lists.index');
    Route::post('lists', [ListsController::class, 'store'])->name('lists.store');
    Route::get('lists/{list:slug}', [ListsController::class, 'show'])->name('lists.show');
    Route::get('lists/{list:slug}/edit', [ListsController::class, 'edit'])->name('lists.edit');
    Route::put('lists/{list:slug}', [ListsController::class, 'update'])->name('lists.update');

    Route::get('lists/{list:slug}/tags', [ListTagController::class, 'index'])->name('lists.tags.index');
    Route::delete('lists/{list:slug}/tags', [ListTagController::class, 'destroy'])->name('lists.tags.destroy');

    Route::get('lists/{list:slug}/segments', [ListSegmentController::class, 'index'])->name('lists.segments.index');
    Route::get('lists/{list:slug}/segments/create', [ListSegmentController::class, 'create'])->name('lists.segments.create');
    Route::post('lists/{list:slug}/segments', [ListSegmentController::class, 'store'])->name('lists.segments.store');
    Route::scopeBindings()->group(function (): void {
        Route::get('lists/{list:slug}/segments/{segment}/edit', [ListSegmentController::class, 'edit'])->name('lists.segments.edit');
        Route::put('lists/{list:slug}/segments/{segment}', [ListSegmentController::class, 'update'])->name('lists.segments.update');
        Route::delete('lists/{list:slug}/segments/{segment}', [ListSegmentController::class, 'destroy'])->name('lists.segments.destroy');
    });
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
