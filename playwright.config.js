import { defineConfig, devices } from '@playwright/test';
import { config as dotenvConfig } from 'dotenv';

// .env.testing'den E2E credentials yükle (SQLite test ortamı, mentorde.local kullanıcıları)
dotenvConfig({ path: '.env.testing' });

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 120_000,           // 21 route testleri için 2dk — manager/mktg dashboard ağır
  expect: { timeout: 8_000 },
  fullyParallel: false,       // PHP dev server tek-thread
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8001',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    navigationTimeout: 40_000, // Manager dashboard çok sorgu yapar, 40s gerekli
  },

  // E2E test sunucusu: port 8001 (üretim 8000'e dokunmaz)
  // migrate:fresh → E2EUserSeeder → serve şeklinde sıralı çalışır
  webServer: {
    command: 'php -r "is_file(\'database/e2e.sqlite\') || touch(\'database/e2e.sqlite\');" && php artisan migrate:fresh --seed --seeder=E2EUserSeeder --env=testing --force --no-interaction && php artisan serve --port=8001 --env=testing',
    url: 'http://127.0.0.1:8001',
    reuseExistingServer: false,
    timeout: 90_000,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
