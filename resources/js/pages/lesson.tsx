import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    Brain,
    CheckCircle2,
    ChevronRight,
    Circle,
    CircleDot,
    Clock3,
    FlaskConical,
    Microscope,
    Search,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { learn } from '@/routes';
import { show as showLearningPath } from '@/routes/learning-paths';
import { show as showLesson } from '@/routes/lessons';

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

type LessonNavigation = null | {
    slug: string;
    title: string;
};

type LessonPageProps = {
    learningPath: LearningPathSummary;
    module: LearningModuleSummary;
    lesson: LessonDetail;
    lab: LabSummary | null;
    previousLesson: LessonNavigation;
    nextLesson: LessonNavigation;
};

type LessonSection = {
    title: string;
    paragraphs: string[];
};

const sectionIcons: Record<string, LucideIcon> = {
    Learn: BookOpen,
    Understand: Brain,
    Exploit: Search,
    Analyze: Microscope,
    Fix: ShieldCheck,
};

function parseLessonContent(content: string): LessonSection[] {
    const trimmedContent = content.trim();

    if (!trimmedContent.startsWith('## ')) {
        return [
            {
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
        .map((block) => {
            const [title, ...bodyLines] = block.trim().split(/\r?\n/);

            return {
                title,
                paragraphs: bodyLines
                    .join('\n')
                    .trim()
                    .split(/\r?\n\s*\r?\n/)
                    .filter(Boolean),
            };
        });
}

function LessonStateLabel({ state }: { state: LessonState }) {
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

function CurriculumLink({
    direction,
    lesson,
    learningPath,
}: {
    direction: 'previous' | 'next';
    lesson: Exclude<LessonNavigation, null>;
    learningPath: LearningPathSummary;
}) {
    const isPrevious = direction === 'previous';

    return (
        <Link
            href={showLesson({
                learningPath: learningPath.slug,
                lesson: lesson.slug,
            })}
            prefetch
            className="hover:border-primary/50 hover:bg-muted/30 focus-visible:ring-ring group flex min-h-24 flex-col justify-center gap-2 rounded-lg border px-4 py-4 outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        >
            <span
                className={`text-muted-foreground flex items-center gap-1.5 text-xs font-medium ${
                    isPrevious ? '' : 'justify-end'
                }`}
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
                className={`group-hover:text-primary text-sm leading-5 font-medium ${
                    isPrevious ? '' : 'text-right'
                }`}
            >
                {lesson.title}
            </span>
        </Link>
    );
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

                <article className="mx-auto w-full max-w-3xl">
                    <header className="border-b pb-8">
                        <p className="text-primary font-mono text-xs font-medium tracking-wide uppercase">
                            Module {learningModule.position} / Lesson{' '}
                            {lesson.position}
                        </p>
                        <h1 className="mt-3 text-3xl leading-tight font-semibold tracking-tight sm:text-4xl">
                            {lesson.title}
                        </h1>
                        <p className="text-muted-foreground mt-4 max-w-2xl text-base leading-7 sm:text-lg">
                            {lesson.summary}
                        </p>
                        <div className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm">
                            <span className="inline-flex items-center gap-1.5">
                                <BookOpen
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {learningModule.title}
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <Clock3 aria-hidden="true" className="size-4" />
                                {lesson.estimated_minutes} min
                            </span>
                            <LessonStateLabel state={lesson.state} />
                            {lab && (
                                <span className="inline-flex items-center gap-1.5">
                                    <FlaskConical
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    Guided lab included
                                </span>
                            )}
                        </div>
                    </header>

                    <div className="divide-y">
                        {sections.map((section, index) => {
                            const SectionIcon =
                                sectionIcons[section.title] ?? BookOpen;

                            return (
                                <section
                                    key={`${section.title}-${index}`}
                                    aria-labelledby={`lesson-section-${index}`}
                                    className="grid gap-5 py-8 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:py-10"
                                >
                                    <div className="flex items-center gap-3 sm:block">
                                        <span className="bg-muted flex size-9 items-center justify-center rounded-md border font-mono text-xs font-semibold tabular-nums">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <SectionIcon
                                            aria-hidden="true"
                                            className="text-muted-foreground mt-3 hidden size-4 sm:block"
                                        />
                                    </div>
                                    <div>
                                        <h2
                                            id={`lesson-section-${index}`}
                                            className="text-xl font-semibold tracking-tight"
                                        >
                                            {section.title}
                                        </h2>
                                        <div className="text-foreground/90 mt-4 space-y-4 text-base leading-7">
                                            {section.paragraphs.map(
                                                (paragraph, paragraphIndex) => (
                                                    <p
                                                        key={`${section.title}-${paragraphIndex}`}
                                                    >
                                                        {paragraph}
                                                    </p>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                </section>
                            );
                        })}
                    </div>

                    {lab && (
                        <aside
                            aria-labelledby="guided-lab-heading"
                            className="bg-muted/25 mt-2 rounded-lg border p-5 sm:p-6"
                        >
                            <div className="flex items-start gap-4">
                                <div className="bg-background flex size-10 shrink-0 items-center justify-center rounded-md border">
                                    <FlaskConical
                                        aria-hidden="true"
                                        className="text-primary size-5"
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-primary text-xs font-medium">
                                        Guided lab available
                                    </p>
                                    <h2
                                        id="guided-lab-heading"
                                        className="mt-1 text-base font-semibold"
                                    >
                                        {lab.title}
                                    </h2>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                                        Practice this lesson in a safe simulated
                                        environment. Lab access arrives in a
                                        later milestone.
                                    </p>
                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            className="capitalize"
                                        >
                                            {lab.difficulty}
                                        </Badge>
                                        <span className="text-muted-foreground font-mono text-xs">
                                            {lab.vulnerability_type.replaceAll(
                                                '_',
                                                ' ',
                                            )}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    )}

                    {(previousLesson || nextLesson) && (
                        <nav
                            aria-label="Curriculum navigation"
                            className="mt-10 grid gap-3 border-t pt-8 sm:grid-cols-2"
                        >
                            {previousLesson ? (
                                <CurriculumLink
                                    direction="previous"
                                    lesson={previousLesson}
                                    learningPath={learningPath}
                                />
                            ) : (
                                <div aria-hidden="true" />
                            )}
                            {nextLesson && (
                                <CurriculumLink
                                    direction="next"
                                    lesson={nextLesson}
                                    learningPath={learningPath}
                                />
                            )}
                        </nav>
                    )}
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
