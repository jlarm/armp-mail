<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TransactionalMail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransactionalMail>
 */
class TransactionalMailFactory extends Factory
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
            'name' => Str::slug(fake()->unique()->words(3, true)),
            'subject' => fake()->sentence(),
            'html' => $html,
            'structured_html' => $html,
            'store_mail' => true,
            'track_opens' => false,
            'track_clicks' => false,
        ];
    }

    /**
     * Indicate that the mail tracks opens and clicks.
     */
    public function tracked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'track_opens' => true,
            'track_clicks' => true,
        ]);
    }
}
