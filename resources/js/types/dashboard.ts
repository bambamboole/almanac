export type DashboardEvent = {
    id: number;
    summary: string | null;
    location: string | null;
    starts_at: string;
    ends_at: string | null;
    all_day: boolean;
    calendar: { name: string; color: string | null };
};

export type DashboardStats = {
    todayEventCount: number;
    weekEventCount: number;
    contactCount: number;
};

export type DashboardProps = {
    todayEvents: DashboardEvent[];
    stats: DashboardStats;
};
