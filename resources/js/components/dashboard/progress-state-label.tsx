import { CheckCircle2, Circle, CircleDot } from 'lucide-react';
import type { ProgressState } from '@/components/dashboard/types';

export function ProgressStateLabel({ state }: { state: ProgressState }) {
    if (state === 'completed') {
        return (
            <span className="text-primary inline-flex items-center gap-1.5 text-xs font-medium">
                <CheckCircle2 aria-hidden="true" className="size-3.5" />
                Completed
            </span>
        );
    }

    if (state === 'in_progress') {
        return (
            <span className="inline-flex items-center gap-1.5 text-xs font-medium">
                <CircleDot
                    aria-hidden="true"
                    className="text-primary size-3.5"
                />
                In progress
            </span>
        );
    }

    return (
        <span className="text-muted-foreground inline-flex items-center gap-1.5 text-xs font-medium">
            <Circle aria-hidden="true" className="size-3.5" />
            Not started
        </span>
    );
}
