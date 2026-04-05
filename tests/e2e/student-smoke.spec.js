import { test, expect } from '@playwright/test';

const creds = {
  email: process.env.E2E_STUDENT_EMAIL || 'student@mentorde.local',
  password: process.env.E2E_STUDENT_PASSWORD || 'ChangeMe123!',
};

async function loginAsStudent(page) {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);

  await page.fill('input[name="email"]', creds.email);
  await page.fill('input[name="password"]', creds.password);

  const submit = page.locator('button[type="submit"], input[type="submit"]').first();
  await submit.click();

  await page.waitForURL(/\/student\//, { timeout: 40000 });
}

test('student core pages smoke', async ({ page }) => {
  await loginAsStudent(page);

  const routes = [
    '/student/dashboard',
    '/student/registration',
    '/student/registration/documents',
    '/student/process-tracking',
    '/student/document-builder',
    '/student/appointments',
    '/student/messages',
    '/student/tickets',
    '/student/materials',
    '/student/contract',
    '/student/services',
    '/student/profile',
    '/student/settings',
  ];

  for (const route of routes) {
    await page.goto(route);
    await expect(page.locator('text=Internal Server Error')).toHaveCount(0);
    await expect(page.locator('text=404')).toHaveCount(0);
    await expect(page.locator('text=BU ALANA ERISIM IZNINIZ YOK')).toHaveCount(0);
  }
});
