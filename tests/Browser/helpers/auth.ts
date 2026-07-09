import type { Page } from '@playwright/test';
import { login } from '../helpers';

export type RoleName = 'student' | 'admin' | 'department' | 'superadmin';

const accounts: Record<RoleName, { email: string }> = {
    student: { email: 'e2e.student@example.com' },
    admin: { email: 'e2e.admin@example.com' },
    // E2E seed uses the dean account for department clearance workflows.
    department: { email: 'e2e.dean@example.com' },
    superadmin: { email: 'e2e.superadmin@example.com' },
};

export async function loginAs(page: Page, role: RoleName): Promise<void> {
    await login(page, accounts[role].email);
}
