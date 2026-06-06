import type { Inertia } from '@/wayfinder/types';

type DashboardPageProps = Inertia.Pages.Dashboard;

export type DashboardEvent = DashboardPageProps['todayEvents'][number];

export type DashboardStats = DashboardPageProps['stats'];

export type DashboardProps = Pick<DashboardPageProps, 'todayEvents' | 'stats'>;
