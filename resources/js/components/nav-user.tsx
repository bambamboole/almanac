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

export function NavUser() {
    const { auth } = usePage().props;
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
                    size="lg"
                    className="text-sidebar-accent-foreground hover:bg-transparent hover:text-sidebar-accent-foreground"
                    data-test="sidebar-menu-button"
                >
                    <div>
                        <UserInfo user={auth.user} showEmail={true} />
                    </div>
                </SidebarMenuButton>
            </SidebarMenuItem>
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
