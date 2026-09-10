import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Clock3, FlaskConical } from 'lucide-react';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import { ProgressStateLabel } from '@/components/dashboard/progress-state-label';
import type { ProgressState } from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { show as showLesson } from '@/routes/lessons';

type LessonProgress = {
    total: number;
    started: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

export type LearningPathModule = {
    title: string;
    description: string;
    position: number;
    progress: LessonProgress;
    state: ProgressState;
    is_complete: boolean;
    lessons: Array<{
        slug: string;
        title: string;
        summary: string;
        estimated_minutes: number;
        position: number;
        state: ProgressState;
        is_next: boolean;
        has_lab: boolean;
        lab: null | {
            slug: string;
            title: string;
        };
    }>;
};

function lessonActionLabel(state: ProgressState, isNext: boolean): string {
    if (state === 'completed') {
        return 'Review lesson';
    }

    if (state === 'in_progress') {
        return 'Continue lesson';
    }

    return isNext ? 'Start lesson' : 'Open lesson';
}

export function ModuleSection({
    learningPathSlug,
    learningModule,
}: {
    learningPathSlug: string;
    learningModule: LearningPathModule;
}) {
    return (
        <article>
            <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                <CardHeader className="grid gap-5 px-5 py-5 sm:px-6 md:grid-cols-[minmax(0,1fr)_minmax(14rem,0.38fr)] md:items-end">
                    <div className="flex items-start gap-3.5">
                        <CategoryIcon
                            category={learningModule.title}
                            size="md"
                        />
                        <div className="min-w-0 space-y-2">
                            <p className="text-muted-foreground font-mono text-xs">
                                Module {learningModule.position}
                            </p>
                            <CardTitle className="text-lg leading-6">
                                <h3>{learningModule.title}</h3>
                            </CardTitle>
                            <CardDescription className="max-w-2xl leading-6">
                                {learningModule.description}
                            </CardDescription>
                        </div>
                    </div>

                    <div className="space-y-2.5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="text-muted-foreground text-xs">
                                {learningModule.progress.completed} of{' '}
                                {learningModule.progress.total}{' '}
                                {learningModule.progress.total === 1
                                    ? 'lesson'
                                    : 'lessons'}{' '}
                                complete
                            </span>
                            <ProgressStateLabel state={learningModule.state} />
                        </div>
                        <DashboardProgressBar
                            label={`${learningModule.title} completion`}
                            value={
                                learningModule.progress.completion_percentage
                            }
                        />
                    </div>
                </CardHeader>

                <CardContent className="border-t px-0">
                    {learningModule.lessons.length === 0 ? (
                        <div className="px-5 py-5 sm:px-6">
                            <p className="text-muted-foreground text-sm">
                                No lessons in this module yet.
                            </p>
                        </div>
                    ) : (
                        <ol className="divide-y">
                            {learningModule.lessons.map((lesson) => {
                                const lessonRoute = showLesson({
                                    learningPath: learningPathSlug,
                                    lesson: lesson.slug,
                                });

                                return (
                                    <li
                                        key={lesson.slug}
                                        className={cn(
                                            'grid gap-4 px-5 py-5 sm:px-6 md:grid-cols-[2.5rem_minmax(0,1fr)_auto] md:items-center',
                                            lesson.is_next &&
                                                'bg-primary/5 inset-ring-primary/25 inset-ring',
                                        )}
                                    >
                                        <div
                                            aria-hidden="true"
                                            className={cn(
                                                'bg-muted flex size-9 items-center justify-center rounded-md border font-mono text-xs font-semibold',
                                                lesson.state === 'completed' &&
                                                    'border-primary/25 bg-primary/10 text-primary',
                                            )}
                                        >
                                            {lesson.state === 'completed' ? (
                                                <CheckCircle2 className="size-4" />
                                            ) : (
                                                `${learningModule.position}.${lesson.position}`
                                            )}
                                        </div>

                                        <div className="min-w-0 space-y-2">
                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                <h4 className="text-sm leading-5 font-medium sm:text-base">
                                                    <Link
                                                        href={lessonRoute}
                                                        prefetch
                                                        className="hover:text-primary focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                                    >
                                                        {lesson.title}
                                                    </Link>
                                                </h4>
                                                {lesson.is_next && (
                                                    <span className="text-primary text-xs font-medium">
                                                        {lesson.state ===
                                                        'in_progress'
                                                            ? 'Current lesson'
                                                            : 'Next lesson'}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-muted-foreground max-w-3xl text-sm leading-6">
                                                {lesson.summary}
                                            </p>
                                            <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                <span className="inline-flex items-center gap-1.5">
                                                    <Clock3
                                                        aria-hidden="true"
                                                        className="size-3.5"
                                                    />
                                                    {lesson.estimated_minutes}{' '}
                                                    min
                                                </span>
                                                {lesson.has_lab &&
                                                    lesson.lab && (
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <FlaskConical
                                                                aria-hidden="true"
                                                                className="size-3.5"
                                                            />
                                                            Guided lab
                                                            available:{' '}
                                                            {lesson.lab.title}
                                                        </span>
                                                    )}
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-3 md:flex-col md:items-end">
                                            <ProgressStateLabel
                                                state={lesson.state}
                                            />
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="sm"
                                                className="px-0 text-xs md:px-2.5"
                                            >
                                                <Link
                                                    href={lessonRoute}
                                                    prefetch
                                                >
                                                    {lessonActionLabel(
                                                        lesson.state,
                                                        lesson.is_next,
                                                    )}
                                                    <ArrowRight aria-hidden="true" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    )}
                </CardContent>
            </Card>
        </article>
    );
}
