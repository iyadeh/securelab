import { Link } from '@inertiajs/react';
import { Map } from 'lucide-react';
import { DashboardProgressBar } from '@/components/dashboard/progress-bar';
import { ProgressStateLabel } from '@/components/dashboard/progress-state-label';
import { DashboardSectionHeading } from '@/components/dashboard/section-heading';
import type { DashboardLearningPath } from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show as showLearningPath } from '@/routes/learning-paths';

export function LearningPathOverview({
    learningPaths,
}: {
    learningPaths: DashboardLearningPath[];
}) {
    return (
        <section
            aria-labelledby="dashboard-paths-heading"
            className="space-y-4"
        >
            <DashboardSectionHeading
                id="dashboard-paths-heading"
                title="Current learning path"
                description="See how each real curriculum module contributes to the path."
            />

            {learningPaths.length === 0 ? (
                <Card className="gap-0 rounded-lg py-0 shadow-none">
                    <CardContent className="flex min-h-36 items-start gap-3 px-5 py-6 sm:px-6">
                        <Map
                            aria-hidden="true"
                            className="text-muted-foreground mt-0.5 size-5 shrink-0"
                        />
                        <div className="space-y-1">
                            <h3 className="text-sm font-medium">
                                No learning paths available
                            </h3>
                            <p className="text-muted-foreground text-sm leading-6">
                                Curriculum progress will appear when a learning
                                path is available.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-4">
                    {learningPaths.map((learningPath) => (
                        <Card
                            key={learningPath.slug}
                            className="gap-0 overflow-hidden rounded-lg py-0 shadow-none"
                        >
                            <CardContent className="p-0">
                                <div className="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(15rem,0.55fr)_auto] lg:items-center">
                                    <div className="flex min-w-0 items-start gap-3">
                                        <CategoryIcon
                                            category="learning"
                                            size="md"
                                        />
                                        <div className="min-w-0 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold sm:text-lg">
                                                    {learningPath.title}
                                                </h3>
                                                <Badge
                                                    variant="outline"
                                                    className="text-muted-foreground capitalize"
                                                >
                                                    {learningPath.difficulty}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-xs">
                                                {learningPath.module_count}{' '}
                                                {learningPath.module_count === 1
                                                    ? 'module'
                                                    : 'modules'}{' '}
                                                · {learningPath.lesson_count}{' '}
                                                {learningPath.lesson_count === 1
                                                    ? 'lesson'
                                                    : 'lessons'}
                                            </p>
                                            <ProgressStateLabel
                                                state={learningPath.state}
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between gap-4 text-xs">
                                            <span className="text-muted-foreground">
                                                {learningPath.completed_lessons}{' '}
                                                of {learningPath.lesson_count}{' '}
                                                lessons complete
                                            </span>
                                            <span className="font-mono font-semibold tabular-nums">
                                                {
                                                    learningPath.completion_percentage
                                                }
                                                %
                                            </span>
                                        </div>
                                        <DashboardProgressBar
                                            label={`${learningPath.title} completion`}
                                            value={
                                                learningPath.completion_percentage
                                            }
                                        />
                                        {learningPath.next_lesson !== null && (
                                            <p className="text-muted-foreground text-xs">
                                                Next:{' '}
                                                <span className="text-foreground font-medium">
                                                    {
                                                        learningPath.next_lesson
                                                            .title
                                                    }
                                                </span>
                                            </p>
                                        )}
                                    </div>
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={showLearningPath(
                                                learningPath.slug,
                                            )}
                                            prefetch
                                        >
                                            View path
                                        </Link>
                                    </Button>
                                </div>

                                {learningPath.modules.length > 0 && (
                                    <ul className="bg-border grid gap-px border-t sm:grid-cols-2 lg:grid-cols-3">
                                        {learningPath.modules.map(
                                            (learningModule) => (
                                                <li
                                                    key={learningModule.title}
                                                    className="bg-card flex min-w-0 items-start gap-3 px-5 py-4"
                                                >
                                                    <CategoryIcon
                                                        category={
                                                            learningModule.title
                                                        }
                                                        size="sm"
                                                    />
                                                    <div className="min-w-0 space-y-1.5">
                                                        <p className="text-sm leading-5 font-medium">
                                                            {
                                                                learningModule.title
                                                            }
                                                        </p>
                                                        <p className="text-muted-foreground text-xs">
                                                            {
                                                                learningModule.completed_lessons
                                                            }{' '}
                                                            of{' '}
                                                            {
                                                                learningModule.lesson_count
                                                            }{' '}
                                                            lessons
                                                        </p>
                                                        <ProgressStateLabel
                                                            state={
                                                                learningModule.state
                                                            }
                                                        />
                                                    </div>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </section>
    );
}
