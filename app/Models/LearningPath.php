<?php

namespace App\Models;

use Database\Factories\LearningPathFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $difficulty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, LearningModule> $learningModules
 * @property-read Collection<int, Lesson> $lessons
 */
#[Fillable(['slug', 'title', 'description', 'difficulty'])]
class LearningPath extends Model
{
    /** @use HasFactory<LearningPathFactory> */
    use HasFactory;

    public function learningModules(): HasMany
    {
        return $this->hasMany(LearningModule::class)->orderBy('position');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, LearningModule::class)
            ->orderBy('learning_modules.position')
            ->orderBy('lessons.position');
    }
}
