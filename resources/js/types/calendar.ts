export type Calendar = {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    timezone: string | null;
    components: string[];
};

export type CalendarEventCalendar = {
    id: number;
    name: string;
    color: string | null;
};

export type CalendarEvent = {
    id: number;
    calendar_id: number;
    calendar: CalendarEventCalendar;
    summary: string | null;
    description: string | null;
    location: string | null;
    starts_at: string;
    ends_at: string;
    starts_on: string;
    ends_on: string;
    all_day: boolean;
    status: string | null;
    url: string | null;
    etag: string;
};

export type CalendarWindow = {
    start: string;
    end: string;
};
