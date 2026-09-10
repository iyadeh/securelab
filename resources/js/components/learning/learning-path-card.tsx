import { Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Clock3, Layers3 } from 'lucide-react';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import { ProgressStateLabel } from '@/components/dashboard/progress-state-label';
import type { ProgressState } from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show as showLearningPath } from '@/routes/learning-paths';

export type LearningPathOverview = {
    slug: string;
    title: string;
    description: string;
    difficulty: 'easy' | 'medium' | 'hard';
    module_count: number;
    lesson_count: number;
    estimated_minutes: number;
    completed_lessons: number;
    completion_percentage: number;
    state: ProgressState;
    is_complete: boolean;
    next_lesson: null | {
        slug: string;
        title: string;
        module_title: string | null;
        state: ProgressState;
    };
    modules: Array<{
        title: string;
        lesson_count: number;
        completed_lessons: number;
        completion_percentage: number;
        state: ProgressState;
    }>;
};

function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${minutes} min`;
    }

    if (remainingMinutes === 0) {
        return `${hours} hr`;
    }

    return `${hours} hr ${remainingMinutes} min`;
}

function pathActionLabel(state: ProgressState, hasLessons: boolean): string {
    if (!hasLessons) {
        return 'View path';
    }

    if (state === 'completed') {
        return 'Review path';
    }

    if (state === 'in_progress') {
        return 'Continue path';
    }

    return 'Start learning';
}

export function LearningPathCard({
    learningPath,
}: {
    learningPath: LearningPathOverview;
}) {
    return (
        <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
            <CardHeader className="gap-5 border-b px-5 py-5 sm:px-6 lg:px-7 lg:py-6">
                <div className="flex items-start gap-4">
                    <CategoryIcon category="learning" size="lg" />
                    <div className="min-w-0 flex-1 space-y-3">
                        <div className="flex flex-col items-start justify-between gap-3 sm:flex-row sm:gap-4">
                            <div className="space-y-1.5">
                                <CardTitle className="text-xl leading-7 sm:text-2xl">
                                    <h3>{learningPath.title}</h3>
                                </CardTitle>
                                <ProgressStateLabel
                                    state={learningPath.state}
                                />
                            </div>
                            <Badge variant="outline" className="capitalize">
                                {learningPath.difficulty}
                            </Badge>
                        </div>
                        <CardDescription className="max-w-3xl text-sm leading-6">
                            {learningPath.description}
                        </CardDescription>
                    </div>
                </div>

                <dl className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-2 text-xs sm:text-sm">
                    <div className="flex items-center gap-2">
                        <Layers3 aria-hidden="true" className="size-4" />
                        <dt className="sr-only">Modules</dt>
                        <dd>
                            {learningPath.module_count}{' '}
                            {learningPath.module_count === 1
                                ? 'module'
                                : 'modules'}
                        </dd>
                    </div>
                    <div className="flex items-center gap-2">
                        <BookOpen aria-hidden="true" className="size-4" />
                        <dt className="sr-only">Lessons</dt>
                        <dd>
                            {learningPath.lesson_count}{' '}
                            {learningPath.lesson_count === 1
                                ? 'lesson'
                                : 'lessons'}
                        </dd>
                    </div>
                    <div className="flex items-center gap-2">
                        <Clock3 aria-hidden="true" className="size-4" />
                        <dt className="sr-only">Estimated duration</dt>
                        <dd>
                            {formatDuration(learningPath.estimated_minutes)}
                        </dd>
                    </div>
                </dl>
            </CardHeader>

            <CardContent className="grid gap-6 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(16rem,0.65fr)] lg:px-7 lg:py-6">
                <div className="space-y-5">
                    <div className="space-y-3">
                        <div className="flex items-baseline justify-between gap-4">
                            <p className="text-sm font-medium">Path progress</p>
                            <p className="font-mono text-sm font-semibold tabular-nums">
                                {learningPath.completion_percentage}%
                            </p>
                        </div>
                        <DashboardProgressBar
                            label={`${learningPath.title} completion`}
                            value={learningPath.completion_percentage}
                        />
                        <p className="text-muted-foreground text-xs sm:text-sm">
                            {learningPath.completed_lessons} of{' '}
                            {learningPath.lesson_count} lessons complete
                        </p>
                    </div>

                    {learningPath.modules.length > 0 && (
                        <div className="grid gap-2 sm:grid-cols-3">
                            {learningPath.modules.map((learningModule) => (
                                <div
                                    key={learningModule.title}
                                    className="bg-muted/30 flex min-w-0 items-center gap-3 rounded-md border p-3"
                                >
                                    <CategoryIcon
                                        category={learningModule.title}
                                        size="sm"
                                    />
                                    <div className="min-w-0 space-y-0.5">
                                        <p className="text-xs leading-5 font-medium sm:text-sm">
                                            {learningModule.title}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {learningModule.completed_lessons} /{' '}
                                            {learningModule.lesson_count}{' '}
                                            lessons
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="bg-muted/30 flex flex-col justify-between gap-4 rounded-md border p-4">
                    {learningPath.is_complete ? (
                        <div className="space-y-1.5">
                            <p className="text-sm font-medium">Path complete</p>
                            <p className="text-muted-foreground text-xs leading-5">
                                All available lessons are complete and remain
                                open for review.
                            </p>
                        </div>
                    ) : learningPath.next_lesson ? (
                        <div className="space-y-2">
                            <p className="text-primary text-xs font-medium">
                                {learningPath.next_lesson.state ===
                                'in_progress'
                                    ? 'Continue learning'
                                    : 'Next lesson'}
                            </p>
                            <div>
                                <p className="text-sm font-medium">
                                    {learningPath.next_lesson.title}
                                </p>
                                {learningPath.next_lesson.module_title && (
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {learningPath.next_lesson.module_title}
                                    </p>
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-1.5">
                            <p className="text-sm font-medium">
                                No lessons available
                            </p>
                            <p className="text-muted-foreground text-xs leading-5">
                                Curriculum will appear here when lessons are
                                published.
                            </p>
                        </div>
                    )}

                    <Button asChild className="w-full justify-between">
                        <Link
                            href={showLearningPath(learningPath.slug)}
                            prefetch
                        >
                            {pathActionLabel(
                                learningPath.state,
                                learningPath.lesson_count > 0,
                            )}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
