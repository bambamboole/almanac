import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
    align = 'start',
    hideTextOnCollapse = false,
}: {
    user: User;
    showEmail?: boolean;
    align?: 'start' | 'center';
    hideTextOnCollapse?: boolean;
}) {
    const getInitials = useInitials();
    const showAvatar = Boolean(user.avatar && user.avatar !== '');

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-lg">
                {showAvatar ? (
                    <AvatarImage src={user.avatar} alt={user.name} />
                ) : null}
                <AvatarFallback className="rounded-lg text-black dark:text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div
                className={cn(
                    'grid flex-1 text-sm leading-tight',
                    align === 'center' ? 'text-center' : 'text-left',
                    hideTextOnCollapse &&
                        'group-data-[collapsible=icon]:hidden',
                )}
            >
                <span className="truncate font-medium">{user.name}</span>
                {showEmail ? (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                ) : null}
            </div>
        </>
    );
}
