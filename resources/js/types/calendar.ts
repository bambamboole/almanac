export type Calendar = {
    id: number;
    display_name: string;
    description: string | null;
    color: string | null;
    timezone: string | null;
    components: string[];
};

export type CalendarEventCalendar = {
    id: number;
    display_name: string;
    color: string | null;
};

/** Mirrors Bambamboole\LaravelDav\Dto\CalendarObjectData (the `raw` field is stripped server-side). */
export type CalendarObjectData = {
    uid: string | null;
    componentType: string | null;
    summary: string | null;
    description: string | null;
    location: string | null;
    status: string | null;
    url: string | null;
    startsAt: string | null;
    endsAt: string | null;
    isAllDay: boolean;
    isRecurring: boolean;
    timezone: string | null;
};

export type CalendarEvent = {
    id: number;
    dav_calendar_id: number;
    etag: string;
    starts_at: string;
    ends_at: string;
    is_all_day: boolean;
    calendar: CalendarEventCalendar;
    data: CalendarObjectData;
};

export type CalendarWindow = {
    start: string;
    end: string;
};
