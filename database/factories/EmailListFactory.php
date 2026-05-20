<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailList>
 */
class EmailListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->optional()->sentence(),
            'default_from_email' => fake()->safeEmail(),
            'default_from_name' => fake()->name(),
            'default_reply_to_email' => fake()->optional()->safeEmail(),
            'requires_confirmation' => fake()->boolean(),
            'redirect_after_subscribed' => fake()->optional()->url(),
            'campaign_mails_per_minute' => fake()->optional()->numberBetween(10, 120),
        ];
    }
}
