import { defineConfig, devices } from '@playwright/test';

const host = process.env.E2E_HOST ?? '127.0.0.1';
const appPort = process.env.E2E_APP_PORT ?? '8000';
const vitePort = process.env.E2E_VITE_PORT ?? '5173';
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://${host}:${appPort}`;
const seedCommand = process.env.E2E_REFRESH_DB === '1'
    ? 'php artisan migrate:fresh --force --seed --seeder=E2eSeeder && '
    : '';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? [['github'], ['html', { outputFolder: 'playwright-report', open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: [
        {
            command: `${seedCommand}php artisan serve --host=${host} --port=${appPort}`,
            url: `${baseURL}/up`,
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
        {
            command: `LARAVEL_BYPASS_ENV_CHECK=1 npm run dev -- --host ${host} --port ${vitePort}`,
            url: `http://${host}:${vitePort}/resources/js/app.js`,
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
    ],
});
