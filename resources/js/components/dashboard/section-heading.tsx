export function DashboardSectionHeading({
    id,
    title,
    description,
}: {
    id: string;
    title: string;
    description: string;
}) {
    return (
        <div className="space-y-1">
            <h2 id={id} className="text-xl font-semibold tracking-tight">
                {title}
            </h2>
            <p className="text-muted-foreground text-sm leading-6">
                {description}
            </p>
        </div>
    );
}
