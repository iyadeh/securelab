import { Head } from '@inertiajs/react';
import { FlaskConical } from 'lucide-react';
import { PlannedPage } from '@/components/planned-page';
import { labs } from '@/routes';

export default function Labs() {
    return (
        <>
            <Head title="Labs" />
            <PlannedPage
                title="Labs"
                description="The safe lab catalog and workspaces will be introduced in later milestones."
                icon={FlaskConical}
            />
        </>
    );
}

Labs.layout = {
    breadcrumbs: [
        {
            title: 'Labs',
            href: labs(),
        },
    ],
};
