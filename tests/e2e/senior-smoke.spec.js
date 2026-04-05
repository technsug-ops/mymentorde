import { test, expect } from '@playwright/test';

const creds = {
  email: process.env.E2E_SENIOR_EMAIL || 'seniorww@mentorde.local',
  password: process.env.E2E_SENIOR_PASSWORD || 'ChangeMe123!',
};

async function loginAsSenior(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', creds.email);
  await page.fill('input[name="password"]', creds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  await page.waitForURL(/\/senior\//, { timeout: 40000 });
}

function noErrors(page) {
  return Promise.all([
    expect(page.locator('text=Internal Server Error')).toHaveCount(0),
    expect(page.locator('text=404')).toHaveCount(0),
    expect(page.locator('text=BU ALANA ERISIM IZNINIZ YOK')).toHaveCount(0),
  ]);
}

test('senior core pages smoke', async ({ page }) => {
  await loginAsSenior(page);

  const routes = [
    '/senior/dashboard',
    '/senior/students',
    '/senior/appointments',
    '/senior/messages',
    '/senior/tickets',
    '/senior/vault',
    '/senior/process-tracking',
    '/senior/document-builder',
    '/senior/materials',
    '/senior/knowledge-base',
    '/senior/notes',
    '/senior/performance',
    '/senior/contracts',
    '/senior/services',
    '/senior/registration-documents',
    '/senior/profile',
    '/senior/settings',
  ];

  for (const route of routes) {
    await page.goto(route);
    await noErrors(page);
  }
});

test('senior dashboard has key elements', async ({ page }) => {
  await loginAsSenior(page);
  await page.goto('/senior/dashboard');

  // Sidebar navigation visible
  await expect(page.locator('nav, aside, [class*="sidebar"]').first()).toBeVisible();

  // No PHP/Laravel error traces
  await expect(page.locator('text=Call to undefined')).toHaveCount(0);
  await expect(page.locator('text=SQLSTATE')).toHaveCount(0);
});

test('senior cannot access manager portal', async ({ page }) => {
  await loginAsSenior(page);
  await page.goto('/manager/dashboard');

  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/senior/') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});

test('senior cannot access student portal', async ({ page }) => {
  await loginAsSenior(page);
  await page.goto('/student/dashboard');

  const url = page.url();
  const isBlocked =
    url.includes('/login') ||
    url.includes('/senior/') ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0 ||
    (await page.locator('text=403').count()) > 0;

  expect(isBlocked).toBe(true);
});
