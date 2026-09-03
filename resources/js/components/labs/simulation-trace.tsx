import {
    ArrowDown,
    Cog,
    FileInput,
    FileOutput,
    ShieldCheck,
    type LucideIcon,
} from 'lucide-react';

export type SimulationOutcome =
    | 'normal_behavior'
    | 'vulnerable_behavior'
    | 'rejected';

export type SimulationStepType =
    | 'input'
    | 'application'
    | 'decision'
    | 'output';

export type SimulationTraceStep = {
    key: string;
    type: SimulationStepType;
    label: string;
    description: string;
};

type SimulationTraceProps = {
    outcome: SimulationOutcome;
    steps: SimulationTraceStep[];
};

type StepPresentation = {
    icon: LucideIcon;
    label: string;
};

const stepPresentations: Record<SimulationStepType, StepPresentation> = {
    input: {
        icon: FileInput,
        label: 'Input',
    },
    application: {
        icon: Cog,
        label: 'Application handling',
    },
    decision: {
        icon: ShieldCheck,
        label: 'Security decision',
    },
    output: {
        icon: FileOutput,
        label: 'Simulated output',
    },
};

const decisionStyles: Record<SimulationOutcome, string> = {
    normal_behavior: 'border-primary/25 bg-primary/5',
    vulnerable_behavior:
        'border-amber-500/30 bg-amber-500/5 dark:border-amber-400/25',
    rejected: 'border-border bg-muted/30',
};

export function SimulationTrace({ outcome, steps }: SimulationTraceProps) {
    return (
        <ol aria-label="Simulation flow" className="space-y-0">
            {steps.map((step, index) => {
                const presentation = stepPresentations[step.type];
                const Icon = presentation.icon;
                const isLastStep = index === steps.length - 1;
                const isDecisionStep = step.type === 'decision';

                return (
                    <li
                        key={step.key}
                        className="grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 sm:grid-cols-[3rem_minmax(0,1fr)] sm:gap-x-4"
                    >
                        <div
                            aria-hidden="true"
                            className="flex min-h-full flex-col items-center"
                        >
                            <span className="bg-background text-muted-foreground flex size-9 shrink-0 items-center justify-center rounded-md border">
                                <Icon className="size-4" />
                            </span>
                            {!isLastStep && (
                                <span className="flex min-h-10 flex-1 flex-col items-center py-1.5">
                                    <span className="bg-border w-px flex-1" />
                                    <ArrowDown className="text-muted-foreground size-3.5 shrink-0" />
                                </span>
                            )}
                        </div>

                        <div
                            className={
                                isDecisionStep
                                    ? `mb-5 rounded-lg border p-4 ${decisionStyles[outcome]}`
                                    : isLastStep
                                      ? 'pt-1'
                                      : 'pt-1 pb-6'
                            }
                        >
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span className="text-muted-foreground font-mono text-[0.6875rem] font-medium tracking-wide uppercase tabular-nums">
                                    {String(index + 1).padStart(2, '0')} -{' '}
                                    {presentation.label}
                                </span>
                            </div>
                            <h4 className="mt-1 text-sm font-semibold">
                                {step.label}
                            </h4>
                            <p className="text-muted-foreground mt-1.5 text-sm leading-6 break-words">
                                {step.description}
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
