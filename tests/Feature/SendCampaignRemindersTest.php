<?php

declare(strict_types=1);

use App\Console\Commands\SendCampaignReminders;
use App\Enums\CampaignFrequency;
use App\Enums\CampaignStatus;
use App\Mail\CampaignReminderMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    Cache::flush();
});

it('sends a reminder for a recurring campaign due tomorrow', function (): void {
    User::factory()->create(['email' => 'admin@example.com']);

    Campaign::factory()->create([
        'name' => 'Weekly Newsletter',
        'frequency' => CampaignFrequency::WEEKLY,
        'status' => CampaignStatus::SENDING,
        'next_run_at' => now()->addHours(24),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertSent(CampaignReminderMail::class, 1);
    Mail::assertSent(CampaignReminderMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
});

it('does not send a reminder for a once-only campaign', function (): void {
    User::factory()->create();

    Campaign::factory()->create([
        'frequency' => CampaignFrequency::ONCE,
        'next_run_at' => now()->addHours(24),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertNothingSent();
});

it('does not send a reminder for a paused campaign', function (): void {
    User::factory()->create();

    Campaign::factory()->create([
        'frequency' => CampaignFrequency::WEEKLY,
        'status' => CampaignStatus::PAUSED,
        'next_run_at' => now()->addHours(24),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertNothingSent();
});

it('does not send a reminder for a campaign not due tomorrow', function (): void {
    User::factory()->create();

    Campaign::factory()->create([
        'frequency' => CampaignFrequency::WEEKLY,
        'status' => CampaignStatus::SENDING,
        'next_run_at' => now()->addDays(5),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertNothingSent();
});

it('does not send duplicate reminders for the same campaign on the same day', function (): void {
    User::factory()->create();

    Campaign::factory()->create([
        'frequency' => CampaignFrequency::MONTHLY,
        'status' => CampaignStatus::SENDING,
        'next_run_at' => now()->addHours(24),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();
    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertSent(CampaignReminderMail::class, 1);
});

it('sends to all users', function (): void {
    User::factory()->count(3)->create();

    Campaign::factory()->create([
        'frequency' => CampaignFrequency::BIWEEKLY,
        'status' => CampaignStatus::SENDING,
        'next_run_at' => now()->addHours(24),
    ]);

    $this->artisan(SendCampaignReminders::class)->assertSuccessful();

    Mail::assertSent(CampaignReminderMail::class, 3);
});
