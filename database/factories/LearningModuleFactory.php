<?php

namespace Database\Factories;

use App\Models\LearningModule;
use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LearningModule>
 */
class LearningModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_path_id' => LearningPath::factory(),
            'title' => Str::title(fake()->sentence(3)),
            'description' => fake()->paragraph(),
            'position' => fake()->unique()->numberBetween(1, 10_000),
        ];
    }
}
