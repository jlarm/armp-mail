<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignDispatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignDispatch>
 */
class CampaignDispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'status' => 'sent',
            'scheduled_at' => now(),
            'sent_at' => now(),
            'sent_to_count' => 0,
            'open_count' => 0,
            'unique_open_count' => 0,
            'click_count' => 0,
            'unique_click_count' => 0,
            'bounce_count' => 0,
            'unsubscribe_count' => 0,
        ];
    }
}
