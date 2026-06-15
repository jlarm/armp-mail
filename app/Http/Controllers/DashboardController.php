<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignDispatch;
use App\Models\Subscriber;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'newSubscribers' => Subscriber::where('created_at', '>=', now()->subDays(30))->count(),
            'campaigns' => [
                'draft' => Campaign::where('status', CampaignStatus::DRAFT->value)->count(),
                'scheduled' => Campaign::whereNotNull('next_run_at')->count(),
                'sent' => CampaignDispatch::where('status', 'sent')->count(),
            ],
            'latestCampaign' => $this->latestCampaign(),
            'audienceGrowth' => $this->audienceGrowth(),
        ]);
    }

    /**
     * The most recently sent campaign with its rolled-up engagement.
     *
     * @return array<string, mixed>|null
     */
    private function latestCampaign(): ?array
    {
        $dispatch = CampaignDispatch::query()
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->with('campaign:id,name')
            ->latest('sent_at')
            ->first();

        if ($dispatch?->campaign === null) {
            return null;
        }

        $campaign = $dispatch->campaign;

        $totals = $campaign->dispatches()
            ->where('status', 'sent')
            ->selectRaw('SUM(unique_open_count) AS opens, SUM(unique_click_count) AS clicks, SUM(unsubscribe_count) AS unsubs, SUM(bounce_count) AS bounces')
            ->first();

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'opens' => (int) ($totals->opens ?? 0),
            'clicks' => (int) ($totals->clicks ?? 0),
            'unsubs' => (int) ($totals->unsubs ?? 0),
            'bounces' => (int) ($totals->bounces ?? 0),
        ];
    }

    /**
     * Cumulative subscriber totals over the last 60 days.
     *
     * @return array<int, array{date: string, total: int}>
     */
    private function audienceGrowth(): array
    {
        $start = now()->subDays(60)->startOfDay();

        $running = Subscriber::where('created_at', '<', $start)->count();

        $perDay = Subscriber::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $series = [];

        for ($offset = 0; $offset <= 60; $offset++) {
            $day = $start->copy()->addDays($offset);
            $running += (int) ($perDay[$day->toDateString()] ?? 0);

            $series[] = ['date' => $day->toDateString(), 'total' => $running];
        }

        return $series;
    }
}
