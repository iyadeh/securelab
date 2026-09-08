<?php

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Tests\TestCase;

const CANONICAL_ACHIEVEMENT_SLUGS = [
    'first-lesson',
    'first-lab',
    'path-complete',
    'lab-series-complete',
    'security-foundations-complete',
];

/**
 * @return EloquentCollection<int, Lesson>
 */
function canonicalAchievementLessons(): EloquentCollection
{
    return Lesson::query()
        ->select('lessons.*')
        ->join('learning_modules', 'learning_modules.id', '=', 'lessons.learning_module_id')
        ->join('learning_paths', 'learning_paths.id', '=', 'learning_modules.learning_path_id')
        ->where('learning_paths.slug', 'web-security-fundamentals')
        ->orderBy('learning_modules.position')
        ->orderBy('lessons.position')
        ->get();
}

function achievementLessonRoute(string $action, Lesson $lesson): string
{
    $lesson->loadMissing('learningModule.learningPath');

    return route("lessons.{$action}", [
        'learningPath' => $lesson->learningModule->learningPath->slug,
        'lesson' => $lesson->slug,
    ]);
}

function completeAchievementLesson(TestCase $testCase, User $user, Lesson $lesson): void
{
    $testCase->actingAs($user)
        ->post(achievementLessonRoute('complete', $lesson))
        ->assertRedirect();
}

function completeAchievementLab(TestCase $testCase, User $user, Lab $lab): void
{
    $testCase->actingAs($user)
        ->post(route('labs.complete', ['lab' => $lab->slug]))
        ->assertRedirect();
}

test('canonical achievements seed exactly once with stable metadata', function () {
    $this->seed(AchievementSeeder::class);
    $this->seed(AchievementSeeder::class);

    expect(Achievement::query()->orderBy('position')->pluck('slug')->all())
        ->toBe(CANONICAL_ACHIEVEMENT_SLUGS)
        ->and(Achievement::query()->orderBy('position')->pluck('category')->all())
        ->toBe(['learning', 'labs', 'learning', 'labs', 'completion']);
    $this->assertDatabaseCount('achievements', 5);
    $this->assertDatabaseCount('user_achievements', 0);
});

test('first lesson and first lab are awarded from trusted completion actions', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lesson = canonicalAchievementLessons()->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $this->travelTo('2026-09-08 10:00:00');

    $this->actingAs($user)->post(achievementLessonRoute('complete', $lesson), [
        'user_id' => $otherUser->id,
        'achievement_slug' => 'security-foundations-complete',
        'unlocked_at' => '2000-01-01 00:00:00',
        'completed_count' => 999,
    ])->assertRedirect();

    $this->assertDatabaseHas('user_achievements', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('slug', 'first-lesson')->value('id'),
        'unlocked_at' => '2026-09-08 10:00:00',
    ]);
    expect($user->userAchievements()->count())->toBe(1)
        ->and($otherUser->userAchievements()->count())->toBe(0);

    $this->travelTo('2026-09-08 11:00:00');
    $this->actingAs($user)->post(route('labs.complete', ['lab' => $lab->slug]), [
        'user_id' => $otherUser->id,
        'achievement_id' => Achievement::query()->where('slug', 'path-complete')->value('id'),
        'unlocked_at' => '2000-01-01 00:00:00',
    ])->assertRedirect();

    $this->assertDatabaseHas('user_achievements', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('slug', 'first-lab')->value('id'),
        'unlocked_at' => '2026-09-08 11:00:00',
    ]);
    expect($user->userAchievements()->count())->toBe(2)
        ->and($otherUser->userAchievements()->count())->toBe(0);
});

test('starting lessons and labs or simulating never awards achievements', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = canonicalAchievementLessons()->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();

    $this->actingAs($user)->post(achievementLessonRoute('start', $lesson))->assertRedirect();
    $this->actingAs($user)->post(route('labs.start', ['lab' => $lab->slug]))->assertRedirect();
    $this->actingAs($user)->post(route('labs.simulate', ['lab' => $lab->slug]), [
        'input' => ['resource_id' => '2002'],
    ])->assertRedirect();

    $this->assertDatabaseCount('user_achievements', 0);
});

test('path completion requires every lesson in one non-empty path', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lessons = canonicalAchievementLessons();
    $incompletePath = LearningPath::factory()->create();
    $incompleteModule = LearningModule::factory()->for($incompletePath)->create();
    Lesson::factory()->for($incompleteModule)->create();

    foreach ($lessons->take($lessons->count() - 1) as $lesson) {
        completeAchievementLesson($this, $user, $lesson);
    }

    expect($user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'path-complete'))
        ->exists())->toBeFalse();

    completeAchievementLesson($this, $user, $lessons->last());

    expect($user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'path-complete'))
        ->exists())->toBeTrue()
        ->and($user->userAchievements()
            ->whereHas('achievement', fn ($query) => $query->where('slug', 'security-foundations-complete'))
            ->exists())->toBeFalse();
});

test('lab series completion requires every available lab', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $labs = Lab::query()->orderBy('slug')->get();

    foreach ($labs->take($labs->count() - 1) as $lab) {
        completeAchievementLab($this, $user, $lab);
    }

    expect($user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'lab-series-complete'))
        ->exists())->toBeFalse();

    completeAchievementLab($this, $user, $labs->last());

    expect($user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'lab-series-complete'))
        ->exists())->toBeTrue()
        ->and($user->userAchievements()
            ->whereHas('achievement', fn ($query) => $query->where('slug', 'security-foundations-complete'))
            ->exists())->toBeFalse();
});

test('completing both domains awards security foundations and backfills eligible awards', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lessons = canonicalAchievementLessons();
    $labs = Lab::query()->orderBy('slug')->get();

    foreach ($lessons as $lesson) {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    }

    foreach ($labs->take($labs->count() - 1) as $lab) {
        $user->labProgress()->create([
            'lab_id' => $lab->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    expect($user->userAchievements()->count())->toBe(0);

    completeAchievementLab($this, $user, $labs->last());

    expect($user->userAchievements()
        ->with('achievement')
        ->get()
        ->pluck('achievement.slug')
        ->sort()
        ->values()
        ->all())->toBe(collect(CANONICAL_ACHIEVEMENT_SLUGS)->sort()->values()->all());
});

test('repeated completion and synchronization preserve the first unlock time', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = canonicalAchievementLessons()->firstOrFail();
    $this->travelTo('2026-09-08 10:00:00');
    completeAchievementLesson($this, $user, $lesson);
    $award = $user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'first-lesson'))
        ->firstOrFail();

    $this->travelTo('2026-09-08 12:00:00');
    completeAchievementLesson($this, $user, $lesson);
    app(AchievementService::class)->syncFor($user);
    app(AchievementService::class)->syncFor($user);

    expect($user->userAchievements()
        ->whereHas('achievement', fn ($query) => $query->where('slug', 'first-lesson'))
        ->count())->toBe(1)
        ->and($award->fresh()?->unlocked_at->toDateTimeString())->toBe('2026-09-08 10:00:00');
});

test('achievement awards and unlock timestamps are isolated by user', function () {
    $this->seed(DatabaseSeeder::class);
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $lesson = canonicalAchievementLessons()->firstOrFail();
    $this->travelTo('2026-09-08 09:00:00');
    completeAchievementLesson($this, $firstUser, $lesson);
    $firstAward = $firstUser->userAchievements()->firstOrFail();

    expect($secondUser->userAchievements()->count())->toBe(0);

    $this->travelTo('2026-09-08 11:00:00');
    completeAchievementLesson($this, $secondUser, $lesson);

    expect($firstUser->userAchievements()->count())->toBe(1)
        ->and($secondUser->userAchievements()->count())->toBe(1)
        ->and($firstAward->fresh()?->unlocked_at->toDateTimeString())->toBe('2026-09-08 09:00:00')
        ->and($secondUser->userAchievements()->firstOrFail()->unlocked_at->toDateTimeString())
        ->toBe('2026-09-08 11:00:00');
});

test('read-only pages do not backfill awards for existing eligible progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = canonicalAchievementLessons()->firstOrFail();
    LessonProgress::factory()->for($user)->for($lesson)->completed()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('progress'))->assertOk();
    $this->actingAs($user)->get(route('achievements'))->assertOk();

    $this->assertDatabaseCount('user_achievements', 0);
});

test('empty curriculum and catalog never satisfy completion criteria', function () {
    $this->seed(AchievementSeeder::class);
    $user = User::factory()->create();

    app(AchievementService::class)->syncFor($user);
    LearningPath::factory()->create();
    app(AchievementService::class)->syncFor($user);

    $this->assertDatabaseCount('user_achievements', 0);
});

test('achievement models expose typed relationships and cascade awards', function () {
    $user = User::factory()->create();
    $achievement = Achievement::factory()->create();
    $award = UserAchievement::factory()
        ->for($user)
        ->for($achievement)
        ->create(['unlocked_at' => '2026-09-08 10:00:00']);

    expect($award->user->is($user))->toBeTrue()
        ->and($award->achievement->is($achievement))->toBeTrue()
        ->and($user->userAchievements->first()?->is($award))->toBeTrue()
        ->and($achievement->userAchievements->first()?->is($award))->toBeTrue()
        ->and($award->unlocked_at->toDateTimeString())->toBe('2026-09-08 10:00:00');

    $achievement->delete();
    $this->assertDatabaseCount('user_achievements', 0);

    $secondAchievement = Achievement::factory()->create();
    UserAchievement::factory()->for($user)->for($secondAchievement)->create();
    $user->delete();
    $this->assertDatabaseCount('user_achievements', 0);
});

test('database uniqueness rejects duplicate user achievement awards', function () {
    $user = User::factory()->create();
    $achievement = Achievement::factory()->create();
    UserAchievement::factory()->for($user)->for($achievement)->create();

    expect(fn () => UserAchievement::factory()->for($user)->for($achievement)->create())
        ->toThrow(QueryException::class);
});
