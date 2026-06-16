<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignFrequency;
use App\Enums\CampaignStatus;
use App\Mail\CampaignReminderMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

#[Signature('campaigns:send-reminders')]
#[Description('Send a day-before reminder email for each recurring campaign due tomorrow.')]
class SendCampaignReminders extends Command
{
    public function handle(): int
    {
        $recipients = User::pluck('email')->all();

        if (empty($recipients)) {
            return self::SUCCESS;
        }

        $due = Campaign::query()
            ->whereNotNull('next_run_at')
            ->whereBetween('next_run_at', [now()->addHours(20), now()->addHours(28)])
            ->whereNotIn('status', [CampaignStatus::CANCELLED->value, CampaignStatus::PAUSED->value])
            ->whereNotIn('frequency', [CampaignFrequency::ONCE->value])
            ->with('emailList')
            ->get();

        $sent = 0;

        foreach ($due as $campaign) {
            // Deduplicate: one reminder per campaign per next_run_at window.
            $cacheKey = 'campaign_reminder_'.$campaign->id.'_'.$campaign->next_run_at->format('Ymd');

            if (Cache::has($cacheKey)) {
                continue;
            }

            $mailable = new CampaignReminderMail($campaign);

            foreach ($recipients as $email) {
                Mail::to($email)->send($mailable);
            }

            Cache::put($cacheKey, true, now()->addHours(30));
            $sent++;
        }

        $this->info("Sent reminders for {$sent} campaign(s).");

        return self::SUCCESS;
    }
}
