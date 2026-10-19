export type AppRole = 'admin' | 'subscriber';

export type User = {
    id: number;
    name: string;
    email: string;
    app_role?: AppRole;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    can_manage_platform?: boolean;
};

export type TenantSummary = {
    id: string;
    name: string;
    slug: string;
    status: string;
    token_balance?: number;
    subscription_status?: string;
    monthly_subscription_cost?: number;
    role?: string | null;
    can_manage?: boolean;
};

export type Tenancy = {
    current: TenantSummary | null;
    tenants: TenantSummary[];
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
