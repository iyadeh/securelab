import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CheckCircle2,
    ChevronRight,
    Circle,
    CircleDot,
    ShieldCheck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { labs as labsRoute } from '@/routes';
import { workspace as workspaceLab } from '@/routes/labs';
import { show as showLesson } from '@/routes/lessons';

type LabState = 'not_started' | 'in_progress' | 'completed';

type LabDifficulty = 'easy' | 'medium' | 'hard';

type LabDetail = {
    slug: string;
    title: string;
    description: string;
    vulnerability_type: string;
    difficulty: LabDifficulty;
    scenario: string;
    state: LabState;
};

type RelatedLesson = {
    slug: string;
    title: string;
    summary: string;
    module: {
        title: string;
    };
    learning_path: {
        slug: string;
        title: string;
    };
};

type LabDetailPageProps = {
    lab: LabDetail;
    lesson: RelatedLesson | null;
};

function formatVulnerabilityType(vulnerabilityType: string): string {
    return vulnerabilityType.replaceAll('_', ' ');
}

function LabStateLabel({ state }: { state: LabState }) {
    if (state === 'completed') {
        return (
            <span className="text-primary inline-flex items-center gap-1.5 text-sm font-medium">
                <CheckCircle2 aria-hidden="true" className="size-4" />
                Completed
            </span>
        );
    }

    if (state === 'in_progress') {
        return (
            <span className="inline-flex items-center gap-1.5 text-sm font-medium">
                <CircleDot aria-hidden="true" className="text-primary size-4" />
                In progress
            </span>
        );
    }

    return (
        <span className="text-muted-foreground inline-flex items-center gap-1.5 text-sm font-medium">
            <Circle aria-hidden="true" className="size-4" />
            Not started
        </span>
    );
}

export default function LabDetailPage({ lab, lesson }: LabDetailPageProps) {
    return (
        <>
            <Head title={lab.title} />
            <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <nav aria-label="Breadcrumb">
                    <ol className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-sm">
                        <li>
                            <Link
                                href={labsRoute()}
                                prefetch
                                className="hover:text-foreground focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            >
                                Labs
                            </Link>
                        </li>
                        <li aria-hidden="true">
                            <ChevronRight className="size-3.5" />
                        </li>
                        <li className="text-foreground" aria-current="page">
                            {lab.title}
                        </li>
                    </ol>
                </nav>

                <article>
                    <header className="max-w-3xl border-b pb-8">
                        <p className="text-primary font-mono text-xs font-medium tracking-wide uppercase">
                            {formatVulnerabilityType(lab.vulnerability_type)}
                        </p>
                        <h1 className="mt-3 text-3xl leading-tight font-semibold tracking-tight sm:text-4xl">
                            {lab.title}
                        </h1>
                        <p className="text-muted-foreground mt-4 text-base leading-7 sm:text-lg">
                            {lab.description}
                        </p>
                        <div className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm">
                            <span className="inline-flex items-center gap-2">
                                Difficulty
                                <Badge variant="outline" className="capitalize">
                                    {lab.difficulty}
                                </Badge>
                            </span>
                            <LabStateLabel state={lab.state} />
                        </div>
                    </header>

                    <div className="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
                        <section aria-labelledby="lab-scenario-heading">
                            <Card className="gap-0 rounded-lg py-0 shadow-none">
                                <CardContent className="px-5 py-6 sm:px-7 sm:py-8">
                                    <h2
                                        id="lab-scenario-heading"
                                        className="text-xl font-semibold tracking-tight"
                                    >
                                        Scenario
                                    </h2>
                                    <div className="mt-5 border-t pt-5">
                                        <p className="text-foreground/90 text-base leading-7 break-words whitespace-pre-wrap">
                                            {lab.scenario}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </section>

                        <div className="grid gap-4">
                            <section aria-labelledby="related-learning-heading">
                                <Card className="gap-0 rounded-lg py-0 shadow-none">
                                    <CardContent className="px-5 py-5">
                                        <div className="flex items-start gap-3">
                                            <BookOpen
                                                aria-hidden="true"
                                                className="text-primary mt-0.5 size-4 shrink-0"
                                            />
                                            <div className="min-w-0">
                                                <h2
                                                    id="related-learning-heading"
                                                    className="text-sm font-semibold"
                                                >
                                                    Related learning
                                                </h2>
                                                {lesson ? (
                                                    <div className="mt-3 space-y-2">
                                                        <Link
                                                            href={showLesson({
                                                                learningPath:
                                                                    lesson
                                                                        .learning_path
                                                                        .slug,
                                                                lesson: lesson.slug,
                                                            })}
                                                            prefetch
                                                            className="hover:text-primary focus-visible:ring-ring rounded-sm text-sm leading-5 font-medium outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                                        >
                                                            {lesson.title}
                                                        </Link>
                                                        <p className="text-muted-foreground text-xs leading-5">
                                                            {
                                                                lesson.module
                                                                    .title
                                                            }{' '}
                                                            &middot;{' '}
                                                            {
                                                                lesson
                                                                    .learning_path
                                                                    .title
                                                            }
                                                        </p>
                                                        <p className="text-muted-foreground text-sm leading-6">
                                                            {lesson.summary}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <p className="text-muted-foreground mt-3 text-sm leading-6">
                                                        This lab is available
                                                        independently and has no
                                                        related lesson.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </section>

                            <aside
                                aria-labelledby="lab-preparation-heading"
                                className="bg-muted/25 rounded-lg border p-5"
                            >
                                <div className="flex items-start gap-3">
                                    <ShieldCheck
                                        aria-hidden="true"
                                        className="text-primary mt-0.5 size-4 shrink-0"
                                    />
                                    <div>
                                        <h2
                                            id="lab-preparation-heading"
                                            className="text-sm font-semibold"
                                        >
                                            Before the guided exercise
                                        </h2>
                                        <p className="text-muted-foreground mt-2 text-sm leading-6">
                                            Review the scenario and related
                                            lesson before entering the guided
                                            exercise.
                                        </p>
                                        <Button
                                            asChild
                                            size="sm"
                                            className="mt-4"
                                        >
                                            <Link href={workspaceLab(lab.slug)}>
                                                Open workspace
                                                <ArrowRight aria-hidden="true" />
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </article>
            </main>
        </>
    );
}

LabDetailPage.layout = {
    breadcrumbs: [
        {
            title: 'Labs',
            href: labsRoute(),
        },
    ],
};
