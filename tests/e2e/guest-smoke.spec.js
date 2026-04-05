import { test, expect } from '@playwright/test';

const creds = {
  email: process.env.E2E_GUEST_EMAIL || 'guest@mentorde.local',
  password: process.env.E2E_GUEST_PASSWORD || 'ChangeMe123!',
};

async function loginAsGuest(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', creds.email);
  await page.fill('input[name="password"]', creds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  await page.waitForURL(/\/guest\//, { timeout: 40000 });
}

function noErrors(page) {
  return Promise.all([
    expect(page.locator('text=Internal Server Error')).toHaveCount(0),
    expect(page.locator('text=404')).toHaveCount(0),
    expect(page.locator('text=BU ALANA ERISIM IZNINIZ YOK')).toHaveCount(0),
  ]);
}

test('guest core pages smoke', async ({ page }) => {
  await loginAsGuest(page);

  const routes = [
    '/guest/dashboard',
    '/guest/registration/form',
    '/guest/registration/documents',
    '/guest/profile',
    '/guest/services',
    '/guest/tickets',
    '/guest/messages',
    '/guest/contract',
    '/guest/settings',
  ];

  for (const route of routes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('guest dashboard has key elements', async ({ page }) => {
  await loginAsGuest(page);
  await page.goto('/guest/dashboard');

  // Sidebar navigation visible
  await expect(page.locator('nav, aside, [class*="sidebar"]').first()).toBeVisible();

  // No PHP/Laravel error traces
  await expect(page.locator('text=Call to undefined')).toHaveCount(0);
  await expect(page.locator('text=SQLSTATE')).toHaveCount(0);
});

test('guest cannot access student portal', async ({ page }) => {
  await loginAsGuest(page);
  await page.goto('/student/dashboard');

  // Must redirect away or show access denied — not the student dashboard
  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/guest/') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});
