<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var list<array{slug: string, title: string, description: string, category: string, position: int}> $achievements */
        $achievements = [
            [
                'slug' => 'first-lesson',
                'title' => 'First Lesson',
                'description' => 'Complete your first SecureLab lesson.',
                'category' => 'learning',
                'position' => 1,
            ],
            [
                'slug' => 'first-lab',
                'title' => 'First Blood',
                'description' => 'Complete your first SecureLab guided lab.',
                'category' => 'labs',
                'position' => 2,
            ],
            [
                'slug' => 'path-complete',
                'title' => 'Path Complete',
                'description' => 'Complete every lesson in a SecureLab learning path.',
                'category' => 'learning',
                'position' => 3,
            ],
            [
                'slug' => 'lab-series-complete',
                'title' => 'Lab Series Complete',
                'description' => 'Complete every currently available SecureLab lab.',
                'category' => 'labs',
                'position' => 4,
            ],
            [
                'slug' => 'security-foundations-complete',
                'title' => 'Security Foundations Complete',
                'description' => 'Complete all available SecureLab lessons and labs.',
                'category' => 'completion',
                'position' => 5,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::query()->updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement,
            );
        }
    }
}
