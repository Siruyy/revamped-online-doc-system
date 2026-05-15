import { expect, test } from '@playwright/test';
import { visit } from './helpers';
import { loginAs } from './helpers/auth';

test('superadmin can use user and report management surfaces', async ({ page }) => {
    await loginAs(page, 'superadmin');

    await visit(page, '/superadmin/users');
    await expect(page.getByRole('heading', { name: 'Users' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Create staff' }).last()).toBeVisible();
    await expect(page.getByRole('link', { name: 'Export CSV' }).last()).toBeVisible();

    await visit(page, '/superadmin/reports?from=2026-01-01&to=2026-01-31');
    await expect(page.getByRole('heading', { name: 'System reports' })).toBeVisible();

    for (const linkName of ['Export requests CSV', 'Export payments CSV']) {
        const href = await page.getByRole('link', { name: linkName }).getAttribute('href');
        expect(href).not.toBeNull();

        const url = new URL(href ?? '', page.url());
        expect(url.searchParams.get('from')).toBe('2026-01-01');
        expect(url.searchParams.get('to')).toBe('2026-01-31');
    }
});

test('admin cannot open SuperAdmin report routes', async ({ page }) => {
    await loginAs(page, 'admin');

    const response = await page.goto('/superadmin/reports', { waitUntil: 'commit' });

    expect(response?.status()).toBe(403);
    await expect(page.getByText(/403|forbidden/i).first()).toBeVisible();
});
