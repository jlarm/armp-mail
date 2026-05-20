<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomationStepType;
use App\Models\Automation;
use App\Models\AutomationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationStep>
 */
class AutomationStepFactory extends Factory
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
            'type' => fake()->randomElement(AutomationStepType::cases()),
            'order' => fake()->numberBetween(0, 10),
            'parent_step_id' => null,
            'config' => [],
        ];
    }

    /**
     * Indicate that the step sends an email.
     */
    public function sendMail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => AutomationStepType::SEND_MAIL,
            'config' => [
                'subject' => fake()->sentence(),
                'html' => '<h1>'.fake()->sentence().'</h1>',
            ],
        ]);
    }

    /**
     * Indicate that the step waits for a duration before continuing.
     */
    public function wait(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => AutomationStepType::WAIT,
            'config' => [
                'duration' => fake()->numberBetween(1, 72),
                'unit' => 'hours',
            ],
        ]);
    }
}
