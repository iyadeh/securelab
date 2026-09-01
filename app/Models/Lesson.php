<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $learning_module_id
 * @property int|null $lab_id
 * @property string $slug
 * @property string $title
 * @property string $summary
 * @property string $content
 * @property int $estimated_minutes
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LearningModule $learningModule
 * @property-read Lab|null $lab
 * @property-read Collection<int, LessonProgress> $progress
 */
#[Fillable([
    'learning_module_id',
    'lab_id',
    'slug',
    'title',
    'summary',
    'content',
    'estimated_minutes',
    'position',
])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    public function learningModule(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'position' => 'integer',
        ];
    }
}
