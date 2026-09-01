import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="border-sidebar-primary/25 bg-sidebar-primary/10 text-sidebar-primary flex aspect-square size-8 items-center justify-center rounded-md border">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">
                    SecureLab
                </span>
                <span className="text-sidebar-foreground/55 truncate text-[10px] leading-tight tracking-[0.12em] uppercase">
                    Security learning
                </span>
            </div>
        </>
    );
}
