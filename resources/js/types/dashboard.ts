import type { CalendarEvent } from './calendar';

export type DashboardEvent = CalendarEvent;

export type DashboardStats = {
    todayEventCount: number;
    weekEventCount: number;
    contactCount: number;
};

export type DashboardProps = {
    todayEvents: DashboardEvent[];
    stats: DashboardStats;
};
