import { AchievementBadge } from '@/components/achievements/achievement-badge';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import type {
    DashboardAchievementSummary,
    DashboardProgressSummary,
} from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Card, CardContent } from '@/components/ui/card';

type CurrentPathMetric = {
    title: string;
    completed: number;
    total: number;
    completion_percentage: number;
} | null;

function Metric({
    label,
    value,
    detail,
    percentage,
    icon,
}: {
    label: string;
    value: string;
    detail: string;
    percentage: number;
    icon: React.ReactNode;
}) {
    return (
        <div className="bg-card min-w-0 space-y-4 px-4 py-4 sm:px-5">
            <div className="flex items-start gap-3">
                {icon}
                <div className="min-w-0 flex-1">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        {label}
                    </p>
                    <p className="mt-1 font-mono text-lg font-semibold tabular-nums">
                        {value}
                    </p>
                </div>
                <span className="text-muted-foreground font-mono text-xs tabular-nums">
                    {percentage}%
                </span>
            </div>
            <div className="space-y-2">
                <DashboardProgressBar
                    label={`${label} completion`}
                    value={percentage}
                />
                <p className="text-muted-foreground text-xs leading-5">
                    {detail}
                </p>
            </div>
        </div>
    );
}

export function LearnerStats({
    learning,
    labs,
    achievements,
    currentPath,
}: {
    learning: DashboardProgressSummary;
    labs: DashboardProgressSummary;
    achievements: DashboardAchievementSummary;
    currentPath: CurrentPathMetric;
}) {
    const achievementPercentage =
        achievements.total === 0
            ? 0
            : Math.round((achievements.unlocked / achievements.total) * 100);

    return (
        <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
            <CardContent className="bg-border grid gap-px p-0 sm:grid-cols-2 xl:grid-cols-4">
                <Metric
                    label="Lessons"
                    value={`${learning.completed} / ${learning.total}`}
                    detail={
                        learning.total === 0
                            ? 'No lessons available'
                            : `${learning.in_progress} in progress · ${learning.remaining} remaining`
                    }
                    percentage={learning.completion_percentage}
                    icon={<CategoryIcon category="learning" size="sm" />}
                />
                <Metric
                    label="Labs"
                    value={`${labs.completed} / ${labs.total}`}
                    detail={
                        labs.total === 0
                            ? 'No labs available'
                            : `${labs.in_progress} in progress · ${labs.remaining} remaining`
                    }
                    percentage={labs.completion_percentage}
                    icon={<CategoryIcon category="labs" size="sm" />}
                />
                <Metric
                    label="Achievements"
                    value={`${achievements.unlocked} / ${achievements.total}`}
                    detail={
                        achievements.latest_unlocked === null
                            ? achievements.total === 0
                                ? 'No achievements available'
                                : `${achievements.locked} locked`
                            : `Latest: ${achievements.latest_unlocked.title}`
                    }
                    percentage={achievementPercentage}
                    icon={
                        <AchievementBadge
                            slug={achievements.latest_unlocked?.slug ?? null}
                            state={
                                achievements.unlocked > 0
                                    ? 'unlocked'
                                    : 'locked'
                            }
                            size="sm"
                        />
                    }
                />
                <Metric
                    label="Current path"
                    value={
                        currentPath === null
                            ? '—'
                            : `${currentPath.completion_percentage}%`
                    }
                    detail={
                        currentPath === null
                            ? 'No learning path available'
                            : `${currentPath.completed} of ${currentPath.total} lessons · ${currentPath.title}`
                    }
                    percentage={currentPath?.completion_percentage ?? 0}
                    icon={<CategoryIcon category="learning" size="sm" />}
                />
            </CardContent>
        </Card>
    );
}
