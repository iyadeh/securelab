import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type PlannedPageProps = {
    title: string;
    description: string;
    icon: LucideIcon;
};

export function PlannedPage({
    title,
    description,
    icon: Icon,
}: PlannedPageProps) {
    return (
        <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header className="max-w-2xl space-y-2">
                <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                    Planned area
                </p>
                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                    {title}
                </h1>
                <p className="text-muted-foreground text-sm leading-6 sm:text-base">
                    {description}
                </p>
            </header>

            <Card className="rounded-lg shadow-none">
                <CardContent className="flex min-h-56 flex-col items-start justify-center gap-4">
                    <div className="border-primary/25 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border">
                        <Icon className="size-5" />
                    </div>
                    <div className="max-w-lg space-y-1.5">
                        <h2 className="font-medium">Not available yet</h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            This navigation destination is reserved for a later
                            SecureLab milestone.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </main>
    );
}
