<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SuppressionReason;
use App\Models\Suppression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suppression>
 */
class SuppressionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'reason' => fake()->randomElement(SuppressionReason::cases()),
            'notes' => null,
            'suppressed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the address was suppressed manually with a note.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => SuppressionReason::MANUAL,
            'notes' => fake()->sentence(),
        ]);
    }
}
