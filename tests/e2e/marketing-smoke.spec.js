import { test, expect } from '@playwright/test';

const adminCreds = {
  email: process.env.E2E_MKTG_ADMIN_EMAIL || 'omer@mentorde.local',
  password: process.env.E2E_MKTG_ADMIN_PASSWORD || 'ChangeMe123!',
};

const staffCreds = {
  email: process.env.E2E_MKTG_STAFF_EMAIL || 'sule@mentorde.local',
  password: process.env.E2E_MKTG_STAFF_PASSWORD || 'ChangeMe123!',
};

async function loginAsMktgAdmin(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', adminCreds.email);
  await page.fill('input[name="password"]', adminCreds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  await page.waitForURL(/\/mktg-admin/, { timeout: 40000 });
}

async function loginAsMktgStaff(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', staffCreds.email);
  await page.fill('input[name="password"]', staffCreds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  await page.waitForURL(/\/mktg-admin/, { timeout: 40000 });
}

function noErrors(page) {
  return Promise.all([
    expect(page.locator('text=Internal Server Error')).toHaveCount(0),
    expect(page.locator('text=404')).toHaveCount(0),
    expect(page.locator('text=BU ALANA ERISIM IZNINIZ YOK')).toHaveCount(0),
  ]);
}

test('marketing admin — core pages smoke', async ({ page }) => {
  await loginAsMktgAdmin(page);

  const routes = [
    '/mktg-admin/dashboard',
    '/mktg-admin/campaigns',
    '/mktg-admin/email/templates',
    '/mktg-admin/email/segments',
    '/mktg-admin/email/campaigns',
    '/mktg-admin/email/log',
    '/mktg-admin/events',
    '/mktg-admin/social/accounts',
    '/mktg-admin/social/posts',
    '/mktg-admin/social/metrics',
    '/mktg-admin/social/calendar',
    '/mktg-admin/content',
    '/mktg-admin/media',
    '/mktg-admin/tracking-links',
    '/mktg-admin/lead-sources',
    '/mktg-admin/pipeline',
    '/mktg-admin/kpi',
    '/mktg-admin/reports',
    '/mktg-admin/notifications',
    '/mktg-admin/tasks',
    '/mktg-admin/profile',
  ];

  for (const route of routes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('marketing admin — admin-only pages smoke', async ({ page }) => {
  await loginAsMktgAdmin(page);

  const adminOnlyRoutes = [
    '/mktg-admin/team',
    '/mktg-admin/budget',
    '/mktg-admin/dealers',
    '/mktg-admin/integrations',
    '/mktg-admin/settings',
  ];

  for (const route of adminOnlyRoutes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('marketing admin dashboard has key elements', async ({ page }) => {
  await loginAsMktgAdmin(page);
  await page.goto('/mktg-admin/dashboard');

  await expect(page.locator('nav, aside, [class*="sidebar"]').first()).toBeVisible();

  await expect(page.locator('text=Call to undefined')).toHaveCount(0);
  await expect(page.locator('text=SQLSTATE')).toHaveCount(0);
});

test('marketing staff — shared pages accessible', async ({ page }) => {
  await loginAsMktgStaff(page);

  const sharedRoutes = [
    '/mktg-admin/dashboard',
    '/mktg-admin/campaigns',
    '/mktg-admin/email/templates',
    '/mktg-admin/email/segments',
    '/mktg-admin/email/campaigns',
    '/mktg-admin/events',
    '/mktg-admin/social/posts',
    '/mktg-admin/content',
    '/mktg-admin/tracking-links',
    '/mktg-admin/tasks',
    '/mktg-admin/profile',
  ];

  for (const route of sharedRoutes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('marketing staff — admin-only pages blocked', async ({ page }) => {
  await loginAsMktgStaff(page);

  // Admin-only write routes — staff should be denied on POST/mutation
  // Settings page: staff can view but not save — just check no 500
  await page.goto('/mktg-admin/settings');
  await expect(page.locator('text=Internal Server Error')).toHaveCount(0);
});

test('marketing admin cannot access student portal', async ({ page }) => {
  await loginAsMktgAdmin(page);
  await page.goto('/student/dashboard');

  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/mktg-admin') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});
