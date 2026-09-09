import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Award,
    BookOpenCheck,
    CheckCircle2,
    Circle,
    CircleDot,
    FlaskConical,
    Map,
    Settings2,
} from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import {
    achievements as achievementsRoute,
    labs as labsRoute,
    progress as progressRoute,
} from '@/routes';
import { show as showLearningPath } from '@/routes/learning-paths';
import { edit as editProfile, show as showProfile } from '@/routes/profile';

type ProgressState = 'not_started' | 'in_progress' | 'completed';

type Learner = {
    name: string;
    email: string;
    joined_at: string | null;
};

type ProgressSummary = {
    total: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

type AchievementSummary = {
    total: number;
    unlocked: number;
    locked: number;
};

type LearningPathSummary = {
    slug: string;
    title: string;
    lesson_count: number;
    completed_lessons: number;
    completion_percentage: number;
    state: ProgressState;
};

type ProfilePageProps = {
    learner: Learner;
    learningSummary: ProgressSummary;
    labSummary: ProgressSummary;
    achievementSummary: AchievementSummary;
    learningPaths: LearningPathSummary[];
};

const joinedDateFormatter = new Intl.DateTimeFormat('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

function formatJoinedDate(joinedAt: string): string {
    return joinedDateFormatter.format(new Date(joinedAt));
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

function SummaryPanel({
    title,
    count,
    percentage,
    detail,
    linkLabel,
    href,
    icon: Icon,
}: {
    title: string;
    count: string;
    percentage: number;
    detail: string;
    linkLabel: string;
    href: ReturnType<typeof progressRoute>;
    icon: LucideIcon;
}) {
    return (
        <div className="bg-card space-y-4 px-5 py-5 sm:px-6">
            <div className="flex items-start justify-between gap-4">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="border-primary/25 bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-md border">
                        <Icon aria-hidden="true" className="size-4.5" />
                    </div>
                    <div className="min-w-0 space-y-1">
                        <h3 className="text-sm font-semibold">{title}</h3>
                        <p className="text-muted-foreground text-xs leading-5">
                            {count}
                        </p>
                    </div>
                </div>
                <p className="font-mono text-lg font-semibold tabular-nums">
                    {percentage}%
                </p>
            </div>

            <div className="space-y-2">
                <ProgressBar label={`${title} completion`} value={percentage} />
                <p className="text-muted-foreground text-xs leading-5">
                    {detail}
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

export default function ProfilePage({
    learner,
    learningSummary,
    labSummary,
    achievementSummary,
    learningPaths,
}: ProfilePageProps) {
    const getInitials = useInitials();
    const achievementPercentage =
        achievementSummary.total === 0
            ? 0
            : Math.round(
                  (achievementSummary.unlocked / achievementSummary.total) *
                      100,
              );
    const learningDetail =
        learningSummary.total === 0
            ? 'No lessons available'
            : learningSummary.completed === learningSummary.total
              ? 'Completed'
              : `${learningSummary.in_progress} in progress · ${learningSummary.remaining} remaining`;
    const labDetail =
        labSummary.total === 0
            ? 'No labs available'
            : labSummary.completed === labSummary.total
              ? 'Completed'
              : `${labSummary.in_progress} in progress · ${labSummary.remaining} remaining`;
    const achievementDetail =
        achievementSummary.total === 0
            ? 'No achievements available'
            : achievementSummary.locked === 0
              ? 'All achievements unlocked'
              : `${achievementSummary.locked} locked`;

    return (
        <>
            <Head title="Profile" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Profile
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Your SecureLab identity and learning journey at a
                        glance.
                    </p>
                </header>

                <section aria-labelledby="learner-identity-heading">
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardContent className="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div className="flex min-w-0 items-center gap-4">
                                <Avatar
                                    aria-hidden="true"
                                    className="border-primary/25 size-12 border"
                                >
                                    <AvatarFallback className="bg-primary/10 text-primary rounded-md text-sm font-semibold">
                                        {getInitials(learner.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 space-y-1">
                                    <h2
                                        id="learner-identity-heading"
                                        className="text-lg font-semibold tracking-tight"
                                    >
                                        {learner.name}
                                    </h2>
                                    <p className="text-muted-foreground text-sm break-all">
                                        {learner.email}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {learner.joined_at === null
                                            ? 'Member since date unavailable'
                                            : `Member since ${formatJoinedDate(learner.joined_at)}`}
                                    </p>
                                </div>
                            </div>

                            <Button asChild size="sm" variant="outline">
                                <Link href={editProfile()} prefetch>
                                    <Settings2 aria-hidden="true" />
                                    Manage account settings
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>

                <section
                    aria-labelledby="profile-overview-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="profile-overview-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Learning overview
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Learning, labs, and achievements remain distinct
                            measures of your journey.
                        </p>
                    </div>

                    <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                        <CardContent className="bg-border grid p-0 md:grid-cols-3 md:gap-px">
                            <SummaryPanel
                                title="Learning"
                                count={
                                    learningSummary.total === 0
                                        ? 'No lessons available'
                                        : `${learningSummary.completed} of ${learningSummary.total} lessons complete`
                                }
                                percentage={
                                    learningSummary.completion_percentage
                                }
                                detail={learningDetail}
                                href={progressRoute()}
                                linkLabel="View progress"
                                icon={BookOpenCheck}
                            />
                            <SummaryPanel
                                title="Labs"
                                count={
                                    labSummary.total === 0
                                        ? 'No labs available'
                                        : `${labSummary.completed} of ${labSummary.total} labs complete`
                                }
                                percentage={labSummary.completion_percentage}
                                detail={labDetail}
                                href={labsRoute()}
                                linkLabel="Browse labs"
                                icon={FlaskConical}
                            />
                            <SummaryPanel
                                title="Achievements"
                                count={
                                    achievementSummary.total === 0
                                        ? 'No achievements available'
                                        : `${achievementSummary.unlocked} of ${achievementSummary.total} unlocked`
                                }
                                percentage={achievementPercentage}
                                detail={achievementDetail}
                                href={achievementsRoute()}
                                linkLabel="View achievements"
                                icon={Award}
                            />
                        </CardContent>
                    </Card>
                </section>

                <section
                    aria-labelledby="profile-paths-heading"
                    className="space-y-4"
                >
                    <div className="space-y-1">
                        <h2
                            id="profile-paths-heading"
                            className="text-xl font-semibold tracking-tight"
                        >
                            Learning paths
                        </h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Concise progress across curriculum paths in your
                            journey.
                        </p>
                    </div>

                    {learningPaths.length === 0 ? (
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardContent className="flex min-h-40 items-start gap-3 px-5 py-6 sm:px-6">
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
                                            <div className="min-w-0 space-y-2">
                                                <h3 className="text-sm font-semibold sm:text-base">
                                                    {learningPath.title}
                                                </h3>
                                                <StateLabel
                                                    state={learningPath.state}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex items-baseline justify-between gap-4 text-xs">
                                                    <span className="text-muted-foreground">
                                                        {
                                                            learningPath.completed_lessons
                                                        }{' '}
                                                        of{' '}
                                                        {
                                                            learningPath.lesson_count
                                                        }{' '}
                                                        lessons
                                                    </span>
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
                                                    View learning path
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

ProfilePage.layout = {
    breadcrumbs: [
        {
            title: 'Profile',
            href: showProfile(),
        },
    ],
};
