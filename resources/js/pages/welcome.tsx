import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ShieldCheck } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="bg-background text-foreground flex min-h-svh flex-col">
                <header className="border-border border-b">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                        <Link
                            href="/"
                            className="focus-visible:ring-ring flex items-center gap-2 rounded-md font-semibold focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <span className="border-primary/25 bg-primary/10 text-primary flex size-9 items-center justify-center rounded-md border">
                                <AppLogoIcon className="size-6" />
                            </span>
                            <span>SecureLab</span>
                        </Link>

                        <nav aria-label="Account navigation">
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href={dashboard()} prefetch>
                                        Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild size="sm">
                                        <Link href={register()}>
                                            Create account
                                        </Link>
                                    </Button>
                                </div>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-16 sm:px-6 sm:py-24">
                    <div className="max-w-3xl space-y-8">
                        <div className="border-border bg-card text-muted-foreground inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-xs font-medium">
                            <ShieldCheck className="text-primary size-4" />
                            Practical application security learning
                        </div>

                        <div className="space-y-4">
                            <h1 className="max-w-2xl text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">
                                Learn security by understanding how systems
                                fail.
                            </h1>
                            <p className="text-muted-foreground max-w-2xl text-base leading-7 sm:text-lg">
                                SecureLab is a focused environment for
                                structured lessons, safe labs, and measurable
                                application security practice.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()} prefetch>
                                        Open dashboard
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Create account
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </main>

                <footer className="border-border text-muted-foreground border-t px-4 py-5 text-center text-xs sm:px-6">
                    SecureLab · Security learning workspace
                </footer>
            </div>
        </>
    );
}
