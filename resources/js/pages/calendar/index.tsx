import type {
    DateSelectArg,
    DayCellMountArg,
    EventClickArg,
    EventInput,
    EventMountArg,
} from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import type { DateClickArg } from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head, router, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import '@/lib/echo';
import {
    CalendarDays,
    Download,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import type { CSSProperties } from 'react';
import {
    CreateCalendarDialog,
    CreateEventDialog,
    DeleteCalendarDialog,
    EditCalendarDialog,
    EditEventDialog,
} from '@/components/calendar/calendar-dialogs';
import type { CreateEventDefaults } from '@/components/calendar/calendar-dialogs';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { calendar } from '@/wayfinder/routes';
import { exportMethod as exportCalendar } from '@/wayfinder/routes/calendar';
import { exportMethod as exportSingleCalendar } from '@/wayfinder/routes/calendar/calendars';
import { DEFAULT_EVENT_COLOR } from '@/lib/calendar';
import type { Calendar, CalendarEvent, CalendarWindow } from '@/types/calendar';

type Props = {
    calendars: Calendar[];
    window: CalendarWindow;
};

function CalendarSwatch({ color }: { color: string | null }) {
    return (
        <span
            className="size-2.5 shrink-0 rounded-full border border-black/10 dark:border-white/20"
            style={{ backgroundColor: color ?? DEFAULT_EVENT_COLOR }}
            aria-hidden="true"
        />
    );
}

function localDateKey(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

type DavChangedPayload = {
    type: 'calendar' | 'address_book';
    collection_id: number;
    uri: string;
    operation: string;
    sync_token: string;
};

function eventDayKeys(event: CalendarEvent): string[] {
    if (!event.starts_at || !event.ends_at) {
        return [];
    }

    const startsOn = event.starts_at.slice(0, 10);

    if (!event.is_all_day) {
        return [startsOn];
    }

    const keys: string[] = [];
    const current = new Date(`${startsOn}T00:00:00Z`);
    const end = new Date(`${event.ends_at.slice(0, 10)}T00:00:00Z`);

    while (current < end) {
        keys.push(current.toISOString().slice(0, 10));
        current.setUTCDate(current.getUTCDate() + 1);
    }

    return keys;
}

const fullCalendarTheme = {
    '--fc-border-color': 'var(--border)',
    '--fc-page-bg-color': 'var(--background)',
    '--fc-neutral-bg-color': 'var(--muted)',
    '--fc-neutral-text-color': 'var(--muted-foreground)',
    '--fc-list-event-hover-bg-color': 'var(--accent)',
    '--fc-today-bg-color':
        'color-mix(in oklab, var(--primary) 9%, transparent)',
    '--fc-button-bg-color': 'var(--background)',
    '--fc-button-border-color': 'var(--border)',
    '--fc-button-text-color': 'var(--foreground)',
    '--fc-button-hover-bg-color': 'var(--accent)',
    '--fc-button-hover-border-color': 'var(--border)',
    '--fc-button-active-bg-color': 'var(--secondary)',
    '--fc-button-active-border-color': 'var(--border)',
    '--fc-small-font-size': '0.75rem',
} as CSSProperties;

export default function CalendarIndex({ calendars }: Props) {
    const userId = usePage().props.auth.user.id;
    const [editingEvent, setEditingEvent] = useState<CalendarEvent | null>(
        null,
    );
    const [editingCalendar, setEditingCalendar] = useState<Calendar | null>(
        null,
    );
    const [deletingCalendar, setDeletingCalendar] = useState<Calendar | null>(
        null,
    );
    const [creatingEvent, setCreatingEvent] = useState(false);
    const [creatingCalendar, setCreatingCalendar] = useState(false);
    const [createEventDefaults, setCreateEventDefaults] = useState<
        CreateEventDefaults | undefined
    >(undefined);

    const openCreateDialog = useCallback((defaults?: CreateEventDefaults) => {
        setCreateEventDefaults(defaults);
        setCreatingEvent(true);
    }, []);

    useEcho<DavChangedPayload>(
        `dav.${userId}`,
        '.dav.changed',
        (e) => {
            if (e.type === 'calendar') {
                router.reload({ only: ['calendars'] });
            }
        },
        [userId],
        'private',
    );

    const events = useMemo(() => {
        return calendars.flatMap((calendar) => calendar.events);
    }, [calendars]);

    const eventsById = useMemo(() => {
        return new Map(events.map((event) => [String(event.id), event]));
    }, [events]);

    const eventDays = useMemo(() => {
        return new Set(events.flatMap((event) => eventDayKeys(event)));
    }, [events]);

    const writableCalendars = useMemo(() => {
        return calendars.filter((calendar) => calendar.can_write);
    }, [calendars]);

    const calendarEvents = useMemo<EventInput[]>(() => {
        return events.flatMap((event) => {
            if (!event.starts_at || !event.ends_at) {
                return [];
            }

            const color = event.calendar.color ?? DEFAULT_EVENT_COLOR;

            return [
                {
                    id: String(event.id),
                    title: event.data.summary ?? 'Untitled event',
                    start: event.is_all_day
                        ? event.starts_at.slice(0, 10)
                        : event.starts_at,
                    end: event.is_all_day
                        ? event.ends_at.slice(0, 10)
                        : event.ends_at,
                    allDay: event.is_all_day,
                    backgroundColor: color,
                    borderColor: color,
                    extendedProps: {
                        calendarEvent: event,
                        calendarName: event.calendar.display_name,
                    },
                },
            ];
        });
    }, [events]);

    const handleEventClick = useCallback(
        (click: EventClickArg) => {
            click.jsEvent.preventDefault();

            const event = eventsById.get(click.event.id);

            if (event?.calendar.can_write) {
                setEditingEvent(event);
            }
        },
        [eventsById],
    );

    const handleDateSelect = useCallback(
        (selection: DateSelectArg) => {
            openCreateDialog({
                start: selection.start,
                end: selection.end,
                isAllDay: selection.allDay,
            });
        },
        [openCreateDialog],
    );

    const handleDateClick = useCallback(
        (click: DateClickArg) => {
            openCreateDialog({
                start: click.date,
                isAllDay: click.allDay,
            });
        },
        [openCreateDialog],
    );

    const handleEventDidMount = useCallback((mount: EventMountArg) => {
        mount.el.dataset.editEvent = mount.event.id;
        mount.el.setAttribute(
            'aria-label',
            `Edit ${mount.event.title || 'event'}`,
        );
    }, []);

    const handleDayCellDidMount = useCallback(
        (mount: DayCellMountArg) => {
            const key = localDateKey(mount.date);

            if (eventDays.has(key)) {
                mount.el.dataset.calendarDayGroup = key;
            }
        },
        [eventDays],
    );

    return (
        <>
            <Head title="Calendar" />

            {editingEvent && (
                <EditEventDialog
                    event={editingEvent}
                    open={editingEvent !== null}
                    onClose={() => setEditingEvent(null)}
                />
            )}

            {creatingEvent && (
                <CreateEventDialog
                    calendars={writableCalendars}
                    open={creatingEvent}
                    defaults={createEventDefaults}
                    onClose={() => setCreatingEvent(false)}
                />
            )}

            {creatingCalendar && (
                <CreateCalendarDialog
                    open={creatingCalendar}
                    onClose={() => setCreatingCalendar(false)}
                />
            )}

            {editingCalendar && (
                <EditCalendarDialog
                    key={editingCalendar.id}
                    calendar={editingCalendar}
                    open={editingCalendar !== null}
                    onClose={() => setEditingCalendar(null)}
                />
            )}

            {deletingCalendar && (
                <DeleteCalendarDialog
                    calendar={deletingCalendar}
                    open={deletingCalendar !== null}
                    onClose={() => setDeletingCalendar(null)}
                />
            )}

            <div className="mx-auto flex h-full min-h-0 w-full max-w-[88rem] flex-1 flex-col gap-5 px-5 pt-6 md:px-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Calendar"
                        description="Organize your events and appointments."
                    />

                    <div className="flex items-center gap-2">
                        <Button
                            onClick={() => openCreateDialog(undefined)}
                            disabled={writableCalendars.length === 0}
                        >
                            <Plus className="size-4" />
                            New event
                        </Button>
                    </div>
                </div>

                <div className="grid min-h-0 gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
                    <aside className="almanac-panel min-h-0">
                        <div className="flex items-center justify-between gap-2 border-b border-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">Calendars</h2>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                    aria-label="New calendar"
                                    data-new-calendar
                                    onClick={() => setCreatingCalendar(true)}
                                >
                                    <Plus className="size-3.5" />
                                </Button>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                >
                                    <a
                                        href={exportCalendar.url()}
                                        download
                                        aria-label="Export calendars"
                                        data-export-calendars
                                    >
                                        <Download className="size-3.5" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                        {calendars.length > 0 ? (
                            <div className="divide-y divide-border overflow-y-auto">
                                {calendars.map((cal) => (
                                    <div
                                        key={cal.id}
                                        className="flex min-w-0 items-start gap-3 px-4 py-3"
                                    >
                                        <CalendarSwatch color={cal.color} />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {cal.display_name}
                                            </p>
                                            {cal.timezone && (
                                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                    {cal.timezone}
                                                </p>
                                            )}
                                        </div>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7 shrink-0"
                                                    aria-label={`${cal.display_name} actions`}
                                                    data-calendar-actions={
                                                        cal.id
                                                    }
                                                >
                                                    <MoreHorizontal className="size-3.5" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {cal.is_owner && (
                                                    <DropdownMenuItem
                                                        data-edit-calendar={
                                                            cal.id
                                                        }
                                                        onSelect={() =>
                                                            setEditingCalendar(
                                                                cal,
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="size-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem asChild>
                                                    <a
                                                        href={exportSingleCalendar.url(
                                                            cal.id,
                                                        )}
                                                        download
                                                        data-export-calendar={
                                                            cal.id
                                                        }
                                                    >
                                                        <Download className="size-4" />
                                                        Export
                                                    </a>
                                                </DropdownMenuItem>
                                                {cal.is_owner && (
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        data-delete-calendar={
                                                            cal.id
                                                        }
                                                        onSelect={() =>
                                                            setDeletingCalendar(
                                                                cal,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="p-6 text-center text-sm text-muted-foreground">
                                No calendars are connected.
                            </div>
                        )}
                    </aside>

                    <main className="almanac-panel min-h-0">
                        <div className="flex flex-col gap-1 border-b border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-sm font-semibold">
                                Calendar view
                            </h2>
                            <Badge variant="outline">
                                {events.length}{' '}
                                {events.length === 1 ? 'event' : 'events'}
                            </Badge>
                        </div>

                        {events.length === 0 ? (
                            <div className="flex min-h-80 flex-col items-center justify-center p-8 text-center">
                                <div className="mb-4 flex size-12 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                                    <CalendarDays className="size-6 text-muted-foreground" />
                                </div>
                                <p className="font-medium">No events</p>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                    Events from connected DAV calendars will
                                    appear here.
                                </p>
                            </div>
                        ) : (
                            <div
                                data-calendar-view
                                className="h-[clamp(36rem,74vh,46rem)] overflow-hidden p-3 text-foreground [&_.fc]:h-full [&_.fc-button]:rounded-md [&_.fc-button]:px-3 [&_.fc-button]:py-1.5 [&_.fc-button]:text-sm [&_.fc-button]:font-semibold [&_.fc-col-header-cell-cushion]:py-2 [&_.fc-col-header-cell-cushion]:text-xs [&_.fc-col-header-cell-cushion]:font-semibold [&_.fc-col-header-cell-cushion]:text-muted-foreground [&_.fc-daygrid-day-number]:text-xs [&_.fc-daygrid-day-number]:text-muted-foreground [&_.fc-event]:cursor-pointer [&_.fc-event]:border-0 [&_.fc-event]:shadow-none [&_.fc-event-main]:min-w-0 [&_.fc-scrollgrid]:rounded-md [&_.fc-toolbar]:flex-wrap [&_.fc-toolbar]:gap-2 [&_.fc-toolbar-title]:text-base [&_.fc-toolbar-title]:font-semibold"
                                style={fullCalendarTheme}
                            >
                                <FullCalendar
                                    plugins={[
                                        dayGridPlugin,
                                        timeGridPlugin,
                                        interactionPlugin,
                                    ]}
                                    initialView="dayGridMonth"
                                    headerToolbar={{
                                        left: 'prev,next today',
                                        center: 'title',
                                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                                    }}
                                    buttonText={{
                                        today: 'Today',
                                        month: 'Month',
                                        week: 'Week',
                                        day: 'Day',
                                    }}
                                    events={calendarEvents}
                                    eventClick={handleEventClick}
                                    eventDidMount={handleEventDidMount}
                                    dayCellDidMount={handleDayCellDidMount}
                                    select={handleDateSelect}
                                    dateClick={handleDateClick}
                                    editable={false}
                                    eventStartEditable={false}
                                    eventDurationEditable={false}
                                    selectable
                                    nowIndicator
                                    height="100%"
                                />
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </>
    );
}

CalendarIndex.layout = {
    breadcrumbs: [
        {
            title: 'Calendar',
            href: calendar(),
        },
    ],
};
