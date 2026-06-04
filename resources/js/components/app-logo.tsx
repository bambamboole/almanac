import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <div className="flex w-full items-center justify-center">
            <AppLogoIcon className="size-24 transition-[width,height] group-data-[collapsible=icon]:hidden" />
            <AppLogoIcon
                compact
                className="hidden size-8 group-data-[collapsible=icon]:block"
            />
        </div>
    );
}
