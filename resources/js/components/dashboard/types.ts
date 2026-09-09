export type ProgressState = 'not_started' | 'in_progress' | 'completed';

export type DashboardProgressSummary = {
    total: number;
    in_progress: number;
    completed: number;
    remaining: number;
    completion_percentage: number;
};

export type DashboardAchievementSummary = {
    total: number;
    unlocked: number;
    locked: number;
    latest_unlocked: null | {
        slug: string;
        title: string;
    };
};

export type DashboardModule = {
    title: string;
    lesson_count: number;
    completed_lessons: number;
    in_progress_lessons: number;
    completion_percentage: number;
    state: ProgressState;
};

export type DashboardLearningPath = {
    slug: string;
    title: string;
    difficulty: string;
    module_count: number;
    lesson_count: number;
    completed_lessons: number;
    in_progress_lessons: number;
    completion_percentage: number;
    state: ProgressState;
    next_lesson: null | {
        slug: string;
        title: string;
        summary: string;
        module_title: string | null;
    };
    modules: DashboardModule[];
};

export type InProgressLesson = {
    slug: string;
    title: string;
    summary: string;
    module_title: string;
    learning_path: {
        slug: string;
        title: string;
        completed_lessons: number;
        lesson_count: number;
        completion_percentage: number;
    };
};

export type InProgressLab = {
    slug: string;
    title: string;
    vulnerability_type: string;
};
