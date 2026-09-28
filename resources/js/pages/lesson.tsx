import { Form, Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    CheckCircle2,
    ChevronRight,
    Clock3,
    FlaskConical,
    Play,
} from 'lucide-react';
import { ProgressStateLabel } from '@/components/dashboard/progress-state-label';
import {
    LessonNavigation,
    type LessonNavigationItem,
} from '@/components/learning/lesson-navigation';
import {
    LessonRoadmap,
    type LessonRoadmapStage,
} from '@/components/learning/lesson-roadmap';
import { LessonSection } from '@/components/learning/lesson-section';
import { CategoryIcon } from '@/components/security/category-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { learn } from '@/routes';
import { show as showLab } from '@/routes/labs';
import { show as showLearningPath } from '@/routes/learning-paths';
import {
    complete as completeLesson,
    start as startLesson,
} from '@/routes/lessons';

type LessonState = 'not_started' | 'in_progress' | 'completed';

type LearningPathSummary = {
    slug: string;
    title: string;
};

type LearningModuleSummary = {
    title: string;
    position: number;
};

type LessonDetail = {
    slug: string;
    title: string;
    summary: string;
    content: string;
    estimated_minutes: number;
    position: number;
    state: LessonState;
};

type LabSummary = {
    slug: string;
    title: string;
    difficulty: 'easy' | 'medium' | 'hard';
    vulnerability_type: string;
};

type LessonPageProps = {
    learningPath: LearningPathSummary;
    module: LearningModuleSummary;
    lesson: LessonDetail;
    lab: LabSummary | null;
    previousLesson: LessonNavigationItem | null;
    nextLesson: LessonNavigationItem | null;
};

type LessonSectionData = LessonRoadmapStage & {
    paragraphs: string[];
};

const stageIds: Record<string, string> = {
    Learn: 'learn',
    Understand: 'understand',
    Exploit: 'exploit',
    Analyze: 'analyze',
    Fix: 'fix',
};

function parseLessonContent(content: string): LessonSectionData[] {
    const trimmedContent = content.trim();

    if (!trimmedContent.startsWith('## ')) {
        return [
            {
                id: 'lesson-content',
                title: 'Lesson content',
                paragraphs: trimmedContent
                    .split(/\r?\n\s*\r?\n/)
                    .filter(Boolean),
            },
        ];
    }

    return trimmedContent
        .split(/^##\s+/m)
        .filter(Boolean)
        .map((block, index) => {
            const [title, ...bodyLines] = block.trim().split(/\r?\n/);

            return {
                id: stageIds[title] ?? `lesson-section-${index + 1}`,
                title,
                paragraphs: bodyLines
                    .join('\n')
                    .trim()
                    .split(/\r?\n\s*\r?\n/)
                    .filter(Boolean),
            };
        });
}

function formatVulnerabilityType(vulnerabilityType: string): string {
    return vulnerabilityType.replaceAll('_', ' ');
}

export default function LessonPage({
    learningPath,
    module: learningModule,
    lesson,
    lab,
    previousLesson,
    nextLesson,
}: LessonPageProps) {
    const sections = parseLessonContent(lesson.content);

    return (
        <>
            <Head title={lesson.title} />
            <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <nav aria-label="Breadcrumb">
                    <ol className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-sm">
                        <li>
                            <Link
                                href={learn()}
                                prefetch
                                className="hover:text-foreground focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            >
                                Learning Paths
                            </Link>
                        </li>
                        <li aria-hidden="true">
                            <ChevronRight className="size-3.5" />
                        </li>
                        <li>
                            <Link
                                href={showLearningPath(learningPath.slug)}
                                prefetch
                                className="hover:text-foreground focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            >
                                {learningPath.title}
                            </Link>
                        </li>
                        <li aria-hidden="true">
                            <ChevronRight className="size-3.5" />
                        </li>
                        <li className="text-foreground" aria-current="page">
                            {lesson.title}
                        </li>
                    </ol>
                </nav>

                <article className="mx-auto w-full max-w-4xl">
                    <header className="border-b pb-8">
                        <div className="flex items-start gap-4">
                            <CategoryIcon
                                category={learningModule.title}
                                size="lg"
                            />
                            <div className="min-w-0 flex-1">
                                <p className="text-primary font-mono text-xs font-medium tracking-[0.14em] uppercase">
                                    Module {learningModule.position} · Lesson{' '}
                                    {lesson.position}
                                </p>
                                <p className="text-muted-foreground mt-2 text-sm font-medium">
                                    {learningModule.title}
                                </p>
                                <h1 className="mt-2 max-w-3xl text-3xl leading-tight font-semibold tracking-tight sm:text-4xl">
                                    {lesson.title}
                                </h1>
                                <p className="text-muted-foreground mt-4 max-w-3xl text-base leading-7 sm:text-lg">
                                    {lesson.summary}
                                </p>
                            </div>
                        </div>

                        <dl className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm">
                            <div className="inline-flex items-center gap-1.5">
                                <BookOpen
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                <dt className="sr-only">Learning path</dt>
                                <dd>{learningPath.title}</dd>
                            </div>
                            <div className="inline-flex items-center gap-1.5">
                                <Clock3 aria-hidden="true" className="size-4" />
                                <dt className="sr-only">Estimated duration</dt>
                                <dd>{lesson.estimated_minutes} min</dd>
                            </div>
                            <div className="inline-flex items-center gap-1.5">
                                <dt className="sr-only">Lesson state</dt>
                                <dd className="inline-flex flex-wrap items-center gap-1.5">
                                    <ProgressStateLabel state={lesson.state} />
                                    {lesson.state === 'completed' && (
                                        <span>· Available for review</span>
                                    )}
                                </dd>
                            </div>
                            {lab && (
                                <div className="inline-flex items-center gap-1.5">
                                    <FlaskConical
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    <dt className="sr-only">Guided lab</dt>
                                    <dd>Guided lab available</dd>
                                </div>
                            )}
                        </dl>
                    </header>

                    {lesson.state === 'not_started' && (
                        <section
                            aria-labelledby="start-lesson-heading"
                            className="mt-6"
                        >
                            <Card className="gap-0 rounded-lg py-0 shadow-none">
                                <CardContent className="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-1">
                                        <h2
                                            id="start-lesson-heading"
                                            className="text-sm font-medium"
                                        >
                                            Ready to begin?
                                        </h2>
                                        <p className="text-muted-foreground text-sm leading-5">
                                            Starting records progress without
                                            restricting lesson access.
                                        </p>
                                    </div>
                                    <Form
                                        {...startLesson.form({
                                            learningPath: learningPath.slug,
                                            lesson: lesson.slug,
                                        })}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                aria-busy={processing}
                                                className="w-full sm:w-auto"
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <Play aria-hidden="true" />
                                                )}
                                                {processing
                                                    ? 'Starting...'
                                                    : 'Start lesson'}
                                            </Button>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        </section>
                    )}

                    <section
                        aria-labelledby="lesson-roadmap-heading"
                        className="mt-8 space-y-3"
                    >
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <h2
                                id="lesson-roadmap-heading"
                                className="text-sm font-semibold"
                            >
                                Lesson roadmap
                            </h2>
                            <p className="text-muted-foreground text-xs">
                                Educational structure, not tracked progress
                            </p>
                        </div>
                        <LessonRoadmap
                            stages={sections.map(({ id, title }) => ({
                                id,
                                title,
                            }))}
                        />
                    </section>

                    <div className="mt-6">
                        {sections.map((section, index) => (
                            <LessonSection
                                key={section.id}
                                id={section.id}
                                position={index + 1}
                                title={section.title}
                                paragraphs={section.paragraphs}
                            />
                        ))}
                    </div>

                    {lab && (
                        <aside
                            aria-labelledby="guided-lab-heading"
                            className="mt-2"
                        >
                            <Card className="gap-0 rounded-lg py-0 shadow-none">
                                <CardContent className="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div className="flex min-w-0 items-start gap-4">
                                        <CategoryIcon
                                            category="labs"
                                            size="md"
                                        />
                                        <div className="min-w-0 space-y-2">
                                            <p className="text-primary text-xs font-medium">
                                                Practice this concept
                                            </p>
                                            <h2
                                                id="guided-lab-heading"
                                                className="text-lg font-semibold"
                                            >
                                                {lab.title}
                                            </h2>
                                            <p className="text-muted-foreground max-w-xl text-sm leading-6">
                                                Apply this lesson in
                                                SecureLab&apos;s safe,
                                                deterministic simulation.
                                            </p>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className="capitalize"
                                                >
                                                    {lab.difficulty}
                                                </Badge>
                                                <span className="text-muted-foreground font-mono text-xs capitalize">
                                                    {formatVulnerabilityType(
                                                        lab.vulnerability_type,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full shrink-0 sm:w-auto"
                                    >
                                        <Link href={showLab(lab.slug)} prefetch>
                                            View lab
                                            <FlaskConical aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        </aside>
                    )}

                    {lesson.state === 'in_progress' && (
                        <section
                            aria-labelledby="complete-lesson-heading"
                            className="mt-10 flex flex-col gap-5 border-t pt-8 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="space-y-1">
                                <h2
                                    id="complete-lesson-heading"
                                    className="text-base font-semibold"
                                >
                                    Finished reviewing this lesson?
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Completion is explicit and separate from
                                    next-lesson navigation.
                                </p>
                            </div>
                            <Form
                                {...completeLesson.form({
                                    learningPath: learningPath.slug,
                                    lesson: lesson.slug,
                                })}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        aria-busy={processing}
                                        className="w-full sm:w-auto"
                                    >
                                        {processing ? (
                                            <Spinner />
                                        ) : (
                                            <CheckCircle2 aria-hidden="true" />
                                        )}
                                        {processing
                                            ? 'Completing...'
                                            : 'Complete lesson'}
                                    </Button>
                                )}
                            </Form>
                        </section>
                    )}

                    {lesson.state === 'completed' && (
                        <section
                            aria-labelledby="lesson-completed-heading"
                            className="border-primary/25 bg-primary/5 mt-10 flex items-start gap-4 rounded-lg border p-5"
                        >
                            <CheckCircle2
                                aria-hidden="true"
                                className="text-primary mt-0.5 size-5 shrink-0"
                            />
                            <div className="space-y-1">
                                <h2
                                    id="lesson-completed-heading"
                                    className="text-sm font-semibold"
                                >
                                    Lesson completed
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Progress is saved. This lesson remains
                                    available for review.
                                </p>
                            </div>
                        </section>
                    )}

                    <div className="mt-10">
                        <LessonNavigation
                            learningPath={learningPath}
                            previousLesson={previousLesson}
                            nextLesson={nextLesson}
                        />
                    </div>
                </article>
            </main>
        </>
    );
}

LessonPage.layout = {
    breadcrumbs: [
        {
            title: 'Learning Paths',
            href: learn(),
        },
    ],
};
