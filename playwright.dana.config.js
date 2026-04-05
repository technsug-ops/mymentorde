import { defineConfig, devices } from '@playwright/test';
import { config as dotenvConfig } from 'dotenv';

dotenvConfig({ path: '.env.testing' });

export default defineConfig({
  testDir: './tests/e2e',
  testMatch: '**/dana-journey.spec.js',
  timeout: 120_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:8002',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    navigationTimeout: 40_000,
  },

  // Port 8002 — ana E2E sunucusuna (8001) dokunmaz
  // DanaE2ESeeder = E2EUserSeeder + DanaRealisticJourneySeeder
  // Aynı e2e.sqlite dosyası kullanılır (migrate:fresh sıfırlar)
  webServer: {
    command: 'php -r "is_file(\'database/e2e.sqlite\') || touch(\'database/e2e.sqlite\');" && php artisan migrate:fresh --seed --seeder=DanaE2ESeeder --env=testing --force --no-interaction && php artisan serve --port=8002 --env=testing',
    url: 'http://127.0.0.1:8002',
    reuseExistingServer: false,
    timeout: 120_000,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
