import { test, expect } from '@playwright/test';

const creds = {
  email: process.env.E2E_MANAGER_EMAIL || 'manager@mentorde.local',
  password: process.env.E2E_MANAGER_PASSWORD || 'ChangeMe123!',
};

async function loginAsManager(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', creds.email);
  await page.fill('input[name="password"]', creds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  // Manager redirects to /config after login (not /manager/)
  await page.waitForURL(/\/(config|manager)/, { timeout: 40000 });
}

function noErrors(page) {
  return Promise.all([
    expect(page.locator('text=Internal Server Error')).toHaveCount(0),
    expect(page.locator('text=404')).toHaveCount(0),
    expect(page.locator('text=BU ALANA ERISIM IZNINIZ YOK')).toHaveCount(0),
  ]);
}

test('manager core pages smoke', async ({ page }) => {
  await loginAsManager(page);

  const routes = [
    '/manager/dashboard',
    '/manager/guests',
    '/manager/students',
    '/manager/seniors',
    '/manager/dealers',
    '/manager/commissions',
    '/manager/contract-template',
    '/manager/theme',
    '/manager/requests',
  ];

  for (const route of routes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('manager dashboard has key elements', async ({ page }) => {
  await loginAsManager(page);
  await page.goto('/manager/dashboard');

  // Sidebar navigation visible
  await expect(page.locator('nav, aside, [class*="sidebar"]').first()).toBeVisible();

  // No PHP/Laravel error traces
  await expect(page.locator('text=Call to undefined')).toHaveCount(0);
  await expect(page.locator('text=SQLSTATE')).toHaveCount(0);
});

test('manager guests list loads', async ({ page }) => {
  await loginAsManager(page);
  await page.goto('/manager/guests');
  await noErrors(page);

  // Table or list container present
  await expect(page.locator('table, [class*="list"], [class*="grid"]').first()).toBeVisible();
});

test('manager students list loads', async ({ page }) => {
  await loginAsManager(page);
  await page.goto('/manager/students');
  await noErrors(page);

  await expect(page.locator('table, [class*="list"], [class*="grid"]').first()).toBeVisible();
});

test('manager cannot access student portal', async ({ page }) => {
  await loginAsManager(page);
  await page.goto('/student/dashboard');

  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/manager/') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});

test('manager cannot access guest portal', async ({ page }) => {
  await loginAsManager(page);
  await page.goto('/guest/dashboard');

  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/manager/') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});
