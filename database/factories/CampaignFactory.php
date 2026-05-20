<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\EmailList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $html = '<h1>'.fake()->sentence().'</h1><p>'.fake()->paragraph().'</p>';

        return [
            'email_list_id' => EmailList::factory(),
            'segment_id' => null,
            'template_id' => null,
            'name' => fake()->words(3, true),
            'subject' => fake()->sentence(),
            'from_email' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'reply_to_email' => fake()->safeEmail(),
            'html' => $html,
            'content_json' => ['pages' => [], 'styles' => [], 'assets' => []],
            'structured_html' => $html,
            'status' => CampaignStatus::DRAFT,
            'track_opens' => true,
            'track_clicks' => true,
        ];
    }

    /**
     * Indicate that the campaign is scheduled to send in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::DRAFT,
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
        ]);
    }

    /**
     * Indicate that the campaign is currently sending.
     */
    public function sending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::SENDING,
        ]);
    }

    /**
     * Indicate that the campaign has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::SENT,
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
