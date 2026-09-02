import { Head } from '@inertiajs/react';
import { BookOpen, CheckCircle2, Clock3, Layers3, Map } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { learn } from '@/routes';

type LearningPathOverview = {
    slug: string;
    title: string;
    description: string;
    difficulty: 'easy' | 'medium' | 'hard';
    module_count: number;
    lesson_count: number;
    estimated_minutes: number;
    completed_lessons: number;
    completion_percentage: number;
    is_complete: boolean;
    next_lesson: null | {
        title: string;
    };
};

type LearningPathsProps = {
    learningPaths: LearningPathOverview[];
};

function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${minutes} min`;
    }

    if (remainingMinutes === 0) {
        return `${hours} hr`;
    }

    return `${hours} hr ${remainingMinutes} min`;
}

export default function LearningPaths({ learningPaths }: LearningPathsProps) {
    return (
        <>
            <Head title="Learning Paths" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Learning Paths
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Follow structured security learning from core concepts
                        through safe, guided labs.
                    </p>
                </header>

                {learningPaths.length === 0 ? (
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardContent className="flex min-h-56 flex-col items-start justify-center gap-4 px-5 py-6 sm:px-6">
                            <div className="border-primary/25 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border">
                                <Map aria-hidden="true" className="size-5" />
                            </div>
                            <div className="max-w-lg space-y-1.5">
                                <h2 className="font-medium">
                                    No learning paths available
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Published curriculum will appear here when
                                    it becomes available.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <section aria-labelledby="available-learning-paths">
                        <h2 id="available-learning-paths" className="sr-only">
                            Available learning paths
                        </h2>
                        <div className="grid gap-4">
                            {learningPaths.map((learningPath) => (
                                <Card
                                    key={learningPath.slug}
                                    className="gap-0 overflow-hidden rounded-lg py-0 shadow-none"
                                >
                                    <CardHeader className="gap-4 border-b px-5 py-5 sm:px-6">
                                        <div className="flex flex-col items-start justify-between gap-3 sm:flex-row sm:gap-4">
                                            <CardTitle className="text-lg leading-6 sm:text-xl">
                                                <h3>{learningPath.title}</h3>
                                            </CardTitle>
                                            <Badge
                                                variant="outline"
                                                className="capitalize"
                                            >
                                                {learningPath.difficulty}
                                            </Badge>
                                        </div>
                                        <CardDescription className="max-w-3xl text-sm leading-6">
                                            {learningPath.description}
                                        </CardDescription>

                                        <dl className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-2 text-xs sm:text-sm">
                                            <div className="flex items-center gap-2">
                                                <Layers3
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                                <dt className="sr-only">
                                                    Modules
                                                </dt>
                                                <dd>
                                                    {learningPath.module_count}{' '}
                                                    {learningPath.module_count ===
                                                    1
                                                        ? 'module'
                                                        : 'modules'}
                                                </dd>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <BookOpen
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                                <dt className="sr-only">
                                                    Lessons
                                                </dt>
                                                <dd>
                                                    {learningPath.lesson_count}{' '}
                                                    {learningPath.lesson_count ===
                                                    1
                                                        ? 'lesson'
                                                        : 'lessons'}
                                                </dd>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Clock3
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                                <dt className="sr-only">
                                                    Estimated duration
                                                </dt>
                                                <dd>
                                                    {formatDuration(
                                                        learningPath.estimated_minutes,
                                                    )}
                                                </dd>
                                            </div>
                                        </dl>
                                    </CardHeader>

                                    <CardContent className="grid gap-5 px-5 py-5 sm:px-6 md:grid-cols-[minmax(0,1fr)_minmax(15rem,0.5fr)] md:items-center">
                                        <div className="space-y-3">
                                            <div className="flex items-baseline justify-between gap-4">
                                                <p className="text-sm font-medium">
                                                    Progress
                                                </p>
                                                <p className="font-mono text-sm font-semibold tabular-nums">
                                                    {
                                                        learningPath.completion_percentage
                                                    }
                                                    %
                                                </p>
                                            </div>
                                            <div
                                                role="progressbar"
                                                aria-label={`${learningPath.title} completion`}
                                                aria-valuemin={0}
                                                aria-valuemax={100}
                                                aria-valuenow={
                                                    learningPath.completion_percentage
                                                }
                                                className="bg-muted h-2 overflow-hidden rounded-sm"
                                            >
                                                <div
                                                    className="bg-primary h-full rounded-sm"
                                                    style={{
                                                        width: `${learningPath.completion_percentage}%`,
                                                    }}
                                                />
                                            </div>
                                            <p className="text-muted-foreground text-xs sm:text-sm">
                                                {learningPath.completed_lessons}{' '}
                                                of {learningPath.lesson_count}{' '}
                                                lessons complete
                                            </p>
                                        </div>

                                        <div className="bg-muted/40 flex min-h-20 items-center gap-3 rounded-md border p-4">
                                            {learningPath.is_complete ? (
                                                <>
                                                    <CheckCircle2
                                                        aria-hidden="true"
                                                        className="text-primary size-5 shrink-0"
                                                    />
                                                    <div className="space-y-1">
                                                        <p className="text-sm font-medium">
                                                            Path complete
                                                        </p>
                                                        <p className="text-muted-foreground text-xs leading-5">
                                                            Every lesson in this
                                                            path is complete.
                                                        </p>
                                                    </div>
                                                </>
                                            ) : learningPath.next_lesson ? (
                                                <>
                                                    <BookOpen
                                                        aria-hidden="true"
                                                        className="text-primary size-5 shrink-0"
                                                    />
                                                    <div className="min-w-0 space-y-1">
                                                        <p className="text-muted-foreground text-xs font-medium">
                                                            Next lesson
                                                        </p>
                                                        <p className="text-sm leading-5 font-medium">
                                                            {
                                                                learningPath
                                                                    .next_lesson
                                                                    .title
                                                            }
                                                        </p>
                                                    </div>
                                                </>
                                            ) : (
                                                <>
                                                    <BookOpen
                                                        aria-hidden="true"
                                                        className="text-muted-foreground size-5 shrink-0"
                                                    />
                                                    <div className="space-y-1">
                                                        <p className="text-sm font-medium">
                                                            No lessons yet
                                                        </p>
                                                        <p className="text-muted-foreground text-xs leading-5">
                                                            Lessons will appear
                                                            when this path is
                                                            expanded.
                                                        </p>
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                )}
            </main>
        </>
    );
}

LearningPaths.layout = {
    breadcrumbs: [
        {
            title: 'Learning Paths',
            href: learn(),
        },
    ],
};
