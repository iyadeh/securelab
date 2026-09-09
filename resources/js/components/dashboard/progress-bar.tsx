export function DashboardProgressBar({
    label,
    value,
}: {
    label: string;
    value: number;
}) {
    return (
        <div
            role="progressbar"
            aria-label={label}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={value}
            className="bg-muted h-1.5 overflow-hidden rounded-sm"
        >
            <div
                className="bg-primary h-full rounded-sm"
                style={{ width: `${value}%` }}
            />
        </div>
    );
}
