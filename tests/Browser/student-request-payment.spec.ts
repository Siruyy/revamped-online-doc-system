import { expect, test } from '@playwright/test';
import { login, logout } from './helpers';

test('student submits a document request and uploads a payment receipt', async ({ page }) => {
    await login(page, 'e2e.student@example.com');
    await page.goto('/student/requests/new');

    await page
        .locator('div', { has: page.getByText('Special Order', { exact: true }) })
        .locator('input[type="checkbox"]')
        .first()
        .check();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByLabel(/Purpose/).fill('E2E scholarship application');
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Submit Request' }).click();

    await expect(page.getByText('Your document request has been submitted')).toBeVisible();
    await expect(page.getByText('E2E scholarship application')).toBeVisible();

    await logout(page);
    await login(page, 'e2e.payment.student@example.com');
    await page.goto('/student/payments');

    const payment = page.getByText('E2E-PAYMENT-REQ').locator('xpath=ancestor::div[contains(@class, "rounded-2xl")][1]');
    await expect(payment).toBeVisible();
    await payment.getByLabel('Payment Method').selectOption('bank_transfer');
    await payment.getByPlaceholder('e.g. TXN-2026-XXXXXX').fill(`E2E-${Date.now()}`);
    await payment.locator('input[type="file"]').setInputFiles({
        name: 'receipt.png',
        mimeType: 'image/png',
        buffer: Buffer.from('e2e receipt'),
    });
    await payment.getByRole('button', { name: 'Submit Receipt' }).click();

    await expect(page.getByText('Your receipt has been submitted and is under admin review')).toBeVisible();
});
