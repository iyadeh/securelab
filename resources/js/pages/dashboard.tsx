import { Head, Link, usePage } from '@inertiajs/react';
import { LearnerStats } from '@/components/dashboard/learner-stats';
import { LearningPathOverview } from '@/components/dashboard/learning-path-overview';
import { NextUpCard } from '@/components/dashboard/next-up-card';
import { PrimaryLearningAction } from '@/components/dashboard/primary-learning-action';
import { DashboardSectionHeading } from '@/components/dashboard/section-heading';
import type {
    DashboardAchievementSummary,
    DashboardLearningPath,
    DashboardProgressSummary,
    InProgressLab,
    InProgressLesson,
} from '@/components/dashboard/types';
import {
    achievements as achievementsRoute,
    dashboard,
    labs as labsRoute,
    learn as learnRoute,
    progress as progressRoute,
} from '@/routes';

type DashboardProps = {
    learningSummary: DashboardProgressSummary;
    labSummary: DashboardProgressSummary;
    achievementSummary: DashboardAchievementSummary;
    learningPaths: DashboardLearningPath[];
    inProgressLessons: InProgressLesson[];
    inProgressLabs: InProgressLab[];
};

export default function Dashboard({
    learningSummary,
    labSummary,
    achievementSummary,
    learningPaths,
    inProgressLessons,
    inProgressLabs,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.trim().split(/\s+/)[0] || auth.user.name;
    const primaryLesson = inProgressLessons[0] ?? null;
    const hasActiveWork =
        inProgressLessons.length > 0 || inProgressLabs.length > 0;
    const currentLearningPath =
        learningPaths.find(
            (learningPath) => learningPath.state === 'in_progress',
        ) ??
        learningPaths.find(
            (learningPath) => learningPath.state === 'not_started',
        ) ??
        learningPaths[0] ??
        null;
    const availableItemCount = learningSummary.total + labSummary.total;
    const journeyComplete =
        availableItemCount > 0 &&
        learningSummary.completed === learningSummary.total &&
        labSummary.completed === labSummary.total;
    const nextUpPath = hasActiveWork
        ? learningPaths.find((learningPath) => {
              if (
                  learningPath.next_lesson === null ||
                  journeyComplete ||
                  primaryLesson === null
              ) {
                  return learningPath.next_lesson !== null && !journeyComplete;
              }

              return (
                  primaryLesson.slug !== learningPath.next_lesson.slug ||
                  primaryLesson.learning_path.slug !== learningPath.slug
              );
          })
        : undefined;

    return (
        <>
            <Head title="Dashboard" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 sm:p-6 lg:p-8">
                <header className="max-w-4xl space-y-2">
                    <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                        Learner dashboard
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Welcome back, {firstName}.
                    </h1>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-6 sm:text-base">
                        Continue your SecureLab learning journey and keep the
                        next security concept in view.
                    </p>
                </header>

                <PrimaryLearningAction
                    learningSummary={learningSummary}
                    labSummary={labSummary}
                    learningPaths={learningPaths}
                    inProgressLessons={inProgressLessons}
                    inProgressLabs={inProgressLabs}
                />

                <section
                    aria-labelledby="dashboard-progress-heading"
                    className="space-y-4"
                >
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <DashboardSectionHeading
                            id="dashboard-progress-heading"
                            title="Your progress"
                            description="Real learning, lab, achievement, and path milestones."
                        />
                        <Link
                            href={progressRoute()}
                            prefetch
                            className="text-primary focus-visible:ring-ring inline-flex items-center gap-1.5 self-start text-sm font-medium underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:outline-none sm:self-auto"
                        >
                            View detailed progress
                        </Link>
                    </div>
                    <LearnerStats
                        learning={learningSummary}
                        labs={labSummary}
                        achievements={achievementSummary}
                        currentPath={
                            currentLearningPath === null
                                ? null
                                : {
                                      title: currentLearningPath.title,
                                      completed:
                                          currentLearningPath.completed_lessons,
                                      total: currentLearningPath.lesson_count,
                                      completion_percentage:
                                          currentLearningPath.completion_percentage,
                                  }
                        }
                    />
                </section>

                <LearningPathOverview learningPaths={learningPaths} />

                {nextUpPath !== undefined && (
                    <NextUpCard learningPath={nextUpPath} />
                )}

                <nav
                    aria-label="Dashboard shortcuts"
                    className="flex flex-wrap items-center gap-x-5 gap-y-3 border-t pt-6"
                >
                    <Link
                        href={learnRoute()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-sm text-sm font-medium underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:outline-none"
                    >
                        Learning paths
                    </Link>
                    <Link
                        href={labsRoute()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-sm text-sm font-medium underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:outline-none"
                    >
                        Browse labs
                    </Link>
                    <Link
                        href={achievementsRoute()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-sm text-sm font-medium underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:outline-none"
                    >
                        View achievements
                    </Link>
                </nav>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
