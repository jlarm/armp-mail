<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SendFeedbackType;
use App\Models\Send;
use App\Models\SendFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SendFeedback>
 */
class SendFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'send_id' => Send::factory(),
            'type' => fake()->randomElement(SendFeedbackType::cases()),
            'url' => null,
            'user_agent' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            'payload' => null,
            'happened_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the event is an open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SendFeedbackType::OPEN,
        ]);
    }

    /**
     * Indicate that the event is a link click.
     */
    public function click(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SendFeedbackType::CLICK,
            'url' => fake()->url(),
        ]);
    }

    /**
     * Indicate that the event is a bounce, carrying the raw webhook payload.
     */
    public function bounce(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SendFeedbackType::BOUNCE,
            'payload' => ['bounceType' => 'Permanent', 'diagnosticCode' => fake()->sentence()],
        ]);
    }
}
