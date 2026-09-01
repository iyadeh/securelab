import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpenCheck, CheckCircle2, CircleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, learn } from '@/routes';

export default function Dashboard() {
    const { auth } = usePage().props;
    const firstName = auth.user.name.trim().split(/\s+/)[0] || auth.user.name;

    return (
        <>
            <Head title="Dashboard" />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <header className="max-w-2xl space-y-2">
                    <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                        Overview
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Welcome back, {firstName}.
                    </h1>
                    <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                        Your SecureLab workspace is ready. Learning activity
                        will appear here as you complete published content.
                    </p>
                </header>

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.65fr)]">
                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardHeader className="border-b py-5">
                            <CardTitle className="text-base">
                                Learning activity
                            </CardTitle>
                            <CardDescription>
                                Completed lessons and lab activity will appear
                                here.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex min-h-56 flex-col items-start justify-center gap-4 py-6">
                            <div className="border-primary/25 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border">
                                <BookOpenCheck className="size-5" />
                            </div>
                            <div className="max-w-md space-y-1.5">
                                <h2 className="font-medium">
                                    No learning activity yet
                                </h2>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Learning paths and labs will populate this
                                    space when content is available.
                                </p>
                            </div>
                            <Button asChild variant="outline" size="sm">
                                <Link href={learn()} prefetch>
                                    View learning paths
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-lg py-0 shadow-none">
                        <CardHeader className="border-b py-5">
                            <CardTitle className="text-base">Account</CardTitle>
                            <CardDescription>
                                Current authenticated identity.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5 py-5">
                            <div className="space-y-1">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                    Name
                                </p>
                                <p className="text-sm font-medium">
                                    {auth.user.name}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                    Email
                                </p>
                                <p className="font-mono text-xs break-all">
                                    {auth.user.email}
                                </p>
                            </div>
                            {auth.user.email_verified_at ? (
                                <div className="flex items-center gap-2 border-t pt-4 text-sm">
                                    <CheckCircle2 className="text-primary size-4" />
                                    <span>Email verified</span>
                                </div>
                            ) : (
                                <div className="text-muted-foreground flex items-center gap-2 border-t pt-4 text-sm">
                                    <CircleAlert className="size-4" />
                                    <span>Email verification pending</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
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
