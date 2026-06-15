<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard reports headline stats', function () {
    $this->actingAs(User::factory()->create());

    Subscriber::factory()->count(2)->create(['created_at' => now()->subDays(3)]);
    Subscriber::factory()->create(['created_at' => now()->subDays(45)]);

    Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);
    Campaign::factory()->create([
        'status' => CampaignStatus::SENDING,
        'next_run_at' => now()->addWeek(),
    ]);

    $this->get(route('dashboard'))->assertInertia(
        fn ($page) => $page
            ->component('Dashboard')
            ->where('newSubscribers', 2)
            ->where('campaigns.draft', 1)
            ->where('campaigns.scheduled', 1)
            ->where('campaigns.sent', 0)
            ->has('audienceGrowth', 61)
    );
});

test('the dashboard surfaces the latest sent campaign engagement', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create(['name' => 'Weekly digest']);
    $campaign->dispatches()->create([
        'status' => 'sent',
        'sent_at' => now(),
        'sent_to_count' => 100,
        'unique_open_count' => 40,
        'unique_click_count' => 12,
        'unsubscribe_count' => 1,
        'bounce_count' => 3,
    ]);

    $this->get(route('dashboard'))->assertInertia(
        fn ($page) => $page
            ->where('latestCampaign.name', 'Weekly digest')
            ->where('latestCampaign.opens', 40)
            ->where('latestCampaign.clicks', 12)
            ->where('latestCampaign.bounces', 3)
    );
});
