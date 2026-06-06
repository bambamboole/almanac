import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/wayfinder/routes';
import { edit } from '@/wayfinder/routes/profile';

export function NavUserProfile() {
    const { auth } = usePage().props;

    return (
        <SidebarMenu className="px-3 py-1">
            <SidebarMenuItem>
                <SidebarMenuButton
                    asChild
                    size="lg"
                    className="h-auto cursor-default flex-col justify-center gap-2 px-2 py-3 text-sidebar-accent-foreground hover:bg-transparent hover:text-sidebar-accent-foreground group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2!"
                    data-test="sidebar-menu-button"
                >
                    <div>
                        <UserInfo
                            user={auth.user}
                            showEmail={true}
                            align="center"
                            hideTextOnCollapse={true}
                        />
                    </div>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

export function NavUserActions() {
    const cleanup = useMobileNavigation();
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const profileUrl = edit();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton
                    asChild
                    isActive={isCurrentOrParentUrl(profileUrl)}
                    tooltip={{ children: 'Settings' }}
                >
                    <Link href={profileUrl} prefetch onClick={cleanup}>
                        <Settings />
                        <span>Settings</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
                <SidebarMenuButton asChild tooltip={{ children: 'Log out' }}>
                    <Link
                        href={logout()}
                        as="button"
                        onClick={handleLogout}
                        data-test="logout-button"
                    >
                        <LogOut />
                        <span>Log out</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
