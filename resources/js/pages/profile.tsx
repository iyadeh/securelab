import { Head } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import { PlannedPage } from '@/components/planned-page';
import { show } from '@/routes/profile';

export default function Profile() {
    return (
        <>
            <Head title="Profile" />
            <PlannedPage
                title="Profile"
                description="The learner profile overview will be introduced in a later milestone."
                icon={UserRound}
            />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile',
            href: show(),
        },
    ],
};
