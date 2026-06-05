import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarDays,
    ContactRound,
    FolderSync,
} from 'lucide-react';
import { calendar, contacts, dashboard } from '@/wayfinder/routes';
import { edit as editProfile } from '@/wayfinder/routes/profile';
import { DEFAULT_EVENT_COLOR } from '@/lib/calendar';
import { moonPhase } from '@/lib/moon';
import type { DashboardProps } from '@/types/dashboard';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

function greeting(date: Date): string {
    const hour = date.getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
}

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
});
const dateFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
});

const quickLinks = [
    { label: 'Calendar', value: 'Today', href: calendar(), icon: CalendarDays },
    {
        label: 'Contacts',
        value: 'People',
        href: contacts(),
        icon: ContactRound,
    },
    { label: 'Sync', value: 'DAV', href: editProfile(), icon: FolderSync },
];

export default function Dashboard({ todayEvents, stats }: DashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const now = new Date();
    const firstName = auth.user.name.split(' ')[0];
    const moon = moonPhase(now);

    const statCards = [
        { label: 'Today’s events', value: stats.todayEventCount },
        { label: 'This week', value: stats.weekEventCount },
        { label: 'Contacts', value: stats.contactCount },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex h-full min-h-0 w-full max-w-[88rem] flex-1 flex-col gap-5 overflow-y-auto px-5 pt-6 pb-6 md:px-6">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="almanac-kicker">
                            {dateFormatter.format(now)}
                        </p>
                        <h1 className="mt-2 font-serif text-3xl font-medium tracking-tight">
                            {greeting(now)}, {firstName}
                        </h1>
                    </div>
                    <p className="flex items-center gap-2 text-sm text-muted-foreground">
                        <span className="text-lg leading-none" aria-hidden>
                            {moon.glyph}
                        </span>
                        {moon.name}
                    </p>
                </header>

                <section className="grid gap-4 sm:grid-cols-3">
                    {statCards.map((card) => (
                        <div key={card.label} className="almanac-panel p-5">
                            <p className="text-sm text-muted-foreground">
                                {card.label}
                            </p>
                            <p className="mt-2 font-serif text-4xl font-medium tracking-tight">
                                {card.value}
                            </p>
                        </div>
                    ))}
                </section>

                <section className="almanac-panel p-5">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="almanac-kicker">Today</p>
                            <h2 className="mt-2 text-xl font-semibold tracking-tight">
                                Agenda
                            </h2>
                        </div>
                        <Link
                            href={calendar()}
                            className="text-sm font-medium text-primary hover:underline"
                        >
                            Open calendar →
                        </Link>
                    </div>

                    {todayEvents.length === 0 ? (
                        <p className="mt-6 rounded-md border border-border/70 bg-background/55 p-6 text-center text-sm text-muted-foreground">
                            Nothing scheduled today.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y divide-border/70">
                            {todayEvents.map((event) => (
                                <li
                                    key={event.id}
                                    className="grid grid-cols-[64px_1fr] items-center gap-4 py-3"
                                >
                                    <span className="text-sm font-semibold tabular-nums text-primary">
                                        {event.is_all_day
                                            ? 'All day'
                                            : timeFormatter.format(
                                                  new Date(event.starts_at),
                                              )}
                                    </span>
                                    <span className="flex items-center gap-3">
                                        <span
                                            className="size-2 shrink-0 rounded-full"
                                            style={{
                                                background:
                                                    event.calendar.color ??
                                                    DEFAULT_EVENT_COLOR,
                                            }}
                                            aria-hidden
                                        />
                                        <span className="text-sm font-medium">
                                            {event.data.summary ??
                                                'Untitled event'}
                                        </span>
                                        {event.data.location && (
                                            <span className="text-xs text-muted-foreground">
                                                · {event.data.location}
                                            </span>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="grid gap-4 sm:grid-cols-3">
                    {quickLinks.map((item) => (
                        <Link
                            key={item.label}
                            href={item.href}
                            className="almanac-panel group block p-5 transition hover:-translate-y-0.5 hover:border-primary/45"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex size-11 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                                    <item.icon className="size-5" />
                                </div>
                                <ArrowUpRight className="size-4 text-muted-foreground transition group-hover:text-foreground" />
                            </div>
                            <p className="mt-5 text-sm text-muted-foreground">
                                {item.label}
                            </p>
                            <h2 className="mt-1 text-2xl font-semibold tracking-tight">
                                {item.value}
                            </h2>
                        </Link>
                    ))}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
});
