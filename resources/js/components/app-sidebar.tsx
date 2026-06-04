import { Link, usePage } from '@inertiajs/react';
import { Calendar, ContactRound, LayoutGrid, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { calendar, contacts, dashboard } from '@/wayfinder/routes';
import { index as users } from '@/wayfinder/routes/users';
import type { NavItem } from '@/types';
import { Permission } from '@/types/permissions';

export function AppSidebar() {
    const canManageUsers =
        usePage().props.auth.user.role?.permissions.includes(
            Permission.UsersManage,
        ) ?? false;
    const dashboardUrl = dashboard();
    const calendarUrl = calendar();
    const contactsUrl = contacts();
    const usersUrl = users();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Calendar',
            href: calendarUrl,
            icon: Calendar,
        },
        {
            title: 'Contacts',
            href: contactsUrl,
            icon: ContactRound,
        },
    ];

    if (canManageUsers) {
        mainNavItems.push({
            title: 'Users',
            href: usersUrl,
            icon: Users,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="px-3 pt-3 pb-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            className="hover:bg-sidebar-accent/50 h-28 justify-center p-0 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-0!"
                            asChild
                        >
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
