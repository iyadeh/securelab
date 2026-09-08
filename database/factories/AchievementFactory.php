<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['learning', 'labs', 'completion']),
            'position' => fake()->unique()->numberBetween(1, 10_000),
        ];
    }
}
