<?php

namespace Database\Factories;

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabProgress>
 */
class LabProgressFactory extends Factory
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
            'lab_id' => Lab::factory(),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the lab has been started but not completed.
     */
    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the lab has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }
}
