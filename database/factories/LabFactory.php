<?php

namespace Database\Factories;

use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lab>
 */
class LabFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'description' => fake()->paragraph(),
            'vulnerability_type' => fake()->randomElement([
                'access_control',
                'authentication',
                'cross_site_scripting',
                'sql_injection',
            ]),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'scenario' => fake()->sentence(),
        ];
    }
}
