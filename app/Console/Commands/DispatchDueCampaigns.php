<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Jobs\SendCampaignDispatch;
use App\Models\Campaign;
use App\Models\CampaignDispatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('campaigns:dispatch-due')]
#[Description('Materialise a dispatch for every campaign whose next run is due.')]
class DispatchDueCampaigns extends Command
{
    public function handle(): int
    {
        $ids = Campaign::query()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->whereNotIn('status', [CampaignStatus::CANCELLED->value, CampaignStatus::PAUSED->value])
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$dispatched): void {
                $campaign = Campaign::query()
                    ->whereNotNull('next_run_at')
                    ->where('next_run_at', '<=', now())
                    ->whereNotIn('status', [CampaignStatus::CANCELLED->value, CampaignStatus::PAUSED->value])
                    ->lockForUpdate()
                    ->find($id);

                if ($campaign === null) {
                    return;
                }

                $this->dispatchCampaign($campaign);
                $dispatched++;
            });
        }

        $this->info("Dispatched {$dispatched} campaign(s).");

        return self::SUCCESS;
    }

    private function dispatchCampaign(Campaign $campaign): void
    {
        $dispatch = CampaignDispatch::create([
            'campaign_id' => $campaign->id,
            'status' => 'pending',
            'scheduled_at' => $campaign->next_run_at,
        ]);

        SendCampaignDispatch::dispatch($dispatch);

        $next = $campaign->frequency->nextRunAfter(Carbon::now());

        $campaign->forceFill([
            'last_sent_at' => now(),
            'next_run_at' => $next,
            'status' => $next === null ? CampaignStatus::SENT->value : CampaignStatus::SENDING->value,
            'sent_at' => $campaign->sent_at ?? now(),
        ])->save();
    }
}
