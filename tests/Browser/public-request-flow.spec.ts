import { expect, test } from '@playwright/test';
import { visit } from './helpers';

const tinyPng = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
    'base64',
);

test('public requestor submits and tracks without an account', async ({ page }) => {
    await visit(page, '/');

    await page.getByRole('link', { name: /request document/i }).click();
    await expect(page).toHaveURL(/\/request-document$/);
    await expect(page.locator('input[type="password"]')).toHaveCount(0);

    await page.getByText('Special Order', { exact: true }).click();
    await page.getByRole('button', { name: /continue/i }).click();

    await page.getByLabel(/full name/i).fill('Public E2E Requestor');
    await page.getByLabel(/email/i).fill('public.e2e@example.com');
    await page.getByLabel(/contact number/i).fill('09990001234');
    await page.getByLabel(/id number/i).fill('PUBLIC-E2E');
    await page.getByLabel(/course/i).fill('BSIT');
    await page.getByLabel(/year level/i).fill('4');
    await page.getByLabel(/graduation \/ last semester/i).fill('2nd Sem 2025-2026');
    await page.getByLabel(/purpose/i).fill('E2E public request smoke test');
    await page.getByRole('button', { name: /continue/i }).click();

    const requirementInputs = page.locator('input[type="file"]');
    const requirementCount = await requirementInputs.count();

    for (let index = 0; index < requirementCount; index += 1) {
        await requirementInputs.nth(index).setInputFiles({
            name: `public-upload-${index}.png`,
            mimeType: 'image/png',
            buffer: tinyPng,
        });
    }

    await page.getByRole('button', { name: /continue/i }).click();
    await page.getByLabel(/payment method/i).fill('GCash');
    await page.getByLabel(/payment reference/i).fill(`PUBLIC-E2E-${Date.now()}`);
    await page.locator('input[type="file"]').setInputFiles({
        name: 'public-receipt.png',
        mimeType: 'image/png',
        buffer: tinyPng,
    });
    await page.getByRole('button', { name: /continue/i }).click();
    await page.getByRole('button', { name: /submit public request/i }).click();

    await expect(page.getByRole('heading', { name: /save this reference number/i })).toBeVisible();
    const referenceText = await page.locator('p.font-mono').textContent();
    expect(referenceText).toMatch(/^REQ-\d{4}-\d{6}$/);

    await visit(page, '/track-document');
    await page.getByLabel(/reference number/i).fill(referenceText ?? '');
    await page.getByRole('button', { name: /track document/i }).click();

    await expect(page.getByText(referenceText ?? '')).toBeVisible();
    await expect(page.getByText(/current stage/i)).toBeVisible();
});
