import { Head } from '@inertiajs/react';
import { Map } from 'lucide-react';
import { PlannedPage } from '@/components/planned-page';
import { learn } from '@/routes';

export default function LearningPaths() {
    return (
        <>
            <Head title="Learning Paths" />
            <PlannedPage
                title="Learning Paths"
                description="Structured security learning paths will be introduced in a later milestone."
                icon={Map}
            />
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
