import { expect, type Page } from '@playwright/test';

export const E2E_PASSWORD = 'password';

export async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).not.toHaveURL(/\/login$/);
}

export async function logout(page: Page): Promise<void> {
    await page.getByRole('button', { name: 'Log Out' }).first().click();
    await expect(page).toHaveURL(/\/login$/);
}
