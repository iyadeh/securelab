import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { DashboardSectionHeading } from '@/components/dashboard/section-heading';
import type { DashboardLearningPath } from '@/components/dashboard/types';
import { CategoryIcon } from '@/components/security/category-icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show as showLesson } from '@/routes/lessons';

export function NextUpCard({
    learningPath,
}: {
    learningPath: DashboardLearningPath;
}) {
    const nextLesson = learningPath.next_lesson;

    if (nextLesson === null) {
        return null;
    }

    return (
        <section aria-labelledby="dashboard-next-heading" className="space-y-4">
            <DashboardSectionHeading
                id="dashboard-next-heading"
                title="Next up"
                description="Keep this lesson visible while your active work stays primary."
            />
            <Card className="gap-0 rounded-lg py-0 shadow-none">
                <CardContent className="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div className="flex min-w-0 items-start gap-3">
                        <CategoryIcon
                            category={nextLesson.module_title ?? 'learning'}
                            size="md"
                        />
                        <div className="min-w-0 space-y-1">
                            <p className="text-muted-foreground text-xs">
                                {learningPath.title}
                                {nextLesson.module_title !== null && (
                                    <> · {nextLesson.module_title}</>
                                )}
                            </p>
                            <h3 className="font-semibold">
                                {nextLesson.title}
                            </h3>
                            <p className="text-muted-foreground max-w-2xl text-sm leading-6">
                                {nextLesson.summary}
                            </p>
                        </div>
                    </div>
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={showLesson({
                                learningPath: learningPath.slug,
                                lesson: nextLesson.slug,
                            })}
                            prefetch
                        >
                            Open lesson
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </section>
    );
}
