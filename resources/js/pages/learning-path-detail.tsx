import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    CheckCircle2,
    Circle,
    CircleDot,
    Clock3,
    FlaskConical,
    Layers3,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { learn } from '@/routes';
import { show as showLesson } from '@/routes/lessons';

type LessonState = 'not_started' | 'in_progress' | 'completed';

type LessonProgress = {
    total: number;
    started: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

type LearningPathLesson = {
    slug: string;
    title: string;
    summary: string;
    estimated_minutes: number;
    position: number;
    state: LessonState;
    is_next: boolean;
    has_lab: boolean;
    lab: null | {
        slug: string;
        title: string;
    };
};

type LearningPathModule = {
    title: string;
    description: string;
    position: number;
    progress: LessonProgress;
    is_complete: boolean;
    lessons: LearningPathLesson[];
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
    is_complete: boolean;
    next_lesson: null | {
        slug: string;
        title: string;
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

function ProgressBar({
    label,
    value,
    className,
}: {
    label: string;
    value: number;
    className?: string;
}) {
    return (
        <div
            role="progressbar"
            aria-label={label}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={value}
            className={cn('bg-muted h-2 overflow-hidden rounded-sm', className)}
        >
            <div
                className="bg-primary h-full rounded-sm"
                style={{ width: `${value}%` }}
            />
        </div>
    );
}

function LessonStateIndicator({ state }: { state: LessonState }) {
    if (state === 'completed') {
        return (
            <span className="text-primary inline-flex items-center gap-1.5 text-xs font-medium">
                <CheckCircle2 aria-hidden="true" className="size-4" />
                Completed
            </span>
        );
    }

    if (state === 'in_progress') {
        return (
            <span className="inline-flex items-center gap-1.5 text-xs font-medium">
                <CircleDot aria-hidden="true" className="text-primary size-4" />
                In progress
            </span>
        );
    }

    return (
        <span className="text-muted-foreground inline-flex items-center gap-1.5 text-xs font-medium">
            <Circle aria-hidden="true" className="size-4" />
            Not started
        </span>
    );
}

export default function LearningPathDetailPage({
    learningPath,
}: LearningPathDetailProps) {
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

                <header className="max-w-3xl space-y-4">
                    <Badge variant="outline" className="capitalize">
                        {learningPath.difficulty}
                    </Badge>
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {learningPath.title}
                        </h1>
                        <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                            {learningPath.description}
                        </p>
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
                </header>

                <section aria-labelledby="overall-progress-heading">
                    <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                        <CardHeader className="gap-4 border-b px-5 py-5 sm:px-6">
                            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                                <div className="space-y-1">
                                    <CardTitle>
                                        <h2 id="overall-progress-heading">
                                            Overall progress
                                        </h2>
                                    </CardTitle>
                                    <CardDescription>
                                        Lesson completion across this path.
                                    </CardDescription>
                                </div>
                                <p className="font-mono text-2xl font-semibold tabular-nums">
                                    {
                                        learningPath.progress
                                            .completion_percentage
                                    }
                                    %
                                </p>
                            </div>
                            <ProgressBar
                                label={`${learningPath.title} completion`}
                                value={
                                    learningPath.progress.completion_percentage
                                }
                            />
                        </CardHeader>

                        <CardContent className="px-0">
                            <dl className="bg-border grid grid-cols-2 gap-px sm:grid-cols-5">
                                {[
                                    ['Total', learningPath.progress.total],
                                    ['Started', learningPath.progress.started],
                                    [
                                        'Completed',
                                        learningPath.progress.completed,
                                    ],
                                    [
                                        'In progress',
                                        learningPath.progress.in_progress,
                                    ],
                                    [
                                        'Remaining',
                                        learningPath.progress.remaining,
                                    ],
                                ].map(([label, value]) => (
                                    <div
                                        key={label}
                                        className="bg-card flex min-h-20 flex-col justify-center gap-1 px-5 py-4 sm:px-6"
                                    >
                                        <dt className="text-muted-foreground text-xs">
                                            {label}
                                        </dt>
                                        <dd className="font-mono text-lg font-semibold tabular-nums">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>

                            <div className="bg-muted/35 flex items-center gap-3 border-t px-5 py-4 sm:px-6">
                                {learningPath.is_complete ? (
                                    <>
                                        <CheckCircle2
                                            aria-hidden="true"
                                            className="text-primary size-5 shrink-0"
                                        />
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium">
                                                Path complete
                                            </p>
                                            <p className="text-muted-foreground text-xs leading-5">
                                                Every lesson is complete.
                                            </p>
                                        </div>
                                    </>
                                ) : learningPath.next_lesson ? (
                                    <>
                                        <BookOpen
                                            aria-hidden="true"
                                            className="text-primary size-5 shrink-0"
                                        />
                                        <div className="min-w-0 space-y-1">
                                            <p className="text-primary text-xs font-medium">
                                                Next lesson
                                            </p>
                                            <p className="text-sm leading-5 font-medium">
                                                {learningPath.next_lesson.title}
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <BookOpen
                                            aria-hidden="true"
                                            className="text-muted-foreground size-5 shrink-0"
                                        />
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium">
                                                No lessons available
                                            </p>
                                            <p className="text-muted-foreground text-xs leading-5">
                                                This path has no lessons yet.
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>
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
                            Curriculum
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Modules and lessons follow their explicit learning
                            order.
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
                                <article key={learningModule.position}>
                                    <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                                        <CardHeader className="grid gap-5 px-5 py-5 sm:px-6 md:grid-cols-[minmax(0,1fr)_minmax(14rem,0.35fr)] md:items-end">
                                            <div className="space-y-2">
                                                <p className="text-muted-foreground font-mono text-xs">
                                                    Module{' '}
                                                    {learningModule.position}
                                                </p>
                                                <CardTitle className="text-lg leading-6">
                                                    <h3>
                                                        {learningModule.title}
                                                    </h3>
                                                </CardTitle>
                                                <CardDescription className="max-w-3xl leading-6">
                                                    {learningModule.description}
                                                </CardDescription>
                                            </div>

                                            <div className="space-y-2.5">
                                                <div className="flex items-center justify-between gap-4 text-xs">
                                                    <span className="text-muted-foreground inline-flex items-center gap-1.5">
                                                        {learningModule.is_complete && (
                                                            <CheckCircle2
                                                                aria-hidden="true"
                                                                className="text-primary size-3.5"
                                                            />
                                                        )}
                                                        {
                                                            learningModule
                                                                .progress
                                                                .completed
                                                        }{' '}
                                                        of{' '}
                                                        {
                                                            learningModule
                                                                .progress.total
                                                        }{' '}
                                                        complete
                                                    </span>
                                                    <span className="font-mono font-semibold tabular-nums">
                                                        {
                                                            learningModule
                                                                .progress
                                                                .completion_percentage
                                                        }
                                                        %
                                                    </span>
                                                </div>
                                                <ProgressBar
                                                    label={`${learningModule.title} completion`}
                                                    value={
                                                        learningModule.progress
                                                            .completion_percentage
                                                    }
                                                    className="h-1.5"
                                                />
                                            </div>
                                        </CardHeader>

                                        <CardContent className="border-t px-0">
                                            {learningModule.lessons.length ===
                                            0 ? (
                                                <div className="px-5 py-5 sm:px-6">
                                                    <p className="text-muted-foreground text-sm">
                                                        No lessons in this
                                                        module yet.
                                                    </p>
                                                </div>
                                            ) : (
                                                <ol className="divide-y">
                                                    {learningModule.lessons.map(
                                                        (lesson) => (
                                                            <li
                                                                key={
                                                                    lesson.slug
                                                                }
                                                                className={cn(
                                                                    'grid gap-4 px-5 py-5 sm:px-6 md:grid-cols-[2.5rem_minmax(0,1fr)_auto] md:items-center',
                                                                    lesson.is_next &&
                                                                        'bg-primary/5 inset-ring-primary/30 inset-ring',
                                                                )}
                                                            >
                                                                <div
                                                                    aria-hidden="true"
                                                                    className="bg-muted flex size-9 items-center justify-center rounded-md border font-mono text-xs font-semibold"
                                                                >
                                                                    {
                                                                        learningModule.position
                                                                    }
                                                                    .
                                                                    {
                                                                        lesson.position
                                                                    }
                                                                </div>

                                                                <div className="min-w-0 space-y-2">
                                                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                                        <h4 className="text-sm leading-5 font-medium sm:text-base">
                                                                            <Link
                                                                                href={showLesson(
                                                                                    {
                                                                                        learningPath:
                                                                                            learningPath.slug,
                                                                                        lesson: lesson.slug,
                                                                                    },
                                                                                )}
                                                                                prefetch
                                                                                className="hover:text-primary focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                                                            >
                                                                                {
                                                                                    lesson.title
                                                                                }
                                                                            </Link>
                                                                        </h4>
                                                                        {lesson.is_next && (
                                                                            <span className="text-primary text-xs font-medium">
                                                                                Next
                                                                                lesson
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                    <p className="text-muted-foreground max-w-3xl text-sm leading-6">
                                                                        {
                                                                            lesson.summary
                                                                        }
                                                                    </p>
                                                                    <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                                        <span className="inline-flex items-center gap-1.5">
                                                                            <Clock3
                                                                                aria-hidden="true"
                                                                                className="size-3.5"
                                                                            />
                                                                            {
                                                                                lesson.estimated_minutes
                                                                            }{' '}
                                                                            min
                                                                        </span>
                                                                        {lesson.has_lab &&
                                                                            lesson.lab && (
                                                                                <span className="inline-flex items-center gap-1.5">
                                                                                    <FlaskConical
                                                                                        aria-hidden="true"
                                                                                        className="size-3.5"
                                                                                    />
                                                                                    Guided
                                                                                    lab:{' '}
                                                                                    {
                                                                                        lesson
                                                                                            .lab
                                                                                            .title
                                                                                    }
                                                                                </span>
                                                                            )}
                                                                    </div>
                                                                </div>

                                                                <div className="md:justify-self-end">
                                                                    <LessonStateIndicator
                                                                        state={
                                                                            lesson.state
                                                                        }
                                                                    />
                                                                </div>
                                                            </li>
                                                        ),
                                                    )}
                                                </ol>
                                            )}
                                        </CardContent>
                                    </Card>
                                </article>
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
