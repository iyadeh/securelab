import { Link, usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';
import { show as showProfile } from '@/routes/profile';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="border-sidebar-border bg-background sticky top-0 z-30 flex h-(--app-header-height) shrink-0 items-center gap-3 border-b px-4 transition-[width,height] ease-linear">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <div className="min-w-0 overflow-hidden">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <Link
                href={showProfile()}
                prefetch
                aria-label={`Open learner profile for ${auth.user.name}`}
                className="hover:bg-accent/60 focus-visible:ring-ring flex h-10 shrink-0 items-center gap-2 rounded-md px-1.5 transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <span className="hidden min-w-0 text-right leading-tight sm:block">
                    <span className="block max-w-36 truncate text-xs font-medium lg:max-w-44">
                        {auth.user.name}
                    </span>
                    <span className="text-muted-foreground hidden max-w-44 truncate text-[11px] lg:block">
                        {auth.user.email}
                    </span>
                </span>
                <Avatar
                    aria-hidden="true"
                    className="border-primary/25 size-8 rounded-md border"
                >
                    <AvatarFallback className="bg-primary/10 text-primary rounded-md text-xs font-semibold">
                        {getInitials(auth.user.name)}
                    </AvatarFallback>
                </Avatar>
            </Link>
        </header>
    );
}
