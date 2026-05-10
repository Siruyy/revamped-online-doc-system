import { expect, test, type Page } from '@playwright/test';
import { loginAs, type RoleName } from './helpers/auth';

const viewports = [
    { name: 'small-phone', width: 375, height: 812 },
    { name: 'large-phone', width: 430, height: 932 },
    { name: 'tablet', width: 768, height: 1024 },
];

const publicRoutes = ['/', '/login', '/register'];

const roleRoutes: Record<RoleName, string[]> = {
    student: ['/student/dashboard', '/student/requests', '/student/payments', '/student/clearance', '/student/faq'],
    admin: [
        '/admin/dashboard',
        '/admin/requests',
        '/admin/payments',
        '/admin/clearances',
        '/admin/document-types',
        '/admin/settings/payment-profile',
    ],
    department: ['/department/dashboard', '/department/clearances', '/department/faq'],
    superadmin: ['/superadmin/dashboard', '/superadmin/users', '/superadmin/users/pending', '/superadmin/logs', '/superadmin/reports'],
};

async function visitReady(page: Page, route: string): Promise<void> {
    await page.goto(route, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
}

async function expectNoHorizontalOverflow(page: Page): Promise<void> {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
}

async function expectPrimaryTouchTargets(page: Page): Promise<void> {
    const offenders = await page.evaluate(() => {
        const interactive = Array.from(document.querySelectorAll('button, a, input, select, textarea'));

        return interactive
            .map((element) => {
                const rect = element.getBoundingClientRect();
                const checkVisibility = (
                    element as Element & {
                        checkVisibility?: (options?: { checkOpacity?: boolean; checkVisibilityCSS?: boolean }) => boolean;
                    }
                ).checkVisibility;
                const inViewport = rect.bottom > 0 && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;
                const visible = checkVisibility
                    ? checkVisibility.call(element, { checkOpacity: true, checkVisibilityCSS: true }) && inViewport
                    : rect.width > 0 && rect.height > 0 && inViewport;
                const text = (element.textContent || element.getAttribute('aria-label') || element.getAttribute('name') || '').trim();

                return {
                    tag: element.tagName.toLowerCase(),
                    text,
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    visible,
                    inlineTextLink: element.tagName.toLowerCase() === 'a' && rect.height < 30 && text.length > 12,
                };
            })
            .filter((item) => item.visible)
            .filter((item) => !item.inlineTextLink)
            .filter((item) => item.width < 44 || item.height < 44)
            .slice(0, 10);
    });

    expect(offenders).toEqual([]);
}

for (const viewport of viewports) {
    test.describe(`responsive public ${viewport.name}`, () => {
        test.use({ viewport: { width: viewport.width, height: viewport.height } });

        for (const route of publicRoutes) {
            test(`${route} has no horizontal overflow`, async ({ page }) => {
                await visitReady(page, route);
                await expectNoHorizontalOverflow(page);
            });
        }
    });

    for (const [role, routes] of Object.entries(roleRoutes) as [RoleName, string[]][]) {
        test.describe(`responsive ${role} ${viewport.name}`, () => {
            test.use({ viewport: { width: viewport.width, height: viewport.height } });

            test.beforeEach(async ({ page }) => {
                await loginAs(page, role);
            });

            for (const route of routes) {
                test(`${route} has no horizontal overflow`, async ({ page }) => {
                    await visitReady(page, route);
                    await expectNoHorizontalOverflow(page);
                });
            }
        });
    }
}

test.describe('mobile interaction guardrails', () => {
    test.use({ viewport: { width: 375, height: 812 } });

    test('student navigation exposes accessible mobile controls', async ({ page }) => {
        await loginAs(page, 'student');
        await visitReady(page, '/student/dashboard');

        await page.getByRole('button', { name: /navigation menu/i }).click();
        await expect(page.getByRole('link', { name: /my requests/i })).toBeVisible();
    });

    test('admin navigation exposes accessible mobile controls', async ({ page }) => {
        await loginAs(page, 'admin');
        await visitReady(page, '/admin/dashboard');

        await page.getByRole('button', { name: /navigation menu/i }).click();
        await expect(page.getByRole('link', { name: /requests/i })).toBeVisible();
    });

    test('student dashboard primary controls meet touch target minimums', async ({ page }) => {
        await loginAs(page, 'student');
        await visitReady(page, '/student/dashboard');
        await expectPrimaryTouchTargets(page);
    });
});
