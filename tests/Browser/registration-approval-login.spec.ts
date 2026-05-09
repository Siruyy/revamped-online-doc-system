import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, login, logout } from './helpers';

test('student registration can be approved by SuperAdmin and then log in', async ({ page }) => {
    const token = Date.now();
    const email = `e2e.pending.${token}@example.com`;
    const studentId = `E2E-${token}`.slice(0, 50);

    await page.goto('/register');
    await page.getByLabel('Full Name').fill('E2E Pending Student');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Course').fill('BSIT');
    await page.getByLabel('Year Level').fill('4');
    await page.getByLabel('Student ID').fill(studentId);
    await page.getByLabel('Contact Number').fill('09990000000');
    await page.getByLabel('Password', { exact: true }).fill(E2E_PASSWORD);
    await page.getByLabel('Confirm Password').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Register' }).click();

    await expect(page.getByRole('heading', { name: 'Registration submitted' })).toBeVisible();

    await login(page, 'e2e.superadmin@example.com');
    await page.goto('/superadmin/users/pending');
    await expect(page.getByText(email)).toBeVisible();
    await page.locator('tr', { hasText: email }).getByRole('button', { name: 'Approve' }).click();
    await expect(page.getByText(email)).toHaveCount(0);
    await logout(page);

    await login(page, email);
    await expect(page.getByRole('heading', { name: /Welcome back/i })).toBeVisible();
});
