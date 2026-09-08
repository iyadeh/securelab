import { Head } from '@inertiajs/react';
import { Award, CheckCircle2, LockKeyhole } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { achievements as achievementsRoute } from '@/routes';

type AchievementState = 'locked' | 'unlocked';

type AchievementSummary = {
    total: number;
    unlocked: number;
    locked: number;
};

type AchievementItem = {
    slug: string;
    title: string;
    description: string;
    category: string;
    position: number;
    state: AchievementState;
    unlocked_at: string | null;
};

type AchievementsPageProps = {
    summary: AchievementSummary;
    achievements: AchievementItem[];
};

const categoryLabels: Record<string, string> = {
    learning: 'Learning',
    labs: 'Labs',
    completion: 'Completion',
};

const unlockDateFormatter = new Intl.DateTimeFormat('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

function formatCategory(category: string): string {
    return (
        categoryLabels[category] ??
        category
            .replaceAll('_', ' ')
            .replace(/\b\w/g, (character) => character.toUpperCase())
    );
}

function formatUnlockDate(unlockedAt: string): string {
    return unlockDateFormatter.format(new Date(unlockedAt));
}

function AchievementRow({ achievement }: { achievement: AchievementItem }) {
    const isUnlocked = achievement.state === 'unlocked';

    return (
        <li>
            <article className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6 sm:px-6">
                <div className="flex min-w-0 items-start gap-3">
                    <div
                        className={
                            isUnlocked
                                ? 'border-primary/25 bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-md border'
                                : 'border-border bg-muted text-muted-foreground flex size-9 shrink-0 items-center justify-center rounded-md border'
                        }
                    >
                        {isUnlocked ? (
                            <CheckCircle2
                                aria-hidden="true"
                                className="size-4.5"
                            />
                        ) : (
                            <LockKeyhole
                                aria-hidden="true"
                                className="size-4.5"
                            />
                        )}
                    </div>
                    <div className="min-w-0 space-y-1.5">
                        <h3 className="text-sm font-semibold sm:text-base">
                            {achievement.title}
                        </h3>
                        <p className="text-muted-foreground max-w-3xl text-sm leading-6">
                            {achievement.description}
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 items-center justify-between gap-4 pl-12 sm:flex-col sm:items-end sm:justify-start sm:gap-1 sm:pl-0">
                    <span
                        className={
                            isUnlocked
                                ? 'text-primary text-xs font-medium'
                                : 'text-muted-foreground text-xs font-medium'
                        }
                    >
                        {isUnlocked ? 'Unlocked' : 'Locked'}
                    </span>
                    {achievement.unlocked_at !== null && (
                        <time
                            dateTime={achievement.unlocked_at}
                            className="text-muted-foreground text-xs tabular-nums"
                        >
                            {formatUnlockDate(achievement.unlocked_at)}
                        </time>
                    )}
                </div>
            </article>
        </li>
    );
}

export default function AchievementsPage({
    summary,
    achievements,
}: AchievementsPageProps) {
    const completionPercentage =
        summary.total === 0
            ? 0
            : Math.round((summary.unlocked / summary.total) * 100);
    const achievementsByCategory = achievements.reduce<
        Record<string, AchievementItem[]>
    >((groups, achievement) => {
        groups[achievement.category] ??= [];
        groups[achievement.category].push(achievement);

        return groups;
    }, {});

    return (
        <>
            <Head title="Achievements" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Achievements
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Track milestones earned through your SecureLab learning
                        progress.
                    </p>
                </header>

                {achievements.length === 0 ? (
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardContent className="flex min-h-56 flex-col items-start justify-center gap-4 px-5 py-6 sm:px-6">
                            <div className="border-primary/25 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border">
                                <Award aria-hidden="true" className="size-5" />
                            </div>
                            <div className="max-w-lg space-y-1.5">
                                <h2 className="font-medium">
                                    No achievements available
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Learning milestones will appear here when
                                    they become available.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <section aria-labelledby="achievement-summary-heading">
                            <Card className="gap-0 rounded-lg py-0 shadow-none">
                                <CardContent className="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                                    <div className="flex min-w-0 flex-1 items-start gap-3">
                                        <div className="border-primary/25 bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md border">
                                            <Award
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <h2
                                                id="achievement-summary-heading"
                                                className="font-semibold"
                                            >
                                                {summary.unlocked} of{' '}
                                                {summary.total} achievements
                                                unlocked
                                            </h2>
                                            <p className="text-muted-foreground text-sm leading-6">
                                                {summary.locked}{' '}
                                                {summary.locked === 1
                                                    ? 'milestone remains'
                                                    : 'milestones remain'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="w-full space-y-2 sm:max-w-sm">
                                        <div
                                            role="progressbar"
                                            aria-label="Achievement completion"
                                            aria-valuemin={0}
                                            aria-valuemax={summary.total}
                                            aria-valuenow={summary.unlocked}
                                            aria-valuetext={`${summary.unlocked} of ${summary.total} achievements unlocked`}
                                            className="bg-muted h-2 overflow-hidden rounded-sm"
                                        >
                                            <div
                                                className="bg-primary h-full rounded-sm"
                                                style={{
                                                    width: `${completionPercentage}%`,
                                                }}
                                            />
                                        </div>
                                        <p className="text-muted-foreground text-right font-mono text-xs tabular-nums">
                                            {completionPercentage}%
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </section>

                        <div className="space-y-7">
                            {Object.entries(achievementsByCategory).map(
                                ([category, categoryAchievements]) => (
                                    <section
                                        key={category}
                                        aria-labelledby={`achievement-category-${category}`}
                                        className="space-y-3"
                                    >
                                        <h2
                                            id={`achievement-category-${category}`}
                                            className="text-lg font-semibold tracking-tight"
                                        >
                                            {formatCategory(category)}
                                        </h2>
                                        <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                                            <CardContent className="px-0">
                                                <ul className="divide-y">
                                                    {categoryAchievements.map(
                                                        (achievement) => (
                                                            <AchievementRow
                                                                key={
                                                                    achievement.slug
                                                                }
                                                                achievement={
                                                                    achievement
                                                                }
                                                            />
                                                        ),
                                                    )}
                                                </ul>
                                            </CardContent>
                                        </Card>
                                    </section>
                                ),
                            )}
                        </div>
                    </>
                )}
            </main>
        </>
    );
}

AchievementsPage.layout = {
    breadcrumbs: [
        {
            title: 'Achievements',
            href: achievementsRoute(),
        },
    ],
};
