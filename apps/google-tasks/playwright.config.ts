import { defineConfig, devices } from '@playwright/test';

/**
 * E2E smoke tests — no real Google OAuth. Server started via webServer;
 * see docs/TESTING.md.
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: 'line',
    use: {
        baseURL: 'http://127.0.0.1:8123',
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8123',
        url: 'http://127.0.0.1:8123/health',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
