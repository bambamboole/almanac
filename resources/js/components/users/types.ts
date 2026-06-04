import type { App, Inertia } from '@/wayfinder/types';

export type UserRole = App.Enums.UserRole;

export type ManagedUser = Inertia.Pages.Users.Index['users'][number];

export type ManagedRole = NonNullable<ManagedUser['role']>;

export type Props = Pick<Inertia.Pages.Users.Index, 'users'>;
