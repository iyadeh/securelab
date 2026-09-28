import type { LucideIcon } from 'lucide-react';
import { BookOpen, Brain, Microscope, Search, ShieldCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

type StagePresentation = {
    icon: LucideIcon;
    context: string;
    iconClassName: string;
};

const stagePresentations: Record<string, StagePresentation> = {
    Learn: {
        icon: BookOpen,
        context: 'Foundation',
        iconClassName: 'border-blue-500/25 bg-blue-500/10 text-blue-400',
    },
    Understand: {
        icon: Brain,
        context: 'Mechanism',
        iconClassName: 'border-violet-500/25 bg-violet-500/10 text-violet-400',
    },
    Exploit: {
        icon: Search,
        context: 'Safe demonstration',
        iconClassName: 'border-amber-500/25 bg-amber-500/10 text-amber-400',
    },
    Analyze: {
        icon: Microscope,
        context: 'Root cause and impact',
        iconClassName: 'border-blue-500/25 bg-blue-500/10 text-blue-400',
    },
    Fix: {
        icon: ShieldCheck,
        context: 'Defensive remediation',
        iconClassName:
            'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
    },
};

const defaultPresentation: StagePresentation = {
    icon: BookOpen,
    context: 'Lesson content',
    iconClassName: 'border-border bg-muted text-muted-foreground',
};

export function LessonSection({
    id,
    position,
    title,
    paragraphs,
}: {
    id: string;
    position: number;
    title: string;
    paragraphs: string[];
}) {
    const presentation = stagePresentations[title] ?? defaultPresentation;
    const Icon = presentation.icon;

    return (
        <section
            id={id}
            aria-labelledby={`${id}-heading`}
            className="scroll-mt-20 border-t py-8 first:border-t-0 sm:py-10"
        >
            <div className="grid gap-5 sm:grid-cols-[3.5rem_minmax(0,1fr)] sm:gap-6">
                <div className="flex items-center gap-3 sm:flex-col sm:items-start">
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-10 items-center justify-center rounded-md border',
                            presentation.iconClassName,
                        )}
                    >
                        <Icon className="size-4.5" strokeWidth={1.75} />
                    </span>
                    <span className="text-muted-foreground font-mono text-[10px] tabular-nums sm:pl-1">
                        {String(position).padStart(2, '0')}
                    </span>
                </div>

                <div className="min-w-0">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                        <h2
                            id={`${id}-heading`}
                            className="text-xl font-semibold tracking-tight sm:text-2xl"
                        >
                            {title}
                        </h2>
                        <p className="text-muted-foreground text-xs font-medium">
                            {presentation.context}
                        </p>
                    </div>
                    <div className="text-foreground/90 mt-5 space-y-4 text-base leading-7">
                        {paragraphs.map((paragraph, paragraphIndex) => (
                            <p
                                key={`${id}-${paragraphIndex}`}
                                className="break-words whitespace-pre-wrap"
                            >
                                {paragraph}
                            </p>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
