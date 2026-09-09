import type { LucideIcon } from 'lucide-react';
import {
    Award,
    BookOpen,
    Braces,
    CircleCheck,
    FlaskConical,
    KeyRound,
    PanelsTopLeft,
    Shield,
    ShieldCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';

type CategoryIconSize = 'sm' | 'md' | 'lg';
type CategoryTone = 'blue' | 'amber' | 'violet' | 'emerald' | 'neutral';

type CategoryDefinition = {
    icon: LucideIcon;
    tone: CategoryTone;
};

const categoryDefinitions: Record<string, CategoryDefinition> = {
    achievements: { icon: Award, tone: 'violet' },
    authentication: { icon: KeyRound, tone: 'blue' },
    completion: { icon: CircleCheck, tone: 'emerald' },
    injection: { icon: Braces, tone: 'amber' },
    labs: { icon: FlaskConical, tone: 'violet' },
    learning: { icon: BookOpen, tone: 'blue' },
    'cross-site scripting': { icon: PanelsTopLeft, tone: 'violet' },
    'access & account security': { icon: ShieldCheck, tone: 'emerald' },
};

const toneClasses: Record<CategoryTone, string> = {
    blue: 'border-blue-500/25 bg-blue-500/10 text-blue-400',
    amber: 'border-amber-500/25 bg-amber-500/10 text-amber-400',
    violet: 'border-violet-500/25 bg-violet-500/10 text-violet-400',
    emerald: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
    neutral: 'border-border bg-muted text-muted-foreground',
};

const sizeClasses: Record<CategoryIconSize, string> = {
    sm: 'size-8 [&>svg]:size-3.5',
    md: 'size-10 [&>svg]:size-4.5',
    lg: 'size-12 [&>svg]:size-5',
};

function normalizeCategory(category: string): string {
    return category.trim().replaceAll('_', ' ').toLowerCase();
}

export function CategoryIcon({
    category,
    size = 'md',
    className,
}: {
    category: string;
    size?: CategoryIconSize;
    className?: string;
}) {
    const definition = categoryDefinitions[normalizeCategory(category)] ?? {
        icon: Shield,
        tone: 'neutral' as const,
    };
    const Icon = definition.icon;

    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex shrink-0 items-center justify-center rounded-md border',
                sizeClasses[size],
                toneClasses[definition.tone],
                className,
            )}
        >
            <Icon strokeWidth={1.75} />
        </span>
    );
}
