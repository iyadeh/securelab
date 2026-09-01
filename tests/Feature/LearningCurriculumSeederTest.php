<?php

use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;

test('seeds the initial learning path in deterministic module and lesson order', function () {
    $this->seed(DatabaseSeeder::class);

    $learningPath = LearningPath::query()
        ->with('learningModules.lessons')
        ->where('slug', 'web-security-fundamentals')
        ->firstOrFail();
    $lessonsByModule = $learningPath->learningModules->mapWithKeys(
        fn (LearningModule $learningModule): array => [
            $learningModule->title => $learningModule->lessons->pluck('slug')->all(),
        ],
    )->all();

    expect($learningPath->title)->toBe('Web Security Fundamentals');
    expect($learningPath->difficulty)->toBe('easy');
    expect($learningPath->learningModules->pluck('title')->all())->toBe([
        'Injection',
        'Cross-Site Scripting',
        'Access & Account Security',
    ]);
    expect($lessonsByModule)->toBe([
        'Injection' => ['sql-injection-authentication-bypass'],
        'Cross-Site Scripting' => ['reflected-xss', 'stored-xss'],
        'Access & Account Security' => ['idor', 'weak-password-reset'],
    ]);

    $learningPath->learningModules->flatMap->lessons->each(function (Lesson $lesson): void {
        expect($lesson->content)
            ->toContain('## Learn')
            ->toContain('## Understand')
            ->toContain('## Exploit')
            ->toContain('## Analyze')
            ->toContain('## Fix');
    });
});

test('seeds exactly the five canonical labs and links each topic lesson', function () {
    $this->seed(DatabaseSeeder::class);

    $labs = Lab::query()->orderBy('slug')->get();
    $lessonLabSlugs = Lesson::query()
        ->with('lab')
        ->get()
        ->mapWithKeys(fn (Lesson $lesson): array => [$lesson->slug => $lesson->lab?->slug])
        ->sortKeys()
        ->all();

    expect($labs)->toHaveCount(5);
    expect($labs->pluck('slug')->all())->toBe([
        'idor',
        'reflected-xss',
        'sql-injection-authentication-bypass',
        'stored-xss',
        'weak-password-reset',
    ]);
    expect($labs->pluck('difficulty', 'slug')->all())->toBe([
        'idor' => 'easy',
        'reflected-xss' => 'easy',
        'sql-injection-authentication-bypass' => 'easy',
        'stored-xss' => 'medium',
        'weak-password-reset' => 'medium',
    ]);
    expect($lessonLabSlugs)->toBe([
        'idor' => 'idor',
        'reflected-xss' => 'reflected-xss',
        'sql-injection-authentication-bypass' => 'sql-injection-authentication-bypass',
        'stored-xss' => 'stored-xss',
        'weak-password-reset' => 'weak-password-reset',
    ]);
});

test('can run twice without duplicates or deleting independent labs', function () {
    $independentLab = Lab::factory()->create([
        'slug' => 'catalog-only-lab',
        'title' => 'Catalog-only Lab',
    ]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $this->assertModelExists($independentLab);
    expect($independentLab->refresh()->title)->toBe('Catalog-only Lab');
    expect(LearningPath::query()->count())->toBe(1);
    expect(LearningModule::query()->count())->toBe(3);
    expect(Lesson::query()->count())->toBe(5);
    expect(Lab::query()->count())->toBe(6);
});
