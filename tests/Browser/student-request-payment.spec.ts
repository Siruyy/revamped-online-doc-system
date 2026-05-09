import { expect, test } from '@playwright/test';
import { login, logout, visit } from './helpers';

test('student submits a document request and uploads a payment receipt', async ({ page }) => {
    await login(page, 'e2e.student@example.com');
    await visit(page, '/student/requests/new');

    await page.getByText('Special Order', { exact: true }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByPlaceholder(/For scholarship application/).fill('E2E scholarship application');
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Submit Request' }).click();

    await expect(page.getByRole('heading', { name: 'Special Order' })).toBeVisible();
    await expect(page.getByText('Request submitted')).toBeVisible();

    await logout(page);
    await login(page, 'e2e.payment.student@example.com');
    await visit(page, '/student/payments');

    const payment = page.getByTestId('payment-card-E2E-PAYMENT-REQ');
    await expect(payment).toBeVisible();
    await payment.locator('select').selectOption('bank_transfer');
    await payment.getByPlaceholder('e.g. TXN-2026-XXXXXX').fill(`E2E-${Date.now()}`);
    await payment.locator('input[type="file"]').setInputFiles({
        name: 'receipt.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
            'base64',
        ),
    });
    await payment.getByRole('button', { name: 'Submit Receipt' }).click();

    await expect(page.getByText('Your receipt has been submitted and is under admin review')).toBeVisible();
});
