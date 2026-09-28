import { ChevronRight } from 'lucide-react';

export type LessonRoadmapStage = {
    id: string;
    title: string;
};

export function LessonRoadmap({ stages }: { stages: LessonRoadmapStage[] }) {
    return (
        <nav
            aria-label="Lesson stages"
            className="bg-muted/20 rounded-lg border px-3 py-3 sm:px-4"
        >
            <ol className="grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-0">
                {stages.map((stage, index) => (
                    <li key={stage.id} className="relative min-w-0 sm:pr-5">
                        <a
                            href={`#${stage.id}`}
                            className="hover:bg-muted focus-visible:ring-ring flex min-h-11 items-center gap-2 rounded-md px-2 py-2 text-sm font-medium outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        >
                            <span className="bg-background text-muted-foreground flex size-6 shrink-0 items-center justify-center rounded-sm border font-mono text-[10px] tabular-nums">
                                {String(index + 1).padStart(2, '0')}
                            </span>
                            <span className="truncate">{stage.title}</span>
                        </a>
                        {index < stages.length - 1 && (
                            <ChevronRight
                                aria-hidden="true"
                                className="text-border absolute top-1/2 right-0 hidden size-4 -translate-y-1/2 sm:block"
                            />
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
