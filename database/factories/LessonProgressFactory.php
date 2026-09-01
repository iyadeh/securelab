<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProgress>
 */
class LessonProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the lesson has been started but not completed.
     */
    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the lesson has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }
}
