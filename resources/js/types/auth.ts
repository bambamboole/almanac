import type { Permission } from '@/types/permissions';
import type { App, Inertia } from '@/wayfinder/types';

export type UserRole = {
    id: number;
    name: App.Enums.UserRole;
    permissions: Permission[];
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    role: UserRole | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = Inertia.Pages.Settings.Security['passkeys'][number];
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
