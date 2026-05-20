<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Type;
use App\Models\CustomField;
use App\Models\EmailList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'email_list_id' => EmailList::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(Type::cases()),
        ];
    }
}
