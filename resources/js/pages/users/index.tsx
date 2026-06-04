import { Head } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ManagedUser, Props } from '@/components/users/types';
import {
    CreateUserDialog,
    DeleteUserDialog,
    EditUserDialog,
} from '@/components/users/user-dialogs';
import { UserRole } from '@/wayfinder/App/Enums/UserRole';
import { index as users } from '@/wayfinder/routes/users';
import { Permission } from '@/types/permissions';

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export default function Users({ users }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<ManagedUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<ManagedUser | null>(null);

    return (
        <>
            <Head title="Users" />

            <div className="mx-auto max-w-[88rem] space-y-5 pt-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Users"
                        description="Create and manage user accounts."
                    />

                    <Button onClick={() => setIsCreateOpen(true)}>
                        <Plus />
                        New user
                    </Button>
                </div>

                <div className="overflow-hidden rounded-lg border bg-card/80 shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-3xl text-left text-sm">
                            <thead className="border-b bg-muted/45 text-xs font-semibold text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">User</th>
                                    <th className="px-4 py-3">Role</th>
                                    <th className="px-4 py-3 text-right">
                                        Calendars
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Address books
                                    </th>
                                    <th className="w-12 px-4 py-3">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.map((user) => {
                                    const canManageUsers =
                                        user.role?.permissions.includes(
                                            Permission.UsersManage,
                                        ) ?? false;

                                    return (
                                        <tr
                                            key={user.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-xs font-semibold text-primary">
                                                        {initials(user.name)}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="truncate font-medium">
                                                            {user.name}
                                                        </div>
                                                        <div className="truncate text-muted-foreground">
                                                            {user.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        canManageUsers
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {user.role?.name ??
                                                        UserRole.Member}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                {user.calendars_count}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                {user.address_books_count}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            aria-label={`${user.name} actions`}
                                                            data-user-actions={
                                                                user.id
                                                            }
                                                        >
                                                            <MoreHorizontal className="size-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            data-edit-user={
                                                                user.id
                                                            }
                                                            onSelect={() =>
                                                                setEditingUser(
                                                                    user,
                                                                )
                                                            }
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            data-delete-user={
                                                                user.id
                                                            }
                                                            onSelect={() =>
                                                                setDeletingUser(
                                                                    user,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <CreateUserDialog
                open={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
            />
            {editingUser && (
                <EditUserDialog
                    key={editingUser.id}
                    user={editingUser}
                    open
                    onClose={() => setEditingUser(null)}
                />
            )}
            {deletingUser && (
                <DeleteUserDialog
                    key={deletingUser.id}
                    user={deletingUser}
                    open
                    onClose={() => setDeletingUser(null)}
                />
            )}
        </>
    );
}

Users.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: users(),
        },
    ],
};
