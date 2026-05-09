import { expect, test } from '@playwright/test';
import { login, logout } from './helpers';

test('admin approves request and payment, then departments sign clearance', async ({ page }) => {
    await login(page, 'e2e.admin@example.com');
    await page.goto('/admin/requests');
    await page.getByPlaceholder('Search ref, student, document…').fill('E2E-ADMIN-PENDING');
    await page.getByRole('button', { name: 'Apply' }).click();
    await page.locator('tr', { hasText: 'E2E-ADMIN-PENDING' }).getByRole('link', { name: /Open/ }).click();
    await page.getByRole('button', { name: 'Approve request' }).click();
    await expect(page.getByText(/approved · processing/i)).toBeVisible();

    await page.goto('/admin/payments?status=pending_approval');
    const payment = page.locator('article', { hasText: 'E2E-CLEARANCE-REQ' });
    await expect(payment).toBeVisible();
    await payment.getByRole('button', { name: 'Approve' }).click();
    await expect(payment.getByText(/Status:\s*approved/i)).toBeVisible();
    await logout(page);

    for (const [email, label] of [
        ['e2e.teacher@example.com', 'Teacher'],
        ['e2e.dean@example.com', 'Dean'],
        ['e2e.accounting@example.com', 'Accounting'],
        ['e2e.sao@example.com', 'SAO'],
    ] as const) {
        await login(page, email);
        await page.goto('/department/clearances?search=E2E-ADMIN');
        await page.locator('tr', { hasText: 'E2E-CLEARANCE-REQ' }).getByRole('link', { name: /Open/ }).click();
        await expect(page.getByRole('heading', { name: /Clearance E2E-CLEARANCE-REQ/ })).toBeVisible();
        await page.getByLabel(/Mark cleared/).fill(`${label} E2E clearance`);
        await page.getByRole('button', { name: 'Mark as cleared' }).click();
        await expect(page.getByText('You cannot act on this clearance for your department in its current state.')).toBeVisible();
        await logout(page);
    }
});
