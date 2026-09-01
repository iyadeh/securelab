import { Head } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { PlannedPage } from '@/components/planned-page';
import { achievements } from '@/routes';

export default function Achievements() {
    return (
        <>
            <Head title="Achievements" />
            <PlannedPage
                title="Achievements"
                description="Earned achievements will appear here after achievement logic is implemented."
                icon={Award}
            />
        </>
    );
}

Achievements.layout = {
    breadcrumbs: [
        {
            title: 'Achievements',
            href: achievements(),
        },
    ],
};
