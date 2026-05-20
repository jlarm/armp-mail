<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Send;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Send>
 */
class SendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::ulid(),
            'sendable_type' => Campaign::class,
            'sendable_id' => Campaign::factory(),
            'subscriber_id' => Subscriber::factory(),
            'transport_message_id' => null,
        ];
    }

    /**
     * Indicate that the message was accepted by the transport.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transport_message_id' => fake()->uuid(),
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the message failed to send.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'failed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'failure_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the recipient opened the message.
     */
    public function opened(): static
    {
        return $this->sent()->state(fn (array $attributes): array => [
            'opened_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the recipient clicked a link in the message.
     */
    public function clicked(): static
    {
        return $this->opened()->state(fn (array $attributes): array => [
            'clicked_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the message bounced.
     */
    public function bounced(): static
    {
        return $this->sent()->state(fn (array $attributes): array => [
            'bounced_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the recipient marked the message as spam.
     */
    public function complained(): static
    {
        return $this->sent()->state(fn (array $attributes): array => [
            'complained_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
