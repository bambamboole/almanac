import type { Inertia } from '@/wayfinder/types';

type CalendarIndexProps = Inertia.Pages.Calendar.Index;

export type Calendar = CalendarIndexProps['calendars'][number];

export type CalendarEvent = CalendarIndexProps['events'][number];

export type CalendarEventCalendar = CalendarEvent['calendar'];

export type CalendarObjectData = CalendarEvent['data'];

export type CalendarWindow = CalendarIndexProps['window'];
