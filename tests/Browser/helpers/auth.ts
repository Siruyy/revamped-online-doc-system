import type { Page } from '@playwright/test';

export type RoleName = 'student' | 'admin' | 'department' | 'superadmin';

const accounts: Record<RoleName, { email: string; password: string }> = {
    student: { email: 'e2e.student@example.com', password: 'password' },
    admin: { email: 'e2e.admin@example.com', password: 'password' },
    department: { email: 'e2e.teacher@example.com', password: 'password' },
    superadmin: { email: 'e2e.superadmin@example.com', password: 'password' },
};

export async function loginAs(page: Page, role: RoleName): Promise<void> {
    const account = accounts[role];

    await page.goto('/login');
    await page.getByLabel('Email').fill(account.email);
    await page.getByLabel('Password').fill(account.password);
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForLoadState('networkidle');
}
