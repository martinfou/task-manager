import { test, expect } from '@playwright/test';

test('health endpoint returns ok JSON', async ({ request }) => {
    const res = await request.get('/health');
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body).toMatchObject({ status: 'ok' });
    expect(typeof body.app).toBe('string');
});

test('login page loads (email auth, no OAuth required)', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByLabel(/email/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /^log in$/i })).toBeVisible();
});
