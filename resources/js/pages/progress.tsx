import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    BookOpenCheck,
    CheckCircle2,
    Circle,
    CircleDot,
    FlaskConical,
    Layers3,
    Map,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { progress as progressRoute } from '@/routes';
import { workspace as workspaceLab } from '@/routes/labs';
import { show as showLearningPath } from '@/routes/learning-paths';
import { show as showLesson } from '@/routes/lessons';

type ProgressState = 'not_started' | 'in_progress' | 'completed';

type ProgressSummary = {
    total: number;
    started: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

type LearningPathProgress = {
    slug: string;
    title: string;
    difficulty: 'easy' | 'medium' | 'hard';
    module_count: number;
    lesson_count: number;
    completed_lessons: number;
    in_progress_lessons: number;
    completion_percentage: number;
    state: ProgressState;
    next_lesson: null | {
        slug: string;
        title: string;
    };
};

type InProgressLesson = {
    slug: string;
    title: string;
    module_title: string;
    learning_path: {
        slug: string;
        title: string;
    };
};

type InProgressLab = {
    slug: string;
    title: string;
    vulnerability_type: string;
};

type ProgressPageProps = {
    lessonProgress: ProgressSummary;
    labProgress: ProgressSummary;
    learningPaths: LearningPathProgress[];
    inProgressLessons: InProgressLesson[];
    inProgressLabs: InProgressLab[];
};

function formatVulnerabilityType(vulnerabilityType: string): string {
    return vulnerabilityType.replaceAll('_', ' ');
}

function ProgressBar({ label, value }: { label: string; value: number }) {
    return (
        <div
            role="progressbar"
            aria-label={label}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={value}
            className="bg-muted h-2 overflow-hidden rounded-sm"
        >
            <div
                className="bg-primary h-full rounded-sm"
                style={{ width: `${value}%` }}
            />
        </div>
    );
}

function StateLabel({ state }: { state: ProgressState }) {
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

function SummaryCard({
    title,
    description,
    unit,
    summary,
    icon: Icon,
}: {
    title: string;
    description: string;
    unit: 'lessons' | 'labs';
    summary: ProgressSummary;
    icon: typeof BookOpenCheck;
}) {
    return (
        <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
            <CardHeader className="gap-3 border-b px-5 py-5 sm:px-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <div className="border-primary/25 bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-md border">
                            <Icon aria-hidden="true" className="size-4.5" />
                        </div>
                        <div className="space-y-1">
                            <CardTitle className="text-base">{title}</CardTitle>
                            <CardDescription className="leading-5">
                                {description}
                            </CardDescription>
                        </div>
                    </div>
                    <p className="font-mono text-lg font-semibold tabular-nums">
                        {summary.completion_percentage}%
                    </p>
                </div>
            </CardHeader>
            <CardContent className="space-y-4 px-5 py-5 sm:px-6">
                <div className="space-y-2.5">
                    <ProgressBar
                        label={`${title} completion`}
                        value={summary.completion_percentage}
                    />
                    <p className="text-sm font-medium">
                        {summary.total === 0
                            ? `No ${unit} available`
                            : `${summary.completed} of ${summary.total} ${unit} complete`}
                    </p>
                </div>
                <dl className="grid grid-cols-3 gap-3 border-t pt-4">
                    <div className="space-y-1">
                        <dt className="text-muted-foreground text-xs">
                            Started
                        </dt>
                        <dd className="font-mono text-sm font-semibold tabular-nums">
                            {summary.started}
                        </dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="text-muted-foreground text-xs">
                            In progress
                        </dt>
                        <dd className="font-mono text-sm font-semibold tabular-nums">
                            {summary.in_progress}
                        </dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="text-muted-foreground text-xs">
                            Remaining
                        </dt>
                        <dd className="font-mono text-sm font-semibold tabular-nums">
                            {summary.remaining}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    );
}

export default function ProgressPage({
    lessonProgress,
    labProgress,
    learningPaths,
    inProgressLessons,
    inProgressLabs,
}: ProgressPageProps) {
    const hasInProgressItems =
        inProgressLessons.length > 0 || inProgressLabs.length > 0;

    return (
        <>
            <Head title="Progress" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Progress
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Track completed learning, active work, and the next step
                        through your SecureLab curriculum.
                    </p>
                </header>

                <section
                    aria-labelledby="progress-summary-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="progress-summary-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Your progress
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Lesson and lab completion remain separate measures.
                        </p>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <SummaryCard
                            title="Learning"
                            description="Structured curriculum lessons"
                            unit="lessons"
                            summary={lessonProgress}
                            icon={BookOpenCheck}
                        />
                        <SummaryCard
                            title="Labs"
                            description="Safe guided exercises"
                            unit="labs"
                            summary={labProgress}
                            icon={FlaskConical}
                        />
                    </div>
                </section>

                <section
                    aria-labelledby="learning-path-progress-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="learning-path-progress-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Learning paths
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Completion follows lesson progress within each path.
                        </p>
                    </div>

                    {learningPaths.length === 0 ? (
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="flex min-h-44 flex-col items-start justify-center gap-3 px-5 py-6 sm:px-6">
                                <div className="border-primary/25 bg-primary/10 text-primary flex size-9 items-center justify-center rounded-md border">
                                    <Map
                                        aria-hidden="true"
                                        className="size-4.5"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="text-sm font-medium">
                                        No learning paths available
                                    </h3>
                                    <p className="text-muted-foreground text-sm leading-6">
                                        Curriculum progress will appear when a
                                        learning path is available.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-3">
                            {learningPaths.map((learningPath) => (
                                <article key={learningPath.slug}>
                                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                                        <CardContent className="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(15rem,0.7fr)_auto] lg:items-center">
                                            <div className="min-w-0 space-y-3">
                                                <div className="flex flex-wrap items-center gap-2.5">
                                                    <h3 className="text-base font-semibold sm:text-lg">
                                                        {learningPath.title}
                                                    </h3>
                                                    <Badge
                                                        variant="outline"
                                                        className="capitalize"
                                                    >
                                                        {
                                                            learningPath.difficulty
                                                        }
                                                    </Badge>
                                                </div>
                                                <div className="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <Layers3
                                                            aria-hidden="true"
                                                            className="size-3.5"
                                                        />
                                                        {
                                                            learningPath.module_count
                                                        }{' '}
                                                        {learningPath.module_count ===
                                                        1
                                                            ? 'module'
                                                            : 'modules'}
                                                    </span>
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <BookOpen
                                                            aria-hidden="true"
                                                            className="size-3.5"
                                                        />
                                                        {
                                                            learningPath.lesson_count
                                                        }{' '}
                                                        {learningPath.lesson_count ===
                                                        1
                                                            ? 'lesson'
                                                            : 'lessons'}
                                                    </span>
                                                    <StateLabel
                                                        state={
                                                            learningPath.state
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <div className="space-y-2.5">
                                                <div className="flex items-baseline justify-between gap-4">
                                                    <p className="text-sm font-medium">
                                                        {
                                                            learningPath.completed_lessons
                                                        }{' '}
                                                        of{' '}
                                                        {
                                                            learningPath.lesson_count
                                                        }{' '}
                                                        complete
                                                    </p>
                                                    <p className="font-mono text-sm font-semibold tabular-nums">
                                                        {
                                                            learningPath.completion_percentage
                                                        }
                                                        %
                                                    </p>
                                                </div>
                                                <ProgressBar
                                                    label={`${learningPath.title} completion`}
                                                    value={
                                                        learningPath.completion_percentage
                                                    }
                                                />
                                                {learningPath.next_lesson ? (
                                                    <p className="text-muted-foreground text-xs leading-5">
                                                        Next:{' '}
                                                        <span className="text-foreground font-medium">
                                                            {
                                                                learningPath
                                                                    .next_lesson
                                                                    .title
                                                            }
                                                        </span>
                                                    </p>
                                                ) : learningPath.state ===
                                                  'completed' ? (
                                                    <p className="text-primary inline-flex items-center gap-1.5 text-xs font-medium">
                                                        <CheckCircle2
                                                            aria-hidden="true"
                                                            className="size-3.5"
                                                        />
                                                        Path complete
                                                    </p>
                                                ) : (
                                                    <p className="text-muted-foreground text-xs">
                                                        No lessons available in
                                                        this path.
                                                    </p>
                                                )}
                                            </div>

                                            <div className="flex flex-wrap gap-2 lg:flex-col lg:items-stretch">
                                                {learningPath.next_lesson && (
                                                    <Button asChild size="sm">
                                                        <Link
                                                            href={showLesson({
                                                                learningPath:
                                                                    learningPath.slug,
                                                                lesson: learningPath
                                                                    .next_lesson
                                                                    .slug,
                                                            })}
                                                            prefetch
                                                        >
                                                            Continue lesson
                                                            <ArrowRight aria-hidden="true" />
                                                        </Link>
                                                    </Button>
                                                )}
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={showLearningPath(
                                                            learningPath.slug,
                                                        )}
                                                        prefetch
                                                    >
                                                        View path
                                                    </Link>
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <section
                    aria-labelledby="continue-learning-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="continue-learning-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Continue learning
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Resume lessons and labs already in progress.
                        </p>
                    </div>

                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        {!hasInProgressItems ? (
                            <CardContent className="flex min-h-36 items-start gap-3 px-5 py-6 sm:px-6">
                                <CircleDot
                                    aria-hidden="true"
                                    className="text-muted-foreground mt-0.5 size-5 shrink-0"
                                />
                                <div className="space-y-1">
                                    <h3 className="text-sm font-medium">
                                        Nothing in progress
                                    </h3>
                                    <p className="text-muted-foreground text-sm leading-6">
                                        Use the next lesson shown above when you
                                        are ready to continue.
                                    </p>
                                </div>
                            </CardContent>
                        ) : (
                            <CardContent className="bg-border grid gap-px px-0 md:grid-cols-2">
                                <div className="bg-card px-5 py-5 sm:px-6">
                                    <h3 className="text-sm font-semibold">
                                        Lessons in progress
                                    </h3>
                                    {inProgressLessons.length === 0 ? (
                                        <p className="text-muted-foreground mt-3 text-sm leading-6">
                                            No active lessons.
                                        </p>
                                    ) : (
                                        <ul className="mt-3 divide-y">
                                            {inProgressLessons.map((lesson) => (
                                                <li
                                                    key={`${lesson.learning_path.slug}:${lesson.slug}`}
                                                    className="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                                >
                                                    <div className="min-w-0 space-y-1">
                                                        <p className="text-sm font-medium">
                                                            {lesson.title}
                                                        </p>
                                                        <p className="text-muted-foreground text-xs leading-5">
                                                            {
                                                                lesson.module_title
                                                            }{' '}
                                                            &middot;{' '}
                                                            {
                                                                lesson
                                                                    .learning_path
                                                                    .title
                                                            }
                                                        </p>
                                                    </div>
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={showLesson({
                                                                learningPath:
                                                                    lesson
                                                                        .learning_path
                                                                        .slug,
                                                                lesson: lesson.slug,
                                                            })}
                                                            prefetch
                                                        >
                                                            Continue lesson
                                                        </Link>
                                                    </Button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="bg-card px-5 py-5 sm:px-6">
                                    <h3 className="text-sm font-semibold">
                                        Labs in progress
                                    </h3>
                                    {inProgressLabs.length === 0 ? (
                                        <p className="text-muted-foreground mt-3 text-sm leading-6">
                                            No active labs.
                                        </p>
                                    ) : (
                                        <ul className="mt-3 divide-y">
                                            {inProgressLabs.map((lab) => (
                                                <li
                                                    key={lab.slug}
                                                    className="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                                >
                                                    <div className="min-w-0 space-y-1">
                                                        <p className="text-sm font-medium">
                                                            {lab.title}
                                                        </p>
                                                        <p className="text-primary font-mono text-xs capitalize">
                                                            {formatVulnerabilityType(
                                                                lab.vulnerability_type,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={workspaceLab(
                                                                lab.slug,
                                                            )}
                                                            prefetch
                                                        >
                                                            Open workspace
                                                        </Link>
                                                    </Button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </CardContent>
                        )}
                    </Card>
                </section>
            </main>
        </>
    );
}

ProgressPage.layout = {
    breadcrumbs: [
        {
            title: 'Progress',
            href: progressRoute(),
        },
    ],
};
