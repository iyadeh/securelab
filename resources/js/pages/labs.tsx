import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    CheckCircle2,
    Circle,
    CircleDot,
    FlaskConical,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { labs as labsRoute } from '@/routes';
import { show as showLesson } from '@/routes/lessons';

type LabState = 'not_started' | 'in_progress' | 'completed';

type LabDifficulty = 'easy' | 'medium' | 'hard';

type DifficultyFilter = 'all' | LabDifficulty;

type LabSummary = {
    total: number;
    started: number;
    in_progress: number;
    completed: number;
    remaining: number;
};

type RelatedLesson = {
    slug: string;
    title: string;
    module: {
        title: string;
    };
    learning_path: {
        slug: string;
        title: string;
    };
};

type CatalogLab = {
    slug: string;
    title: string;
    description: string;
    vulnerability_type: string;
    difficulty: LabDifficulty;
    state: LabState;
    lesson: RelatedLesson | null;
};

type LabsPageProps = {
    summary: LabSummary;
    labs: CatalogLab[];
};

const difficultyFilters: DifficultyFilter[] = ['all', 'easy', 'medium', 'hard'];

function formatVulnerabilityType(vulnerabilityType: string): string {
    return vulnerabilityType.replaceAll('_', ' ');
}

function LabStateIndicator({ state }: { state: LabState }) {
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

export default function LabsPage({ summary, labs }: LabsPageProps) {
    const [difficultyFilter, setDifficultyFilter] =
        useState<DifficultyFilter>('all');
    const visibleLabs =
        difficultyFilter === 'all'
            ? labs
            : labs.filter((lab) => lab.difficulty === difficultyFilter);
    const summaryItems = [
        ['Available labs', summary.total],
        ['In progress', summary.in_progress],
        ['Completed', summary.completed],
        ['Remaining', summary.remaining],
    ] as const;

    return (
        <>
            <Head title="Labs" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Labs
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Explore safe, guided exercises for understanding common
                        web vulnerabilities and their root causes.
                    </p>
                </header>

                {labs.length === 0 ? (
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardContent className="flex min-h-56 flex-col items-start justify-center gap-4 px-5 py-6 sm:px-6">
                            <div className="border-primary/25 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border">
                                <FlaskConical
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            </div>
                            <div className="max-w-lg space-y-1.5">
                                <h2 className="font-medium">
                                    No labs available
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Guided security exercises will appear here
                                    when they become available.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <section aria-labelledby="lab-progress-heading">
                            <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                                <h2
                                    id="lab-progress-heading"
                                    className="sr-only"
                                >
                                    Lab progress
                                </h2>
                                <CardContent className="px-0">
                                    <dl className="bg-border grid grid-cols-2 gap-px sm:grid-cols-4">
                                        {summaryItems.map(([label, value]) => (
                                            <div
                                                key={label}
                                                className="bg-card flex min-h-20 flex-col justify-center gap-1 px-5 py-4 sm:px-6"
                                            >
                                                <dt className="text-muted-foreground text-xs">
                                                    {label}
                                                </dt>
                                                <dd className="font-mono text-lg font-semibold tabular-nums">
                                                    {value}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </CardContent>
                            </Card>
                        </section>

                        <section
                            aria-labelledby="available-labs-heading"
                            className="space-y-4"
                        >
                            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                <div className="space-y-1">
                                    <h2
                                        id="available-labs-heading"
                                        className="text-xl font-semibold tracking-tight"
                                    >
                                        Available labs
                                    </h2>
                                    <p className="text-muted-foreground text-sm leading-6">
                                        Catalog metadata and your current lab
                                        state.
                                    </p>
                                </div>
                                <div
                                    role="group"
                                    aria-label="Filter labs by difficulty"
                                    className="flex flex-wrap gap-2"
                                >
                                    {difficultyFilters.map((filter) => (
                                        <Button
                                            key={filter}
                                            type="button"
                                            size="sm"
                                            variant={
                                                difficultyFilter === filter
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                            aria-pressed={
                                                difficultyFilter === filter
                                            }
                                            onClick={() =>
                                                setDifficultyFilter(filter)
                                            }
                                            className="capitalize"
                                        >
                                            {filter}
                                        </Button>
                                    ))}
                                </div>
                            </div>

                            {visibleLabs.length === 0 ? (
                                <Card className="gap-0 rounded-lg py-0 shadow-none">
                                    <CardContent className="px-5 py-8 sm:px-6">
                                        <p className="text-sm font-medium">
                                            No {difficultyFilter} labs available
                                        </p>
                                        <p className="text-muted-foreground mt-1 text-sm leading-6">
                                            Choose another difficulty to view
                                            available exercises.
                                        </p>
                                    </CardContent>
                                </Card>
                            ) : (
                                <div className="grid gap-3">
                                    {visibleLabs.map((lab) => (
                                        <article key={lab.slug}>
                                            <Card className="gap-0 rounded-lg py-0 shadow-none">
                                                <CardContent className="grid gap-4 px-5 py-5 sm:px-6 md:grid-cols-[2.75rem_minmax(0,1fr)_auto] md:items-center">
                                                    <div className="bg-muted flex size-10 items-center justify-center rounded-md border">
                                                        <FlaskConical
                                                            aria-hidden="true"
                                                            className="text-primary size-5"
                                                        />
                                                    </div>

                                                    <div className="min-w-0 space-y-3">
                                                        <div className="space-y-1.5">
                                                            <p className="text-primary font-mono text-xs capitalize">
                                                                {formatVulnerabilityType(
                                                                    lab.vulnerability_type,
                                                                )}
                                                            </p>
                                                            <h3 className="text-base leading-6 font-semibold sm:text-lg">
                                                                {lab.title}
                                                            </h3>
                                                            <p className="text-muted-foreground max-w-3xl text-sm leading-6">
                                                                {
                                                                    lab.description
                                                                }
                                                            </p>
                                                        </div>

                                                        {lab.lesson && (
                                                            <div className="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                                                <BookOpen
                                                                    aria-hidden="true"
                                                                    className="size-3.5"
                                                                />
                                                                <span>
                                                                    Related
                                                                    lesson:
                                                                </span>
                                                                <Link
                                                                    href={showLesson(
                                                                        {
                                                                            learningPath:
                                                                                lab
                                                                                    .lesson
                                                                                    .learning_path
                                                                                    .slug,
                                                                            lesson: lab
                                                                                .lesson
                                                                                .slug,
                                                                        },
                                                                    )}
                                                                    prefetch
                                                                    className="text-foreground hover:text-primary focus-visible:ring-ring rounded-sm font-medium outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                                                >
                                                                    {
                                                                        lab
                                                                            .lesson
                                                                            .title
                                                                    }
                                                                </Link>
                                                                <span aria-hidden="true">
                                                                    &middot;
                                                                </span>
                                                                <span>
                                                                    {
                                                                        lab
                                                                            .lesson
                                                                            .module
                                                                            .title
                                                                    }{' '}
                                                                    &middot;{' '}
                                                                    {
                                                                        lab
                                                                            .lesson
                                                                            .learning_path
                                                                            .title
                                                                    }
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>

                                                    <div className="flex flex-wrap items-center gap-3 md:flex-col md:items-end">
                                                        <LabStateIndicator
                                                            state={lab.state}
                                                        />
                                                        <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                                            <span>
                                                                Difficulty
                                                            </span>
                                                            <Badge
                                                                variant="outline"
                                                                className="capitalize"
                                                            >
                                                                {lab.difficulty}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </section>
                    </>
                )}
            </main>
        </>
    );
}

LabsPage.layout = {
    breadcrumbs: [
        {
            title: 'Labs',
            href: labsRoute(),
        },
    ],
};
