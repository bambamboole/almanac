import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarDays,
    ContactRound,
    FolderSync,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { calendar, contacts, dashboard, login } from '@/wayfinder/routes';

const signals = [
    {
        label: 'Calendar',
        description: 'Plan days and sync DAV events.',
        href: calendar(),
        icon: CalendarDays,
    },
    {
        label: 'Contacts',
        description: 'Keep address books typed and searchable.',
        href: contacts(),
        icon: ContactRound,
    },
    {
        label: 'DAV sync',
        description: 'Bring devices into the same source of truth.',
        href: dashboard(),
        icon: FolderSync,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();
    const primaryHref = auth.user ? dashboardUrl : login();

    return (
        <>
            <Head title="Welcome" />

            <main className="bg-background text-foreground min-h-screen overflow-hidden">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                    <Link href={dashboardUrl} className="flex items-center">
                        <AppLogoIcon className="size-24" />
                        <span className="sr-only">Almanac</span>
                    </Link>

                    <Button asChild variant="outline">
                        <Link href={primaryHref}>
                            {auth.user ? 'Dashboard' : 'Log in'}
                        </Link>
                    </Button>
                </header>

                <section className="mx-auto grid min-h-[calc(100vh-8rem)] w-full max-w-6xl content-center gap-10 px-6 pb-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-center">
                    <div>
                        <Badge variant="outline">Almanac workspace</Badge>
                        <h1 className="mt-6 max-w-4xl text-5xl font-semibold tracking-tight text-balance md:text-7xl">
                            A private ledger for calendars, contacts, and sync.
                        </h1>
                        <p className="mt-6 max-w-2xl text-base leading-7 text-muted-foreground md:text-lg">
                            Almanac gives the practical parts of a personal
                            operating system a calm, premium home: schedule,
                            address books, and DAV collections in one place.
                        </p>

                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button asChild size="lg">
                                <Link href={primaryHref}>
                                    {auth.user
                                        ? 'Open Almanac'
                                        : 'Enter Almanac'}
                                    <ArrowUpRight className="size-4" />
                                </Link>
                            </Button>
                            {auth.user && (
                                <Button asChild size="lg" variant="outline">
                                    <Link href={contacts()}>View contacts</Link>
                                </Button>
                            )}
                        </div>
                    </div>

                    <aside className="almanac-panel p-4">
                        <div className="border-b border-border/70 px-2 pb-4">
                            <p className="almanac-kicker">Inside</p>
                        </div>
                        <div className="divide-y divide-border/70">
                            {signals.map((signal) => (
                                <Link
                                    key={signal.label}
                                    href={auth.user ? signal.href : login()}
                                    className="group flex gap-4 px-2 py-5"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                                        <signal.icon className="size-5" />
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block text-sm font-semibold">
                                            {signal.label}
                                        </span>
                                        <span className="mt-1 block text-sm leading-5 text-muted-foreground">
                                            {signal.description}
                                        </span>
                                    </span>
                                    <ArrowUpRight className="ml-auto size-4 shrink-0 text-muted-foreground transition group-hover:text-foreground" />
                                </Link>
                            ))}
                        </div>
                    </aside>
                </section>
            </main>
        </>
    );
}
