import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    BookOpenCheck,
    CheckCircle2,
    FlaskConical,
    Map,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    dashboard,
    labs as labsRoute,
    progress as progressRoute,
} from '@/routes';
import { workspace as workspaceLab } from '@/routes/labs';
import { show as showLearningPath } from '@/routes/learning-paths';
import { show as showLesson } from '@/routes/lessons';

type ProgressState = 'not_started' | 'in_progress' | 'completed';

type ProgressSummary = {
    total: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

type DashboardLearningPath = {
    slug: string;
    title: string;
    module_count: number;
    lesson_count: number;
    completed_lessons: number;
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

type DashboardProps = {
    learningSummary: ProgressSummary;
    labSummary: ProgressSummary;
    learningPaths: DashboardLearningPath[];
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

function SummaryBlock({
    title,
    unit,
    summary,
    icon: Icon,
    href,
    linkLabel,
}: {
    title: string;
    unit: 'lessons' | 'labs';
    summary: ProgressSummary;
    icon: typeof BookOpenCheck;
    href: ReturnType<typeof progressRoute>;
    linkLabel: string;
}) {
    const isComplete = summary.total > 0 && summary.completed === summary.total;

    return (
        <div className="space-y-4 px-5 py-5 sm:px-6">
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-center gap-3">
                    <div className="border-primary/25 bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-md border">
                        <Icon aria-hidden="true" className="size-4.5" />
                    </div>
                    <div>
                        <h3 className="text-sm font-semibold">{title}</h3>
                        <p className="text-muted-foreground text-xs">
                            {summary.total === 0
                                ? `No ${unit} available`
                                : `${summary.completed} of ${summary.total} ${unit} complete`}
                        </p>
                    </div>
                </div>
                <p className="font-mono text-lg font-semibold tabular-nums">
                    {summary.completion_percentage}%
                </p>
            </div>

            <div className="space-y-2">
                <ProgressBar
                    label={`${title} completion`}
                    value={summary.completion_percentage}
                />
                <p className="text-muted-foreground text-xs">
                    {isComplete
                        ? 'Completed'
                        : `${summary.in_progress} in progress · ${summary.remaining} remaining`}
                </p>
            </div>

            <Link
                href={href}
                prefetch
                className="text-primary focus-visible:ring-ring inline-flex items-center gap-1.5 text-sm font-medium underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:outline-none"
            >
                {linkLabel}
                <ArrowRight aria-hidden="true" className="size-3.5" />
            </Link>
        </div>
    );
}

export default function Dashboard({
    learningSummary,
    labSummary,
    learningPaths,
    inProgressLessons,
    inProgressLabs,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.trim().split(/\s+/)[0] || auth.user.name;
    const hasInProgressItems =
        inProgressLessons.length > 0 || inProgressLabs.length > 0;
    const hasAvailableLearning = learningPaths.some(
        (learningPath) => learningPath.next_lesson !== null,
    );

    return (
        <>
            <Head title="Dashboard" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Welcome back, {firstName}.
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Pick up active work or check your progress through the
                        SecureLab curriculum and guided labs.
                    </p>
                </header>

                {hasInProgressItems ? (
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
                                Resume work already in progress.
                            </p>
                        </div>

                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="px-5 py-1 sm:px-6">
                                <ul className="divide-y">
                                    {inProgressLessons.map((lesson) => (
                                        <li
                                            key={`${lesson.learning_path.slug}:${lesson.slug}`}
                                            className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="flex min-w-0 items-start gap-3">
                                                <BookOpen
                                                    aria-hidden="true"
                                                    className="text-primary mt-0.5 size-4 shrink-0"
                                                />
                                                <div className="min-w-0 space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {lesson.title}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs leading-5">
                                                        Lesson ·{' '}
                                                        {lesson.module_title} ·
                                                        In progress
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={showLesson({
                                                        learningPath:
                                                            lesson.learning_path
                                                                .slug,
                                                        lesson: lesson.slug,
                                                    })}
                                                    prefetch
                                                >
                                                    Continue lesson
                                                    <ArrowRight aria-hidden="true" />
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                    {inProgressLabs.map((lab) => (
                                        <li
                                            key={lab.slug}
                                            className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="flex min-w-0 items-start gap-3">
                                                <FlaskConical
                                                    aria-hidden="true"
                                                    className="text-primary mt-0.5 size-4 shrink-0"
                                                />
                                                <div className="min-w-0 space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {lab.title}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs leading-5 capitalize">
                                                        Lab ·{' '}
                                                        {formatVulnerabilityType(
                                                            lab.vulnerability_type,
                                                        )}{' '}
                                                        · In progress
                                                    </p>
                                                </div>
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
                                                    <ArrowRight aria-hidden="true" />
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    </section>
                ) : hasAvailableLearning ? (
                    <section
                        aria-labelledby="start-learning-heading"
                        className="space-y-4"
                    >
                        <div className="space-y-1">
                            <h2
                                id="start-learning-heading"
                                className="text-xl font-semibold tracking-tight"
                            >
                                Start learning
                            </h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                Choose a real curriculum path and begin its next
                                lesson.
                            </p>
                        </div>
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="px-5 py-1 sm:px-6">
                                <ul className="divide-y">
                                    {learningPaths.map((learningPath) => {
                                        const nextLesson =
                                            learningPath.next_lesson;

                                        if (nextLesson === null) {
                                            return null;
                                        }

                                        return (
                                            <li
                                                key={learningPath.slug}
                                                className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div className="min-w-0 space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {learningPath.title}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs leading-5">
                                                        {
                                                            learningPath.module_count
                                                        }{' '}
                                                        {learningPath.module_count ===
                                                        1
                                                            ? 'module'
                                                            : 'modules'}{' '}
                                                        ·{' '}
                                                        {
                                                            learningPath.lesson_count
                                                        }{' '}
                                                        {learningPath.lesson_count ===
                                                        1
                                                            ? 'lesson'
                                                            : 'lessons'}
                                                    </p>
                                                    <p className="text-xs">
                                                        Next:{' '}
                                                        <span className="font-medium">
                                                            {nextLesson.title}
                                                        </span>
                                                    </p>
                                                </div>
                                                <Button asChild size="sm">
                                                    <Link
                                                        href={showLesson({
                                                            learningPath:
                                                                learningPath.slug,
                                                            lesson: nextLesson.slug,
                                                        })}
                                                        prefetch
                                                    >
                                                        Start lesson
                                                        <ArrowRight aria-hidden="true" />
                                                    </Link>
                                                </Button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </CardContent>
                        </Card>
                    </section>
                ) : null}

                <section
                    aria-labelledby="dashboard-progress-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="dashboard-progress-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Your progress
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Lesson and lab completion remain separate measures.
                        </p>
                    </div>
                    <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                        <CardContent className="bg-border grid p-0 md:grid-cols-2 md:gap-px">
                            <div className="bg-card">
                                <SummaryBlock
                                    title="Learning"
                                    unit="lessons"
                                    summary={learningSummary}
                                    icon={BookOpenCheck}
                                    href={progressRoute()}
                                    linkLabel="View detailed progress"
                                />
                            </div>
                            <div className="bg-card border-t md:border-t-0">
                                <SummaryBlock
                                    title="Labs"
                                    unit="labs"
                                    summary={labSummary}
                                    icon={FlaskConical}
                                    href={labsRoute()}
                                    linkLabel="Browse labs"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section
                    aria-labelledby="dashboard-paths-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="dashboard-paths-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Learning paths
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            See your position in the current curriculum.
                        </p>
                    </div>

                    {learningPaths.length === 0 ? (
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="flex min-h-36 items-start gap-3 px-5 py-6 sm:px-6">
                                <Map
                                    aria-hidden="true"
                                    className="text-muted-foreground mt-0.5 size-5 shrink-0"
                                />
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
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="px-5 py-1 sm:px-6">
                                <ul className="divide-y">
                                    {learningPaths.map((learningPath) => (
                                        <li
                                            key={learningPath.slug}
                                            className="grid gap-4 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,0.7fr)_auto] lg:items-center"
                                        >
                                            <div className="min-w-0 space-y-1">
                                                <h3 className="text-sm font-semibold sm:text-base">
                                                    {learningPath.title}
                                                </h3>
                                                <p className="text-muted-foreground text-xs">
                                                    {
                                                        learningPath.completed_lessons
                                                    }{' '}
                                                    of{' '}
                                                    {learningPath.lesson_count}{' '}
                                                    lessons complete
                                                </p>
                                            </div>
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between gap-3 text-xs">
                                                    {learningPath.state ===
                                                    'completed' ? (
                                                        <span className="text-primary inline-flex items-center gap-1.5 font-medium">
                                                            <CheckCircle2
                                                                aria-hidden="true"
                                                                className="size-3.5"
                                                            />
                                                            Completed
                                                        </span>
                                                    ) : learningPath.next_lesson ? (
                                                        <span className="truncate">
                                                            Next:{' '}
                                                            <span className="font-medium">
                                                                {
                                                                    learningPath
                                                                        .next_lesson
                                                                        .title
                                                                }
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No lessons available
                                                        </span>
                                                    )}
                                                    <span className="font-mono font-semibold tabular-nums">
                                                        {
                                                            learningPath.completion_percentage
                                                        }
                                                        %
                                                    </span>
                                                </div>
                                                <ProgressBar
                                                    label={`${learningPath.title} completion`}
                                                    value={
                                                        learningPath.completion_percentage
                                                    }
                                                />
                                            </div>
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
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </section>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
