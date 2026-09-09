<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabController extends Controller
{
    /**
     * Display the available lab catalog for the current user.
     */
    public function index(Request $request, LearningProgressService $progressService): Response
    {
        /** @var User $user */
        $user = $request->user();

        $labs = Lab::query()
            ->select(['id', 'slug', 'title', 'description', 'vulnerability_type', 'difficulty'])
            ->with(
                'lessons',
                function (Relation $query): void {
                    $query
                        ->select(['id', 'lab_id', 'learning_module_id', 'slug', 'title'])
                        ->orderBy('slug');
                },
            )
            ->with([
                'lessons.learningModule:id,learning_path_id,title',
                'lessons.learningModule.learningPath:id,slug,title',
            ])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $catalogProgress = $progressService->labCatalogProgress($user, $labs);

        return Inertia::render('labs', [
            'summary' => $catalogProgress['progress'],
            'labs' => $labs->map(function (Lab $lab) use ($catalogProgress): array {
                /** @var Lesson|null $lesson */
                $lesson = $lab->lessons->first();

                return [
                    'slug' => $lab->slug,
                    'title' => $lab->title,
                    'description' => $lab->description,
                    'vulnerability_type' => $lab->vulnerability_type,
                    'difficulty' => $lab->difficulty,
                    'state' => $catalogProgress['lab_states'][$lab->id],
                    'lesson' => $lesson === null
                        ? null
                        : [
                            'slug' => $lesson->slug,
                            'title' => $lesson->title,
                            'module' => [
                                'title' => $lesson->learningModule->title,
                            ],
                            'learning_path' => [
                                'slug' => $lesson->learningModule->learningPath->slug,
                                'title' => $lesson->learningModule->learningPath->title,
                            ],
                        ],
                ];
            })->values(),
        ]);
    }

    /**
     * Display one lab with its related learning context.
     */
    public function show(
        Request $request,
        Lab $lab,
        LearningProgressService $progressService,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $lab->load([
            'lessons' => fn (HasMany $query): HasMany => $query
                ->select(['id', 'lab_id', 'learning_module_id', 'slug', 'title', 'summary'])
                ->orderBy('slug'),
            'lessons.learningModule:id,learning_path_id,title',
            'lessons.learningModule.learningPath:id,slug,title',
        ]);

        /** @var Lesson|null $lesson */
        $lesson = $lab->lessons->first();

        return Inertia::render('lab-detail', [
            'lab' => [
                'slug' => $lab->slug,
                'title' => $lab->title,
                'description' => $lab->description,
                'vulnerability_type' => $lab->vulnerability_type,
                'difficulty' => $lab->difficulty,
                'scenario' => $lab->scenario,
                'state' => $progressService->labState($user, $lab),
            ],
            'lesson' => $lesson === null
                ? null
                : [
                    'slug' => $lesson->slug,
                    'title' => $lesson->title,
                    'summary' => $lesson->summary,
                    'module' => [
                        'title' => $lesson->learningModule->title,
                    ],
                    'learning_path' => [
                        'slug' => $lesson->learningModule->learningPath->slug,
                        'title' => $lesson->learningModule->learningPath->title,
                    ],
                ],
        ]);
    }
}
