import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    CheckCircle2,
    Clock3,
    Layers3,
} from 'lucide-react';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import { ProgressStateLabel } from '@/components/dashboard/progress-state-label';
import type { ProgressState } from '@/components/dashboard/types';
import {
    ModuleSection,
    type LearningPathModule,
} from '@/components/learning/module-section';
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
import { learn } from '@/routes';
import { show as showLesson } from '@/routes/lessons';

type LessonProgress = {
    total: number;
    started: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

type LearningPathDetail = {
    slug: string;
    title: string;
    description: string;
    difficulty: 'easy' | 'medium' | 'hard';
    module_count: number;
    lesson_count: number;
    estimated_minutes: number;
    progress: LessonProgress;
    state: ProgressState;
    is_complete: boolean;
    next_lesson: null | {
        slug: string;
        title: string;
        summary: string;
        module_title: string | null;
        state: ProgressState;
    };
    modules: LearningPathModule[];
};

type LearningPathDetailProps = {
    learningPath: LearningPathDetail;
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

export default function LearningPathDetailPage({
    learningPath,
}: LearningPathDetailProps) {
    const nextLessonRoute = learningPath.next_lesson
        ? showLesson({
              learningPath: learningPath.slug,
              lesson: learningPath.next_lesson.slug,
          })
        : null;

    return (
        <>
            <Head title={learningPath.title} />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <div>
                    <Link
                        href={learn()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex items-center gap-2 rounded-sm text-sm font-medium outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <ArrowLeft aria-hidden="true" className="size-4" />
                        Back to Learning Paths
                    </Link>
                </div>

                <header>
                    <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                        <CardHeader className="gap-5 px-5 py-5 sm:px-6 lg:px-7 lg:py-6">
                            <div className="flex items-start gap-4">
                                <CategoryIcon category="learning" size="lg" />
                                <div className="min-w-0 flex-1 space-y-3">
                                    <div className="flex flex-col items-start justify-between gap-3 sm:flex-row sm:gap-4">
                                        <div className="space-y-2">
                                            <p className="text-primary font-mono text-xs font-medium tracking-[0.14em] uppercase">
                                                Learning path
                                            </p>
                                            <CardTitle className="text-2xl leading-8 sm:text-3xl">
                                                <h1>{learningPath.title}</h1>
                                            </CardTitle>
                                            <ProgressStateLabel
                                                state={learningPath.state}
                                            />
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className="capitalize"
                                        >
                                            {learningPath.difficulty}
                                        </Badge>
                                    </div>
                                    <CardDescription className="max-w-3xl text-sm leading-6 sm:text-base">
                                        {learningPath.description}
                                    </CardDescription>
                                </div>
                            </div>

                            <dl className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-2 text-xs sm:text-sm">
                                <div className="flex items-center gap-2">
                                    <Layers3
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    <dt className="sr-only">Modules</dt>
                                    <dd>
                                        {learningPath.module_count}{' '}
                                        {learningPath.module_count === 1
                                            ? 'module'
                                            : 'modules'}
                                    </dd>
                                </div>
                                <div className="flex items-center gap-2">
                                    <BookOpen
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    <dt className="sr-only">Lessons</dt>
                                    <dd>
                                        {learningPath.lesson_count}{' '}
                                        {learningPath.lesson_count === 1
                                            ? 'lesson'
                                            : 'lessons'}
                                    </dd>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Clock3
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    <dt className="sr-only">
                                        Estimated duration
                                    </dt>
                                    <dd>
                                        {formatDuration(
                                            learningPath.estimated_minutes,
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </CardHeader>

                        <CardContent className="space-y-3 border-t px-5 py-5 sm:px-6 lg:px-7">
                            <div className="flex flex-wrap items-baseline justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium">
                                        Path progress
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {learningPath.progress.completed} of{' '}
                                        {learningPath.progress.total} lessons
                                        complete
                                    </p>
                                </div>
                                <p className="font-mono text-lg font-semibold tabular-nums">
                                    {
                                        learningPath.progress
                                            .completion_percentage
                                    }
                                    %
                                </p>
                            </div>
                            <DashboardProgressBar
                                label={`${learningPath.title} completion`}
                                value={
                                    learningPath.progress.completion_percentage
                                }
                            />
                        </CardContent>
                    </Card>
                </header>

                <section aria-labelledby="path-action-heading">
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardContent className="flex flex-col gap-5 px-5 py-5 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-7">
                            {learningPath.is_complete ? (
                                <div className="flex items-start gap-3">
                                    <div className="border-primary/25 bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md border">
                                        <CheckCircle2
                                            aria-hidden="true"
                                            className="size-5"
                                        />
                                    </div>
                                    <div className="space-y-1.5">
                                        <h2
                                            id="path-action-heading"
                                            className="font-semibold"
                                        >
                                            Path complete
                                        </h2>
                                        <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                                            You have completed every available
                                            lesson in {learningPath.title}. The
                                            curriculum remains open for review.
                                        </p>
                                    </div>
                                </div>
                            ) : learningPath.next_lesson && nextLessonRoute ? (
                                <>
                                    <div className="flex min-w-0 items-start gap-3">
                                        <CategoryIcon
                                            category={
                                                learningPath.next_lesson
                                                    .module_title ?? 'learning'
                                            }
                                            size="md"
                                        />
                                        <div className="min-w-0 space-y-1.5">
                                            <p className="text-primary text-xs font-medium">
                                                {learningPath.next_lesson
                                                    .state === 'in_progress'
                                                    ? 'Continue learning'
                                                    : 'Next lesson'}
                                            </p>
                                            <h2
                                                id="path-action-heading"
                                                className="text-lg font-semibold"
                                            >
                                                {learningPath.next_lesson.title}
                                            </h2>
                                            {learningPath.next_lesson
                                                .module_title && (
                                                <p className="text-muted-foreground text-xs">
                                                    {
                                                        learningPath.next_lesson
                                                            .module_title
                                                    }
                                                </p>
                                            )}
                                            <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                                                {
                                                    learningPath.next_lesson
                                                        .summary
                                                }
                                            </p>
                                        </div>
                                    </div>
                                    <Button asChild className="shrink-0">
                                        <Link href={nextLessonRoute} prefetch>
                                            {learningPath.next_lesson.state ===
                                            'in_progress'
                                                ? 'Continue lesson'
                                                : 'Start lesson'}
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </>
                            ) : (
                                <div className="space-y-1.5">
                                    <h2
                                        id="path-action-heading"
                                        className="font-semibold"
                                    >
                                        No lessons available
                                    </h2>
                                    <p className="text-muted-foreground text-sm leading-6">
                                        Lessons will appear here when this path
                                        is expanded.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section
                    aria-labelledby="curriculum-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="curriculum-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Modules
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Follow each security topic in its intended lesson
                            order. Completed content remains available to
                            review.
                        </p>
                    </div>

                    {learningPath.modules.length === 0 ? (
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="flex min-h-40 items-center px-5 py-6 sm:px-6">
                                <div className="space-y-1">
                                    <h3 className="text-sm font-medium">
                                        No modules available
                                    </h3>
                                    <p className="text-muted-foreground text-sm leading-6">
                                        Curriculum modules will appear here when
                                        they are available.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4">
                            {learningPath.modules.map((learningModule) => (
                                <ModuleSection
                                    key={learningModule.position}
                                    learningPathSlug={learningPath.slug}
                                    learningModule={learningModule}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

LearningPathDetailPage.layout = {
    breadcrumbs: [
        {
            title: 'Learning Paths',
            href: learn(),
        },
    ],
};
