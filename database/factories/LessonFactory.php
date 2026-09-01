<?php

namespace Database\Factories;

use App\Models\LearningModule;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
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
            'learning_module_id' => LearningModule::factory(),
            'lab_id' => null,
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'summary' => fake()->sentence(),
            'content' => fake()->text(600),
            'estimated_minutes' => fake()->numberBetween(5, 60),
            'position' => fake()->unique()->numberBetween(1, 10_000),
        ];
    }
}
