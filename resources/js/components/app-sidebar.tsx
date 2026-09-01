import { Link } from '@inertiajs/react';
import {
    Award,
    ChartNoAxesColumnIncreasing,
    FlaskConical,
    Gauge,
    Map,
    Settings2,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    achievements,
    dashboard,
    labs,
    learn,
    progress,
    settings,
} from '@/routes';
import { show as showProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

type NavigationGroup = {
    label: string;
    items: NavItem[];
};

const navigationGroups: NavigationGroup[] = [
    {
        label: 'Overview',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: Gauge,
            },
        ],
    },
    {
        label: 'Learn',
        items: [
            {
                title: 'Learning Paths',
                href: learn(),
                icon: Map,
            },
            {
                title: 'Labs',
                href: labs(),
                icon: FlaskConical,
            },
        ],
    },
    {
        label: 'You',
        items: [
            {
                title: 'Progress',
                href: progress(),
                icon: ChartNoAxesColumnIncreasing,
            },
            {
                title: 'Achievements',
                href: achievements(),
                icon: Award,
            },
            {
                title: 'Profile',
                href: showProfile(),
                icon: UserRound,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: settings(),
        icon: Settings2,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="border-sidebar-border border-b p-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="py-2">
                {navigationGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter className="border-sidebar-border border-t p-2">
                <NavFooter items={footerNavItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
