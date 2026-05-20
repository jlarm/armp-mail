<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
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
            'name' => fake()->words(3, true),
            'html' => $html,
            'content_json' => ['pages' => [], 'styles' => [], 'assets' => []],
            'structured_html' => $html,
        ];
    }
}
