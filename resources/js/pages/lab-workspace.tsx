import { Form, Head, Link } from '@inertiajs/react';
import {
    CircleSlash2,
    CheckCircle2,
    ChevronRight,
    Circle,
    CircleDot,
    FlaskConical,
    ShieldCheck,
    TriangleAlert,
    type LucideIcon,
} from 'lucide-react';
import {
    SimulationTrace,
    type SimulationOutcome,
    type SimulationTraceStep,
} from '@/components/labs/simulation-trace';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { labs as labsRoute } from '@/routes';
import { show as showLab, simulate as simulateLab } from '@/routes/labs';

type LabState = 'not_started' | 'in_progress' | 'completed';

type LabDifficulty = 'easy' | 'medium' | 'hard';

type WorkspaceLab = {
    slug: string;
    title: string;
    description: string;
    vulnerability_type: string;
    difficulty: LabDifficulty;
    state: LabState;
};

type SimulationField = {
    key: string;
    label: string;
    type: 'text' | 'password';
    placeholder: string;
    max_length: number;
};

type SimulationResult = {
    outcome: SimulationOutcome;
    summary: string;
    input: Record<string, string>;
    steps: SimulationTraceStep[];
};

type SimulationDefinition = {
    supported: boolean;
    instructions: string | null;
    fields: SimulationField[];
    result: SimulationResult | null;
};

type LabWorkspacePageProps = {
    lab: WorkspaceLab;
    simulation: SimulationDefinition;
};

type SimulationFormData = {
    input: Record<string, string>;
    simulation?: string;
};

type OutcomePresentation = {
    label: string;
    context: string;
    icon: LucideIcon;
    containerClassName: string;
    iconClassName: string;
};

const outcomePresentations: Record<SimulationOutcome, OutcomePresentation> = {
    normal_behavior: {
        label: 'Normal behavior',
        context:
            'The fictional application followed the expected modeled behavior.',
        icon: CheckCircle2,
        containerClassName: 'border-primary/25 bg-primary/5',
        iconClassName: 'text-primary',
    },
    vulnerable_behavior: {
        label: 'Vulnerable behavior modeled',
        context:
            'The fictional application would allow an insecure outcome. This result does not complete the lab.',
        icon: TriangleAlert,
        containerClassName:
            'border-amber-500/30 bg-amber-500/5 dark:border-amber-400/25',
        iconClassName: 'text-amber-600 dark:text-amber-400',
    },
    rejected: {
        label: 'Request rejected',
        context:
            'The synthetic request was rejected or did not reach the modeled condition. This is not a learner failure.',
        icon: CircleSlash2,
        containerClassName: 'border-border bg-muted/30',
        iconClassName: 'text-muted-foreground',
    },
};

function formatVulnerabilityType(vulnerabilityType: string): string {
    return vulnerabilityType.replaceAll('_', ' ');
}

function formatInputKey(key: string): string {
    return key
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function LabStateLabel({ state }: { state: LabState }) {
    if (state === 'completed') {
        return (
            <span className="text-primary inline-flex items-center gap-1.5 text-sm font-medium">
                <CheckCircle2 aria-hidden="true" className="size-4" />
                Completed
            </span>
        );
    }

    if (state === 'in_progress') {
        return (
            <span className="inline-flex items-center gap-1.5 text-sm font-medium">
                <CircleDot aria-hidden="true" className="text-primary size-4" />
                In progress
            </span>
        );
    }

    return (
        <span className="text-muted-foreground inline-flex items-center gap-1.5 text-sm font-medium">
            <Circle aria-hidden="true" className="size-4" />
            Not started
        </span>
    );
}

function OutcomeSummary({ result }: { result: SimulationResult }) {
    const presentation = outcomePresentations[result.outcome];
    const Icon = presentation.icon;

    return (
        <div
            className={`rounded-lg border p-4 ${presentation.containerClassName}`}
        >
            <div className="flex items-start gap-3">
                <Icon
                    aria-hidden="true"
                    className={`mt-0.5 size-4 shrink-0 ${presentation.iconClassName}`}
                />
                <div className="min-w-0 space-y-2">
                    <div>
                        <p className="font-mono text-[0.6875rem] font-medium tracking-wide uppercase">
                            Simulated outcome
                        </p>
                        <h3 className="mt-1 text-sm font-semibold">
                            {presentation.label}
                        </h3>
                    </div>
                    <p className="text-sm leading-6">{result.summary}</p>
                    <p className="text-muted-foreground text-xs leading-5">
                        {presentation.context}
                    </p>
                </div>
            </div>
        </div>
    );
}

export default function LabWorkspacePage({
    lab,
    simulation,
}: LabWorkspacePageProps) {
    return (
        <>
            <Head title={`${lab.title} Workspace`} />
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-7 p-4 sm:p-6 lg:p-8">
                <nav aria-label="Breadcrumb">
                    <ol className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-sm">
                        <li>
                            <Link
                                href={labsRoute()}
                                prefetch
                                className="hover:text-foreground focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            >
                                Labs
                            </Link>
                        </li>
                        <li aria-hidden="true">
                            <ChevronRight className="size-3.5" />
                        </li>
                        <li>
                            <Link
                                href={showLab(lab.slug)}
                                prefetch
                                className="hover:text-foreground focus-visible:ring-ring rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            >
                                {lab.title}
                            </Link>
                        </li>
                        <li aria-hidden="true">
                            <ChevronRight className="size-3.5" />
                        </li>
                        <li className="text-foreground" aria-current="page">
                            Workspace
                        </li>
                    </ol>
                </nav>

                <header className="max-w-3xl border-b pb-7">
                    <p className="text-primary font-mono text-xs font-medium tracking-wide uppercase">
                        {formatVulnerabilityType(lab.vulnerability_type)}
                    </p>
                    <h1 className="mt-3 text-3xl leading-tight font-semibold tracking-tight sm:text-4xl">
                        {lab.title}
                    </h1>
                    <p className="text-muted-foreground mt-3 text-base leading-7">
                        {lab.description}
                    </p>
                    <div className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm">
                        <span className="inline-flex items-center gap-2">
                            Difficulty
                            <Badge variant="outline" className="capitalize">
                                {lab.difficulty}
                            </Badge>
                        </span>
                        <LabStateLabel state={lab.state} />
                    </div>
                </header>

                <Alert className="border-primary/25 bg-primary/5">
                    <ShieldCheck aria-hidden="true" className="text-primary" />
                    <AlertTitle>Safe simulation</AlertTitle>
                    <AlertDescription>
                        Your input stays inert. SecureLab models what a
                        fictional vulnerable application would do without
                        executing code, queries, scripts, or external requests.
                    </AlertDescription>
                </Alert>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-start">
                    <section aria-labelledby="simulation-input-heading">
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardHeader className="border-b px-5 py-5 sm:px-6">
                                <h2
                                    id="simulation-input-heading"
                                    className="text-base font-semibold"
                                >
                                    Simulation input
                                </h2>
                                {simulation.instructions && (
                                    <p className="text-muted-foreground text-sm leading-6">
                                        {simulation.instructions}
                                    </p>
                                )}
                            </CardHeader>
                            <CardContent className="px-5 py-5 sm:px-6 sm:py-6">
                                {simulation.supported ? (
                                    <Form<SimulationFormData>
                                        {...simulateLab.form({ lab: lab.slug })}
                                        options={{ preserveScroll: true }}
                                        disableWhileProcessing
                                        className="space-y-5"
                                    >
                                        {({ errors, processing }) => (
                                            <>
                                                {simulation.fields.map(
                                                    (field) => {
                                                        const error =
                                                            errors[
                                                                `input.${field.key}`
                                                            ];
                                                        const errorMessage =
                                                            Array.isArray(error)
                                                                ? error[0]
                                                                : error;

                                                        return (
                                                            <div
                                                                key={field.key}
                                                                className="space-y-2"
                                                            >
                                                                <Label
                                                                    htmlFor={`simulation-${field.key}`}
                                                                >
                                                                    {
                                                                        field.label
                                                                    }
                                                                </Label>
                                                                <Input
                                                                    id={`simulation-${field.key}`}
                                                                    name={`input[${field.key}]`}
                                                                    type={
                                                                        field.type
                                                                    }
                                                                    placeholder={
                                                                        field.placeholder
                                                                    }
                                                                    maxLength={
                                                                        field.max_length
                                                                    }
                                                                    autoComplete="off"
                                                                    aria-invalid={Boolean(
                                                                        errorMessage,
                                                                    )}
                                                                    aria-describedby={
                                                                        errorMessage
                                                                            ? `simulation-${field.key}-error`
                                                                            : undefined
                                                                    }
                                                                />
                                                                <div className="flex justify-between gap-3">
                                                                    {errorMessage ? (
                                                                        <p
                                                                            id={`simulation-${field.key}-error`}
                                                                            className="text-destructive text-sm"
                                                                        >
                                                                            {
                                                                                errorMessage
                                                                            }
                                                                        </p>
                                                                    ) : (
                                                                        <span />
                                                                    )}
                                                                    <span className="text-muted-foreground shrink-0 font-mono text-xs">
                                                                        max{' '}
                                                                        {
                                                                            field.max_length
                                                                        }
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        );
                                                    },
                                                )}

                                                {errors.simulation && (
                                                    <p className="text-destructive text-sm">
                                                        {Array.isArray(
                                                            errors.simulation,
                                                        )
                                                            ? errors
                                                                  .simulation[0]
                                                            : errors.simulation}
                                                    </p>
                                                )}

                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    aria-busy={processing}
                                                    className="w-full sm:w-auto"
                                                >
                                                    {processing ? (
                                                        <Spinner />
                                                    ) : (
                                                        <FlaskConical aria-hidden="true" />
                                                    )}
                                                    {processing
                                                        ? 'Simulating...'
                                                        : 'Run safe simulation'}
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                ) : (
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-semibold">
                                            Simulation unavailable
                                        </h3>
                                        <p className="text-muted-foreground text-sm leading-6">
                                            This lab does not have a supported
                                            safe simulation yet. Its catalog and
                                            scenario remain available.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <section
                        aria-labelledby="simulation-result-heading"
                        aria-live="polite"
                    >
                        <Card className="gap-0 rounded-lg py-0 shadow-none">
                            <CardHeader className="border-b px-5 py-5 sm:px-6">
                                <h2
                                    id="simulation-result-heading"
                                    className="text-base font-semibold"
                                >
                                    Simulated outcome
                                </h2>
                            </CardHeader>
                            <CardContent className="px-5 py-5 sm:px-6 sm:py-6">
                                {simulation.result ? (
                                    <div className="space-y-6">
                                        <OutcomeSummary
                                            result={simulation.result}
                                        />

                                        <section
                                            aria-labelledby="submitted-data-heading"
                                            className="space-y-2"
                                        >
                                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                                <h3
                                                    id="submitted-data-heading"
                                                    className="text-sm font-semibold"
                                                >
                                                    Submitted data
                                                </h3>
                                                <span className="text-muted-foreground font-mono text-[0.6875rem] tracking-wide uppercase">
                                                    Inert text
                                                </span>
                                            </div>
                                            <dl className="divide-y rounded-md border">
                                                {Object.entries(
                                                    simulation.result.input,
                                                ).map(([key, value]) => (
                                                    <div
                                                        key={key}
                                                        className="grid gap-1 px-3 py-3 sm:grid-cols-[9rem_minmax(0,1fr)]"
                                                    >
                                                        <dt className="text-muted-foreground text-xs font-medium">
                                                            {formatInputKey(
                                                                key,
                                                            )}
                                                        </dt>
                                                        <dd className="min-w-0">
                                                            <code className="text-foreground block text-xs break-all whitespace-pre-wrap">
                                                                {value}
                                                            </code>
                                                        </dd>
                                                    </div>
                                                ))}
                                            </dl>
                                        </section>

                                        <section
                                            aria-labelledby="simulation-flow-heading"
                                            className="space-y-4"
                                        >
                                            <div className="space-y-1">
                                                <h3
                                                    id="simulation-flow-heading"
                                                    className="text-sm font-semibold"
                                                >
                                                    Simulation flow
                                                </h3>
                                                <p className="text-muted-foreground text-xs leading-5">
                                                    How the fictional
                                                    application handled the
                                                    submitted data.
                                                </p>
                                            </div>
                                            <SimulationTrace
                                                outcome={
                                                    simulation.result.outcome
                                                }
                                                steps={simulation.result.steps}
                                            />
                                        </section>
                                    </div>
                                ) : (
                                    <div className="flex min-h-48 flex-col items-start justify-center gap-3">
                                        <div className="bg-muted flex size-9 items-center justify-center rounded-md border">
                                            <FlaskConical
                                                aria-hidden="true"
                                                className="text-muted-foreground size-4"
                                            />
                                        </div>
                                        <div className="max-w-md space-y-1">
                                            <h3 className="text-sm font-semibold">
                                                No result yet
                                            </h3>
                                            <p className="text-muted-foreground text-sm leading-6">
                                                Run a safe simulation to inspect
                                                how input moves through the
                                                fictional application.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </main>
        </>
    );
}

LabWorkspacePage.layout = {
    breadcrumbs: [
        {
            title: 'Labs',
            href: labsRoute(),
        },
    ],
};
