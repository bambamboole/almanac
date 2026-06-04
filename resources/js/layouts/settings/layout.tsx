import { Link } from '@inertiajs/react';
import { KeyRound, Palette, ShieldCheck, UserCircle } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/wayfinder/routes/appearance';
import { edit } from '@/wayfinder/routes/profile';
import { edit as editSecurity } from '@/wayfinder/routes/security';
import { edit as editDav } from '@/wayfinder/routes/settings/dav';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: UserCircle,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        title: 'DAV Credentials',
        href: editDav(),
        icon: KeyRound,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto max-w-260 space-y-5 px-5 pt-6 md:px-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="almanac-panel flex flex-col gap-5 p-4 lg:flex-row">
                <aside className="w-full max-w-xl lg:w-38">
                    <nav className="flex flex-col gap-1" aria-label="Settings">
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-secondary text-secondary-foreground':
                                        isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="lg:hidden" />

                <div className="min-w-0 flex-1">
                    <section className="w-full max-w-3xl space-y-12 lg:min-w-3xl">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
