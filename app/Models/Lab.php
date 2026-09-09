<?php

namespace App\Models;

use Database\Factories\LabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $vulnerability_type
 * @property string $difficulty
 * @property string $scenario
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Lesson> $lessons
 * @property-read Collection<int, LabProgress> $progress
 */
#[Fillable(['slug', 'title', 'description', 'vulnerability_type', 'difficulty', 'scenario'])]
class Lab extends Model
{
    /** @use HasFactory<LabFactory> */
    use HasFactory;

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * @return HasMany<LabProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LabProgress::class);
    }
}
