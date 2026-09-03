import { Form, Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronRight,
    Circle,
    CircleDot,
    FlaskConical,
    ShieldCheck,
} from 'lucide-react';
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

type SimulationOutcome = 'normal_behavior' | 'vulnerable_behavior' | 'rejected';

type SimulationStepType = 'input' | 'application' | 'decision' | 'output';

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

type SimulationStep = {
    key: string;
    type: SimulationStepType;
    label: string;
    description: string;
};

type SimulationResult = {
    outcome: SimulationOutcome;
    summary: string;
    input: Record<string, string>;
    steps: SimulationStep[];
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

const outcomeLabels: Record<SimulationOutcome, string> = {
    normal_behavior: 'Normal behavior',
    vulnerable_behavior: 'Vulnerable behavior simulated',
    rejected: 'Rejected',
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
                                        <div className="space-y-3">
                                            <Badge variant="outline">
                                                {
                                                    outcomeLabels[
                                                        simulation.result
                                                            .outcome
                                                    ]
                                                }
                                            </Badge>
                                            <p className="text-sm leading-6">
                                                {simulation.result.summary}
                                            </p>
                                        </div>

                                        <div className="space-y-2">
                                            <h3 className="text-sm font-semibold">
                                                Submitted data
                                            </h3>
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
                                        </div>

                                        <div className="space-y-3">
                                            <h3 className="text-sm font-semibold">
                                                Simulation trace
                                            </h3>
                                            <ol className="space-y-3">
                                                {simulation.result.steps.map(
                                                    (step, index) => (
                                                        <li
                                                            key={step.key}
                                                            className="grid grid-cols-[2rem_minmax(0,1fr)] gap-3"
                                                        >
                                                            <span className="bg-muted flex size-8 items-center justify-center rounded-md border font-mono text-xs font-semibold tabular-nums">
                                                                {String(
                                                                    index + 1,
                                                                ).padStart(
                                                                    2,
                                                                    '0',
                                                                )}
                                                            </span>
                                                            <div className="min-w-0 pt-0.5">
                                                                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                                                    <h4 className="text-sm font-medium">
                                                                        {
                                                                            step.label
                                                                        }
                                                                    </h4>
                                                                    <span className="text-muted-foreground font-mono text-[0.6875rem] uppercase">
                                                                        {
                                                                            step.type
                                                                        }
                                                                    </span>
                                                                </div>
                                                                <p className="text-muted-foreground mt-1 text-sm leading-6 break-words">
                                                                    {
                                                                        step.description
                                                                    }
                                                                </p>
                                                            </div>
                                                        </li>
                                                    ),
                                                )}
                                            </ol>
                                        </div>
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
                                                Submit synthetic input to see
                                                what the fictional application
                                                would do.
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
