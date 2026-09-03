import {
    CircleAlert,
    ShieldCheck,
    Wrench,
    type LucideIcon,
} from 'lucide-react';
import type { SimulationOutcome } from '@/components/labs/simulation-trace';

export type LabGuidance = {
    security_control: string;
    root_cause: string;
    impact: string;
    remediation: string[];
    secure_behavior: string;
    patterns: {
        vulnerable: string;
        secure: string;
    } | null;
};

type SimulationAnalysisProps = {
    guidance: LabGuidance;
    outcome: SimulationOutcome;
};

type OutcomeContext = {
    title: string;
    description: string;
    className: string;
};

type PhaseHeadingProps = {
    id: string;
    title: string;
    description: string;
    icon: LucideIcon;
};

const outcomeContexts: Record<SimulationOutcome, OutcomeContext> = {
    normal_behavior: {
        title: 'Normal behavior observed',
        description:
            'This input followed the ordinary modeled path. The guidance below explains the lab-level risk; this run did not demonstrate its insecure condition.',
        className: 'border-primary/30 bg-primary/5',
    },
    vulnerable_behavior: {
        title: 'Vulnerable behavior observed',
        description:
            'This run modeled an insecure outcome. Connect the security decision in the trace with the missing or failed control below.',
        className:
            'border-amber-500/30 bg-amber-500/5 dark:border-amber-400/25',
    },
    rejected: {
        title: 'Synthetic request rejected',
        description:
            'This request was rejected or unresolved. The guidance still explains the control studied by this lab; rejection is not a learner failure.',
        className: 'border-border bg-muted/30',
    },
};

function PhaseHeading({
    id,
    title,
    description,
    icon: Icon,
}: PhaseHeadingProps) {
    return (
        <div className="flex items-start gap-3">
            <span className="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-md border">
                <Icon aria-hidden="true" className="size-4" />
            </span>
            <div className="space-y-1">
                <h3 id={id} className="text-sm font-semibold">
                    {title}
                </h3>
                <p className="text-muted-foreground text-xs leading-5">
                    {description}
                </p>
            </div>
        </div>
    );
}

export function SimulationAnalysis({
    guidance,
    outcome,
}: SimulationAnalysisProps) {
    const outcomeContext = outcomeContexts[outcome];

    return (
        <div className="space-y-8 border-t pt-7">
            <section aria-labelledby="analysis-heading">
                <PhaseHeading
                    id="analysis-heading"
                    title="Analyze"
                    description="Identify the control, root cause, and impact behind the modeled behavior."
                    icon={CircleAlert}
                />

                <div className="mt-5 space-y-5">
                    <div
                        className={`rounded-lg border p-4 ${outcomeContext.className}`}
                    >
                        <p className="text-sm font-semibold">
                            {outcomeContext.title}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs leading-5">
                            {outcomeContext.description}
                        </p>
                    </div>

                    <dl className="grid gap-5 sm:grid-cols-2">
                        <div className="border-primary/50 border-l-2 pl-4 sm:col-span-2">
                            <dt className="text-muted-foreground font-mono text-[0.6875rem] font-medium tracking-wide uppercase">
                                Security control
                            </dt>
                            <dd className="mt-1.5 text-sm font-semibold">
                                {guidance.security_control}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">
                                Root cause
                            </dt>
                            <dd className="text-muted-foreground mt-1.5 text-sm leading-6">
                                {guidance.root_cause}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">Impact</dt>
                            <dd className="text-muted-foreground mt-1.5 text-sm leading-6">
                                {guidance.impact}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section
                aria-labelledby="remediation-heading"
                className="border-t pt-7"
            >
                <PhaseHeading
                    id="remediation-heading"
                    title="Fix"
                    description="Apply defensive controls at the boundary that accepted or rendered untrusted data."
                    icon={Wrench}
                />

                <div className="mt-5 space-y-6">
                    <div>
                        <h4 className="text-sm font-semibold">
                            Defensive changes
                        </h4>
                        <ul className="text-muted-foreground mt-3 space-y-2 text-sm leading-6">
                            {guidance.remediation.map((item) => (
                                <li
                                    key={item}
                                    className="grid grid-cols-[0.75rem_minmax(0,1fr)] gap-2.5"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="bg-primary mt-2.5 size-1 rounded-full"
                                    />
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {guidance.patterns && (
                        <div className="space-y-3">
                            <div>
                                <h4 className="text-sm font-semibold">
                                    Implementation pattern
                                </h4>
                                <p className="text-muted-foreground mt-1 text-xs leading-5">
                                    Trusted conceptual pseudocode. Displayed as
                                    text and never executed.
                                </p>
                            </div>
                            <div className="bg-border grid gap-px overflow-hidden rounded-lg border md:grid-cols-2">
                                <figure className="bg-background min-w-0 p-4">
                                    <figcaption className="text-muted-foreground font-mono text-[0.6875rem] font-medium tracking-wide uppercase">
                                        Vulnerable pattern
                                    </figcaption>
                                    <pre className="bg-muted/40 mt-3 max-w-full overflow-x-auto rounded-md border p-3 text-xs leading-5 whitespace-pre-wrap">
                                        <code>
                                            {guidance.patterns.vulnerable}
                                        </code>
                                    </pre>
                                </figure>
                                <figure className="bg-background min-w-0 p-4">
                                    <figcaption className="text-primary font-mono text-[0.6875rem] font-medium tracking-wide uppercase">
                                        Safer pattern
                                    </figcaption>
                                    <pre className="bg-primary/5 border-primary/20 mt-3 max-w-full overflow-x-auto rounded-md border p-3 text-xs leading-5 whitespace-pre-wrap">
                                        <code>{guidance.patterns.secure}</code>
                                    </pre>
                                </figure>
                            </div>
                        </div>
                    )}
                </div>
            </section>

            <section
                aria-labelledby="validation-heading"
                className="border-t pt-7"
            >
                <PhaseHeading
                    id="validation-heading"
                    title="Validate"
                    description="Compare the simulated result with the behavior a protected implementation should enforce."
                    icon={ShieldCheck}
                />
                <div className="border-primary/25 bg-primary/5 mt-5 rounded-lg border p-4">
                    <h4 className="text-sm font-semibold">
                        Expected protected behavior
                    </h4>
                    <p className="text-muted-foreground mt-1.5 text-sm leading-6">
                        {guidance.secure_behavior}
                    </p>
                </div>
            </section>
        </div>
    );
}
