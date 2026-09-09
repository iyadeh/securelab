import type { LucideIcon } from 'lucide-react';
import {
    Award,
    BadgeCheck,
    BookOpenCheck,
    FlaskConical,
    Route,
    ShieldCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';

type AchievementBadgeState = 'locked' | 'unlocked';
type AchievementBadgeSize = 'sm' | 'md';

const achievementIcons: Record<string, LucideIcon> = {
    'first-lesson': BookOpenCheck,
    'first-lab': FlaskConical,
    'path-complete': Route,
    'lab-series-complete': ShieldCheck,
    'security-foundations-complete': BadgeCheck,
};

const sizeClasses: Record<AchievementBadgeSize, string> = {
    sm: 'size-9 [&>svg]:size-4',
    md: 'size-11 [&>svg]:size-5',
};

export function AchievementBadge({
    slug,
    state,
    size = 'md',
    className,
}: {
    slug: string | null;
    state: AchievementBadgeState;
    size?: AchievementBadgeSize;
    className?: string;
}) {
    const Icon = slug === null ? Award : (achievementIcons[slug] ?? Award);

    return (
        <span
            aria-hidden="true"
            data-state={state}
            className={cn(
                'flex shrink-0 items-center justify-center rounded-lg border',
                sizeClasses[size],
                state === 'unlocked'
                    ? 'border-blue-500/30 bg-blue-500/10 text-blue-400'
                    : 'border-border bg-muted text-muted-foreground',
                className,
            )}
        >
            <Icon strokeWidth={1.75} />
        </span>
    );
}
