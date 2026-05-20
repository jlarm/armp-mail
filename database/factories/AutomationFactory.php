<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomationStatus;
use App\Models\Automation;
use App\Models\EmailList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Automation>
 */
class AutomationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_list_id' => EmailList::factory(),
            'name' => fake()->words(3, true),
            'status' => AutomationStatus::PAUSED,
            'triggers' => [
                ['type' => 'subscribed', 'config' => []],
            ],
        ];
    }

    /**
     * Indicate that the automation is active and has run.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AutomationStatus::ACTIVE,
            'last_ran_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
