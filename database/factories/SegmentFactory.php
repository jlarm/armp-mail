<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailList;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Segment>
 */
class SegmentFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'rules' => [
                [
                    'field' => fake()->randomElement(['email', 'first_name', 'last_name']),
                    'operator' => fake()->randomElement(['equals', 'contains', 'starts_with']),
                    'value' => fake()->word(),
                ],
            ],
        ];
    }
}
