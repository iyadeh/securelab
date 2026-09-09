import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import { DashboardSectionHeading } from '@/components/dashboard/section-heading';
import type {
    DashboardLearningPath,
    DashboardProgressSummary,
    InProgressLab,
    InProgressLesson,
} from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { labs as labsRoute, progress as progressRoute } from '@/routes';
import { workspace as workspaceLab } from '@/routes/labs';
import { show as showLesson } from '@/routes/lessons';

function formatLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function PrimaryLearningAction({
    learningSummary,
    labSummary,
    learningPaths,
    inProgressLessons,
    inProgressLabs,
}: {
    learningSummary: DashboardProgressSummary;
    labSummary: DashboardProgressSummary;
    learningPaths: DashboardLearningPath[];
    inProgressLessons: InProgressLesson[];
    inProgressLabs: InProgressLab[];
}) {
    const primaryLesson = inProgressLessons[0] ?? null;
    const primaryLab =
        primaryLesson === null ? (inProgressLabs[0] ?? null) : null;
    const secondaryLessons = inProgressLessons.slice(
        primaryLesson === null ? 0 : 1,
    );
    const secondaryLabs = inProgressLabs.slice(primaryLab === null ? 0 : 1);
    const nextLearningPath = learningPaths.find(
        (learningPath) => learningPath.next_lesson !== null,
    );
    const availableItemCount = learningSummary.total + labSummary.total;
    const journeyComplete =
        availableItemCount > 0 &&
        learningSummary.completed === learningSummary.total &&
        labSummary.completed === labSummary.total;
    const hasSecondaryWork =
        secondaryLessons.length > 0 || secondaryLabs.length > 0;

    return (
        <section
            aria-labelledby="dashboard-action-heading"
            className="space-y-4"
        >
            <DashboardSectionHeading
                id="dashboard-action-heading"
                title={
                    primaryLesson !== null || primaryLab !== null
                        ? 'Continue learning'
                        : journeyComplete
                          ? 'Current journey'
                          : 'Start learning'
                }
                description={
                    primaryLesson !== null || primaryLab !== null
                        ? 'Resume the most relevant work already in progress.'
                        : journeyComplete
                          ? 'Review the curriculum and labs you have completed.'
                          : 'Begin with the next available curriculum lesson.'
                }
            />

            <Card className="border-primary/25 gap-0 overflow-hidden rounded-lg border-l-2 border-l-blue-500 py-0 shadow-none">
                <CardContent
                    className={
                        hasSecondaryWork
                            ? 'grid gap-0 p-0 lg:grid-cols-[minmax(0,1.55fr)_minmax(16rem,0.65fr)]'
                            : 'p-0'
                    }
                >
                    <div className="flex min-w-0 flex-col gap-6 px-5 py-6 sm:px-6">
                        {primaryLesson !== null ? (
                            <LessonAction lesson={primaryLesson} />
                        ) : primaryLab !== null ? (
                            <LabAction lab={primaryLab} summary={labSummary} />
                        ) : nextLearningPath?.next_lesson !== null &&
                          nextLearningPath?.next_lesson !== undefined ? (
                            <NextLessonAction
                                learningPath={nextLearningPath}
                                lesson={nextLearningPath.next_lesson}
                            />
                        ) : journeyComplete ? (
                            <CompletedAction />
                        ) : (
                            <EmptyAction hasLabs={labSummary.total > 0} />
                        )}
                    </div>

                    {hasSecondaryWork && (
                        <SecondaryWork
                            lessons={secondaryLessons}
                            labs={secondaryLabs}
                        />
                    )}
                </CardContent>
            </Card>
        </section>
    );
}

function LessonAction({ lesson }: { lesson: InProgressLesson }) {
    return (
        <>
            <div className="flex min-w-0 items-start gap-4">
                <CategoryIcon category={lesson.module_title} size="lg" />
                <div className="min-w-0 space-y-2">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        {lesson.learning_path.title}
                    </p>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-semibold tracking-tight sm:text-xl">
                            {lesson.title}
                        </h3>
                        <Badge
                            variant="outline"
                            className="border-primary/25 text-primary"
                        >
                            In progress
                        </Badge>
                    </div>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                        {lesson.summary}
                    </p>
                </div>
            </div>
            <div className="max-w-2xl space-y-2">
                <div className="flex items-center justify-between gap-4 text-xs">
                    <span className="text-muted-foreground">
                        Path progress · {lesson.learning_path.completed_lessons}{' '}
                        of {lesson.learning_path.lesson_count} lessons
                    </span>
                    <span className="font-mono font-semibold tabular-nums">
                        {lesson.learning_path.completion_percentage}%
                    </span>
                </div>
                <DashboardProgressBar
                    label={`${lesson.learning_path.title} completion`}
                    value={lesson.learning_path.completion_percentage}
                />
            </div>
            <div>
                <Button asChild>
                    <Link
                        href={showLesson({
                            learningPath: lesson.learning_path.slug,
                            lesson: lesson.slug,
                        })}
                        prefetch
                    >
                        Continue lesson
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </>
    );
}

function LabAction({
    lab,
    summary,
}: {
    lab: InProgressLab;
    summary: DashboardProgressSummary;
}) {
    return (
        <>
            <div className="flex min-w-0 items-start gap-4">
                <CategoryIcon category="labs" size="lg" />
                <div className="min-w-0 space-y-2">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        Guided lab
                    </p>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-semibold tracking-tight sm:text-xl">
                            {lab.title}
                        </h3>
                        <Badge
                            variant="outline"
                            className="border-primary/25 text-primary"
                        >
                            In progress
                        </Badge>
                    </div>
                    <p className="text-muted-foreground text-sm leading-6">
                        {formatLabel(lab.vulnerability_type)} · Safe simulated
                        exercise
                    </p>
                </div>
            </div>
            <div className="max-w-2xl space-y-2">
                <div className="flex items-center justify-between gap-4 text-xs">
                    <span className="text-muted-foreground">
                        Lab progress · {summary.completed} of {summary.total}{' '}
                        complete
                    </span>
                    <span className="font-mono font-semibold tabular-nums">
                        {summary.completion_percentage}%
                    </span>
                </div>
                <DashboardProgressBar
                    label="Lab completion"
                    value={summary.completion_percentage}
                />
            </div>
            <div>
                <Button asChild>
                    <Link href={workspaceLab(lab.slug)} prefetch>
                        Open workspace
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </>
    );
}

function NextLessonAction({
    learningPath,
    lesson,
}: {
    learningPath: DashboardLearningPath;
    lesson: NonNullable<DashboardLearningPath['next_lesson']>;
}) {
    return (
        <>
            <div className="flex min-w-0 items-start gap-4">
                <CategoryIcon
                    category={lesson.module_title ?? 'learning'}
                    size="lg"
                />
                <div className="min-w-0 space-y-2">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        {learningPath.title}
                    </p>
                    <h3 className="text-lg font-semibold tracking-tight sm:text-xl">
                        {lesson.title}
                    </h3>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                        {lesson.summary}
                    </p>
                    {lesson.module_title !== null && (
                        <p className="text-xs font-medium">
                            {lesson.module_title}
                        </p>
                    )}
                </div>
            </div>
            <div>
                <Button asChild>
                    <Link
                        href={showLesson({
                            learningPath: learningPath.slug,
                            lesson: lesson.slug,
                        })}
                        prefetch
                    >
                        Start lesson
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </>
    );
}

function CompletedAction() {
    return (
        <>
            <div className="flex items-start gap-4">
                <CategoryIcon category="completion" size="lg" />
                <div className="space-y-2">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        Available journey complete
                    </p>
                    <h3 className="text-lg font-semibold tracking-tight sm:text-xl">
                        Learning complete
                    </h3>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                        You have completed all available SecureLab lessons and
                        labs. The curriculum remains open for review.
                    </p>
                </div>
            </div>
            <div>
                <Button asChild variant="outline">
                    <Link href={progressRoute()} prefetch>
                        View progress
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </>
    );
}

function EmptyAction({ hasLabs }: { hasLabs: boolean }) {
    return (
        <div className="flex items-start gap-4">
            <CategoryIcon category="learning" size="lg" />
            <div className="space-y-2">
                <h3 className="font-semibold">
                    {hasLabs
                        ? 'Explore guided labs'
                        : 'No learning content available'}
                </h3>
                <p className="text-muted-foreground max-w-xl text-sm leading-6">
                    {hasLabs
                        ? 'No lesson action is available right now. The lab catalog remains open for review and practice.'
                        : 'Learning actions will appear when curriculum content becomes available.'}
                </p>
                {hasLabs && (
                    <Link
                        href={labsRoute()}
                        prefetch
                        className="text-primary focus-visible:ring-ring inline-flex items-center gap-1.5 text-sm font-medium underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:outline-none"
                    >
                        Browse labs
                        <ArrowRight aria-hidden="true" className="size-3.5" />
                    </Link>
                )}
            </div>
        </div>
    );
}

function SecondaryWork({
    lessons,
    labs,
}: {
    lessons: InProgressLesson[];
    labs: InProgressLab[];
}) {
    return (
        <aside className="bg-muted/30 border-t px-5 py-5 lg:border-t-0 lg:border-l">
            <p className="text-muted-foreground mb-3 text-xs font-semibold tracking-wide uppercase">
                Also in progress
            </p>
            <ul className="space-y-3">
                {lessons.map((lesson) => (
                    <li key={lesson.slug}>
                        <Link
                            href={showLesson({
                                learningPath: lesson.learning_path.slug,
                                lesson: lesson.slug,
                            })}
                            prefetch
                            className="focus-visible:ring-ring group flex items-start gap-2.5 rounded-md focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <CategoryIcon
                                category={lesson.module_title}
                                size="sm"
                            />
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-medium group-hover:underline">
                                    {lesson.title}
                                </span>
                                <span className="text-muted-foreground block text-xs">
                                    Lesson
                                </span>
                            </span>
                        </Link>
                    </li>
                ))}
                {labs.map((lab) => (
                    <li key={lab.slug}>
                        <Link
                            href={workspaceLab(lab.slug)}
                            prefetch
                            className="focus-visible:ring-ring group flex items-start gap-2.5 rounded-md focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <CategoryIcon category="labs" size="sm" />
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-medium group-hover:underline">
                                    {lab.title}
                                </span>
                                <span className="text-muted-foreground block text-xs">
                                    Lab
                                </span>
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </aside>
    );
}
