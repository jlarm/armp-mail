<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Automation;
use App\Models\AutomationActionSubscriber;
use App\Models\AutomationStep;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationActionSubscriber>
 */
class AutomationActionSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'automation_id' => Automation::factory(),
            'automation_step_id' => fn (array $attributes): AutomationStep => AutomationStep::factory()
                ->for(Automation::findOrFail($attributes['automation_id']))
                ->create(),
            'subscriber_id' => Subscriber::factory(),
            'run_at' => fake()->dateTimeBetween('now', '+1 day'),
            'completed_at' => null,
            'halted_at' => null,
        ];
    }

    /**
     * Indicate that the step has finished running for the subscriber.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'run_at' => fake()->dateTimeBetween('-1 day', '-1 hour'),
            'completed_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the subscriber was halted before completing the step.
     */
    public function halted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'halted_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
