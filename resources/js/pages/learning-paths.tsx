import { Head } from '@inertiajs/react';
import { Map } from 'lucide-react';
import {
    LearningPathCard,
    type LearningPathOverview,
} from '@/components/learning/learning-path-card';
import { Card, CardContent } from '@/components/ui/card';
import { learn } from '@/routes';

type LearningPathsProps = {
    learningPaths: LearningPathOverview[];
};

export default function LearningPaths({ learningPaths }: LearningPathsProps) {
    return (
        <>
            <Head title="Learning Paths" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-3xl space-y-2">
                    <p className="text-primary font-mono text-xs font-medium tracking-[0.14em] uppercase">
                        Guided curriculum
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Learning Paths
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Build practical web security understanding through an
                        ordered curriculum of concepts, analysis, and guided
                        labs.
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
                        <div className="mb-4 flex items-end justify-between gap-4">
                            <div className="space-y-1">
                                <h2
                                    id="available-learning-paths"
                                    className="text-lg font-semibold tracking-tight"
                                >
                                    Available curriculum
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Continue from your first incomplete lesson.
                                </p>
                            </div>
                            <p className="text-muted-foreground hidden font-mono text-xs sm:block">
                                {learningPaths.length}{' '}
                                {learningPaths.length === 1 ? 'path' : 'paths'}
                            </p>
                        </div>
                        <div className="grid gap-4">
                            {learningPaths.map((learningPath) => (
                                <LearningPathCard
                                    key={learningPath.slug}
                                    learningPath={learningPath}
                                />
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
