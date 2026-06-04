import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy as destroyUser,
    store as storeUser,
    update as updateUser,
} from '@/wayfinder/routes/users';
import { UserRole as UserRoleEnum } from '@/wayfinder/App/Enums/UserRole';
import type { App } from '@/wayfinder/types';
import type { ManagedUser, UserRole } from './types';

type CreateUserFormData =
    App.Http.Controllers.Users.UserManagementController.Store.Request & {
        role: UserRole;
    };

type EditUserFormData =
    App.Http.Controllers.Users.UserManagementController.Update.Request & {
        role: UserRole;
    };

export function CreateUserDialog({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<CreateUserFormData>({
        name: '',
        email: '',
        password: '',
        role: UserRoleEnum.Member,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.submit(storeUser(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New user</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) =>
                                form.setData('password', e.target.value)
                            }
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="role">Role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(role: UserRole) =>
                                form.setData('role', role)
                            }
                        >
                            <SelectTrigger
                                id="role"
                                className="w-full"
                                data-testid="role-select-trigger"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    value={UserRoleEnum.Member}
                                    data-testid="role-option-member"
                                >
                                    Member
                                </SelectItem>
                                <SelectItem
                                    value={UserRoleEnum.Admin}
                                    data-testid="role-option-admin"
                                >
                                    Administrator
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create user
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function EditUserDialog({
    user,
    open,
    onClose,
}: {
    user: ManagedUser;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm<EditUserFormData>({
        name: user.name,
        email: user.email,
        role:
            user.role?.name === UserRoleEnum.Admin
                ? UserRoleEnum.Admin
                : UserRoleEnum.Member,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.submit(updateUser(user.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['users'] });
            },
        });
    };

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit user</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="edit-user-name">Name</Label>
                        <Input
                            id="edit-user-name"
                            name="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-user-email">Email</Label>
                        <Input
                            id="edit-user-email"
                            name="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-user-role">Role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(role: UserRole) =>
                                form.setData('role', role)
                            }
                        >
                            <SelectTrigger
                                id="edit-user-role"
                                className="w-full"
                                data-testid="edit-role-select-trigger"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    value={UserRoleEnum.Member}
                                    data-testid="edit-role-option-member"
                                >
                                    Member
                                </SelectItem>
                                <SelectItem
                                    value={UserRoleEnum.Admin}
                                    data-testid="edit-role-option-admin"
                                >
                                    Administrator
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DeleteUserDialog({
    user,
    open,
    onClose,
}: {
    user: ManagedUser;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({});

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.submit(destroyUser(user.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                router.reload({ only: ['users'] });
            },
        });
    };

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete user?</DialogTitle>
                    <DialogDescription>
                        This will permanently remove {user.name}.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit}>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            Delete user
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
