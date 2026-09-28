import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Map } from 'lucide-react';
import { cn } from '@/lib/utils';
import { show as showLearningPath } from '@/routes/learning-paths';
import { show as showLesson } from '@/routes/lessons';

export type LessonNavigationItem = {
    slug: string;
    title: string;
    module_title: string;
};

function LessonLink({
    direction,
    lesson,
    learningPathSlug,
}: {
    direction: 'previous' | 'next';
    lesson: LessonNavigationItem;
    learningPathSlug: string;
}) {
    const isPrevious = direction === 'previous';

    return (
        <Link
            href={showLesson({
                learningPath: learningPathSlug,
                lesson: lesson.slug,
            })}
            prefetch
            className="hover:border-primary/50 hover:bg-muted/30 focus-visible:ring-ring group flex min-h-28 flex-col justify-center gap-2 rounded-lg border px-4 py-4 outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        >
            <span
                className={cn(
                    'text-muted-foreground flex items-center gap-1.5 text-xs font-medium',
                    !isPrevious && 'justify-end',
                )}
            >
                {isPrevious && (
                    <ArrowLeft aria-hidden="true" className="size-3.5" />
                )}
                {isPrevious ? 'Previous lesson' : 'Next lesson'}
                {!isPrevious && (
                    <ArrowRight aria-hidden="true" className="size-3.5" />
                )}
            </span>
            <span
                className={cn(
                    'group-hover:text-primary text-sm leading-5 font-medium',
                    !isPrevious && 'text-right',
                )}
            >
                {lesson.title}
            </span>
            <span
                className={cn(
                    'text-muted-foreground text-xs',
                    !isPrevious && 'text-right',
                )}
            >
                {lesson.module_title}
            </span>
        </Link>
    );
}

export function LessonNavigation({
    learningPath,
    previousLesson,
    nextLesson,
}: {
    learningPath: { slug: string; title: string };
    previousLesson: LessonNavigationItem | null;
    nextLesson: LessonNavigationItem | null;
}) {
    return (
        <nav
            aria-label="Curriculum navigation"
            className="grid gap-3 border-t pt-8 sm:grid-cols-2"
        >
            {previousLesson && (
                <LessonLink
                    direction="previous"
                    lesson={previousLesson}
                    learningPathSlug={learningPath.slug}
                />
            )}
            {nextLesson ? (
                <div className={cn(!previousLesson && 'sm:col-start-2')}>
                    <LessonLink
                        direction="next"
                        lesson={nextLesson}
                        learningPathSlug={learningPath.slug}
                    />
                </div>
            ) : (
                <Link
                    href={showLearningPath(learningPath.slug)}
                    prefetch
                    className="hover:border-primary/50 hover:bg-muted/30 focus-visible:ring-ring group flex min-h-28 flex-col items-end justify-center gap-2 rounded-lg border px-4 py-4 text-right outline-none focus-visible:ring-2 focus-visible:ring-offset-2 sm:col-start-2"
                >
                    <span className="text-muted-foreground flex items-center gap-1.5 text-xs font-medium">
                        Learning path
                        <Map aria-hidden="true" className="size-3.5" />
                    </span>
                    <span className="group-hover:text-primary text-sm leading-5 font-medium">
                        Review {learningPath.title}
                    </span>
                </Link>
            )}
        </nav>
    );
}
