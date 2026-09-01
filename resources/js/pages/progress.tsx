import { Head } from '@inertiajs/react';
import { ChartNoAxesColumnIncreasing } from 'lucide-react';
import { PlannedPage } from '@/components/planned-page';
import { progress } from '@/routes';

export default function Progress() {
    return (
        <>
            <Head title="Progress" />
            <PlannedPage
                title="Progress"
                description="Verified learning progress will appear here once progress tracking is implemented."
                icon={ChartNoAxesColumnIncreasing}
            />
        </>
    );
}

Progress.layout = {
    breadcrumbs: [
        {
            title: 'Progress',
            href: progress(),
        },
    ],
};
