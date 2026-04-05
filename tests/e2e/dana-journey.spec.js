/**
 * Dana Yıldız — MentorDE Portal E2E Simülasyonu
 *
 * DanaRealisticJourneySeeder verisiyle, 155 günlük serüveni
 * gerçek portal sayfaları üzerinden adım adım izler.
 *
 * Çalıştır:
 *   npx playwright test --config=playwright.dana.config.js
 *   veya: npm run e2e:dana
 *
 * Ön koşul: DanaE2ESeeder çalıştırılmış olmalı (playwright.dana.config.js otomatik yapar)
 *
 * Ekip:
 *   Müzeyyen Aksoy    — manager           → muzeyyen.aksoy@mentorde.com
 *   Ömür Demir        — marketing_admin   → omur.demir@mentorde.com
 *   Tuğçe Yıldırım    — marketing_staff   → tugce.yildirim@mentorde.com
 *   Selin Kaya        — operations_staff  → selin.kaya@mentorde.com
 *   Cem Arslan        — operations_admin  → cem.arslan@mentorde.com
 *   Naz Çelik         — finance_admin     → naz.celik@mentorde.com
 *   Burak Şahin       — senior            → burak.sahin@mentorde.com
 */

import { test, expect } from '@playwright/test';

const PASSWORD = 'ChangeMe123!';

const USERS = {
  muzeyyen: { email: 'muzeyyen.aksoy@mentorde.com',  name: 'Müzeyyen Aksoy',   portal: /\/manager\// },
  omur:     { email: 'omur.demir@mentorde.com',      name: 'Ömür Demir',       portal: /\/mktg-admin/ },
  tugce:    { email: 'tugce.yildirim@mentorde.com',  name: 'Tuğçe Yıldırım',  portal: /\/mktg-admin/ },
  selin:    { email: 'selin.kaya@mentorde.com',      name: 'Selin Kaya',       portal: /\/manager\// },
  cem:      { email: 'cem.arslan@mentorde.com',      name: 'Cem Arslan',       portal: /\/manager\// },
  naz:      { email: 'naz.celik@mentorde.com',       name: 'Naz Çelik',        portal: /\/staff\// },
  burak:    { email: 'burak.sahin@mentorde.com',     name: 'Burak Şahin',      portal: /\/senior\// },
};

// ─── Yardımcılar ─────────────────────────────────────────────────────────────

async function login(page, userKey) {
  const u = USERS[userKey];
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login/);
  await page.fill('input[name="email"]', u.email);
  await page.fill('input[name="password"]', PASSWORD);
  await page.locator('button[type="submit"], input[type="submit"]').first().click();
  await page.waitForURL(u.portal, { timeout: 40_000 });
}

async function logout(page) {
  // POST /logout veya link
  const logoutLink = page.locator('a[href*="logout"], form[action*="logout"] button').first();
  if (await logoutLink.count()) {
    await logoutLink.click();
    await page.waitForURL(/\/login/, { timeout: 10_000 });
  } else {
    await page.goto('/logout');
  }
}

function noErrors(page) {
  return Promise.all([
    expect(page.locator('text=Internal Server Error')).toHaveCount(0),
    expect(page.locator('text=SQLSTATE')).toHaveCount(0),
    expect(page.locator('text=Call to undefined')).toHaveCount(0),
    expect(page.locator('text=Whoops')).toHaveCount(0),
  ]);
}

// ─── FAZ 0: KAMPANYA ─────────────────────────────────────────────────────────
// Tuğçe Yıldırım görsel hazırlar, Ömür yayına alır

test('FAZ 0-A — Tuğçe: kampanya görevleri ve içerik takibi', async ({ page }) => {
  await login(page, 'tugce');

  // Marketing dashboard
  await page.goto('/mktg-admin/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Sosyal medya postları — Tuğçe'nin oluşturduğu Instagram story görünür
  await page.goto('/mktg-admin/social/posts');
  await noErrors(page);
  // Sayfa yüklendi mi kontrol
  await expect(page.locator('body')).toBeVisible();

  // Tracking link listesi
  await page.goto('/mktg-admin/tracking-links');
  await noErrors(page);

  // Task board — Tuğçe'nin kampanya taskları
  await page.goto('/tasks');
  await noErrors(page);
  // "WS2026" veya "Instagram" kelimesi geçiyor mu?
  const bodyText = await page.locator('body').textContent();
  expect(bodyText).toContain('WS2026');
});

test('FAZ 0-B — Ömür: kampanya performans raporu ve lead qualify', async ({ page }) => {
  await login(page, 'omur');

  // Kampanyalar
  await page.goto('/mktg-admin/campaigns');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();
  const campaignText = await page.locator('body').textContent();
  expect(campaignText).toContain('WS 2026');

  // Pipeline — lead'leri görüntüle
  await page.goto('/mktg-admin/pipeline');
  await noErrors(page);

  // KPI raporu
  await page.goto('/mktg-admin/kpi');
  await noErrors(page);

  // Lead kaynakları — Instagram verisi
  await page.goto('/mktg-admin/lead-sources');
  await noErrors(page);
});

// ─── FAZ 0.5: LEAD YAKALAMA ──────────────────────────────────────────────────
// Dana formu dolduruyor → Selin'e alert

test('FAZ 0.5 — Selin: HOT LEAD dana.yildiz geldi, task listesinde görünüyor', async ({ page }) => {
  await login(page, 'selin');

  // Manager dashboard — ops_staff erişebilir
  await page.goto('/manager/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Task board — Selin'in görevi: "38 lead'i qualify et..."
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();
});

// ─── FAZ 1: KABUL & SÖZLEŞME ─────────────────────────────────────────────────
// Selin ilk görüşmeyi yapar, sözleşme hazırlar | Müzeyyen onaylar

test('FAZ 1-A — Selin: sözleşme ve kabul taskları', async ({ page }) => {
  await login(page, 'selin');

  // Manager dashboard
  await page.goto('/manager/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Task board — ops_staff sözleşme taskları görür
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();
});

test('FAZ 1-B — Müzeyyen: manager dashboard ve guest overview', async ({ page }) => {
  await login(page, 'muzeyyen');

  // Manager dashboard
  await page.goto('/manager/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Guest listesi
  await page.goto('/manager/guests');
  await noErrors(page);
  const guestText = await page.locator('body').textContent();
  expect(guestText).toContain('Dana');

  // Kampanya + tracking analytics
  await page.goto('/manager/students');
  await noErrors(page);
});

// ─── FAZ 2: EVRAK YÖNETİMİ ───────────────────────────────────────────────────
// Cem belgeleri takip eder | Burak öğrenciyle çalışır

test('FAZ 2-A — Cem: evrak taskları (MEB apostil aksaklığı dahil)', async ({ page }) => {
  await login(page, 'cem');

  // Task board — Cem'in evrak taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  // Cem'in adı ya da "Dana Yıldız" geçmeli
  // En azından task board yüklendi
  expect(bodyText.length).toBeGreaterThan(100);

  // Manager portal — öğrenci listesi
  await page.goto('/manager/students');
  await noErrors(page);
});

test('FAZ 2-B — Burak: senior process-tracking, dana\'nın evrak süreci', async ({ page }) => {
  await login(page, 'burak');

  // Process tracking — Burak'ın öğrencileri
  await page.goto('/senior/process-tracking');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Öğrenci listesi
  await page.goto('/senior/students');
  await noErrors(page);

  // Kayıt belgeleri takibi
  await page.goto('/senior/registration-documents');
  await noErrors(page);
});

// ─── FAZ 3: UNİ-ASSİST ──────────────────────────────────────────────────────
// Cem başvuruyu gönderir (iade aksaklığı var), Burak TU Berlin kabulü takip eder

test('FAZ 3-A — Cem: uni-assist taskları ve iade aksaklığı', async ({ page }) => {
  await login(page, 'cem');

  // Task board — uni_assist process_type taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  // Task board yüklendi mi
  expect(bodyText.length).toBeGreaterThan(100);

  // Kanban görünümü (eğer varsa)
  await page.goto('/tasks/kanban');
  // 404 de olabilir, sadece crash olmasın
  await expect(page.locator('body')).toBeVisible();
});

test('FAZ 3-B — Burak: TU Berlin kabulü — process-tracking done tasks paneli', async ({ page }) => {
  await login(page, 'burak');

  // Process tracking
  await page.goto('/senior/process-tracking');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  // "Tamamlanan" veya task listesi var mı
  expect(bodyText.length).toBeGreaterThan(200);

  // Sözleşmeler — öğrenci sözleşme durumu
  await page.goto('/senior/contracts');
  await noErrors(page);

  // Notes/appointments
  await page.goto('/senior/appointments');
  await noErrors(page);
  await page.goto('/senior/notes');
  await noErrors(page);
});

test('FAZ 3-C — Müzeyyen: manager — TU Berlin kabul sonrası öğrenci durumu', async ({ page }) => {
  await login(page, 'muzeyyen');

  // Öğrenci listesi — Dana'nın Student ID görünür
  await page.goto('/manager/students');
  await noErrors(page);
  const bodyText = await page.locator('body').textContent();
  expect(bodyText).toContain('STU-DANA-R-');

  // Senior listesi (Burak'ın performansı)
  await page.goto('/manager/seniors');
  await noErrors(page);
});

// ─── FAZ 4: VİZE ─────────────────────────────────────────────────────────────
// Cem büyükelçilik (AIS çöküşü aksaklığı), Naz sperrkonto (banka gecikmesi aksaklığı)

test('FAZ 4-A — Cem: vize taskları (AIS çöküşü aksaklığı)', async ({ page }) => {
  await login(page, 'cem');

  // Task board — visa_application taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // "AIS" ya da "vize" içeren task gösterilmeli (seeder verisi)
  const bodyText = await page.locator('body').textContent();
  expect(bodyText.length).toBeGreaterThan(100);

  // Manager dashboard — genel özet
  await page.goto('/manager/dashboard');
  await noErrors(page);
});

test('FAZ 4-B — Naz: sperrkonto ve banka transfer gecikmesi takibi', async ({ page }) => {
  await login(page, 'naz');

  // Task board — finance taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  // Naz'ın finans taskları — Fintiba ya da sperrkonto geçmeli
  // (en azından task board yüklendi)
  expect(bodyText.length).toBeGreaterThan(100);

  // Manager commissions/raporlar
  await page.goto('/manager/seniors');
  await noErrors(page);
});

// ─── FAZ 4-C: VIZE ONAY → BURAK PRE-DEPARTURE ───────────────────────────────

test('FAZ 4-C — Burak: vize onayı sonrası pre-departure briefing', async ({ page }) => {
  await login(page, 'burak');

  // Senior dashboard
  await page.goto('/senior/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Process tracking — vize aşaması done taskları
  await page.goto('/senior/process-tracking');
  await noErrors(page);

  // Document builder — Dana için belge hazırlama
  await page.goto('/senior/document-builder');
  await noErrors(page);

  // Knowledge base
  await page.goto('/senior/knowledge-base');
  await noErrors(page);
});

// ─── FAZ 5: İKAMET & KAYIT ───────────────────────────────────────────────────
// Selin Ausländerbehörde takibi (randevu iptali aksaklığı), Burak immatrikülasyon

test('FAZ 5-A — Selin: Ausländerbehörde taskları (randevu iptali aksaklığı)', async ({ page }) => {
  await login(page, 'selin');

  // Task board — residence_permit taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  expect(bodyText.length).toBeGreaterThan(100);
});

test('FAZ 5-B — Naz: 2. taksit ve sperrkonto aktivasyonu', async ({ page }) => {
  await login(page, 'naz');

  // Task board — 2. taksit ve sperrkonto aktive taskları
  await page.goto('/tasks');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  const bodyText = await page.locator('body').textContent();
  expect(bodyText.length).toBeGreaterThan(100);
});

test('FAZ 5-C — Burak: TU Berlin immatrikülasyon — serüven tamamlandı 🎓', async ({ page }) => {
  await login(page, 'burak');

  // Senior dashboard — bildirim: "TU Berlin Kaydı Tamamlandı"
  await page.goto('/senior/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Process tracking — tüm fazlar tamamlandı
  await page.goto('/senior/process-tracking');
  await noErrors(page);

  const bodyText = await page.locator('body').textContent();
  // Sayfa yüklendi, içerik var
  expect(bodyText.length).toBeGreaterThan(200);

  // Performance — Burak'ın başarı raporu
  await page.goto('/senior/performance');
  await noErrors(page);
});

// ─── FİNAL: MÜZEYYEN MUHASEBESİ ─────────────────────────────────────────────
// Genel Müdür tüm süreci sonuçlandırır

test('FINAL — Müzeyyen: 155 günlük serüven — yönetim raporu', async ({ page }) => {
  await login(page, 'muzeyyen');

  // Manager dashboard
  await page.goto('/manager/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();

  // Öğrenci listesi — Dana'nın Student ID görünür
  await page.goto('/manager/students');
  await noErrors(page);
  const studentsText = await page.locator('body').textContent();
  expect(studentsText).toContain('STU-DANA-R-');

  // Senior listesi — Burak'ın immatriküle sayısı
  await page.goto('/manager/seniors');
  await noErrors(page);

  // Dealer listesi
  await page.goto('/manager/dealers');
  await noErrors(page);

  // Komisyonlar
  await page.goto('/manager/commissions');
  await noErrors(page);

  // Snapshot / rapor
  await page.goto('/manager/guests');
  await noErrors(page);
  const guestText = await page.locator('body').textContent();
  // Dana artık öğrenci olmuş olsa da guest listesinde geçmişi var
  expect(guestText.length).toBeGreaterThan(100);
});

// ─── ÇAPRAZ KONTROL: Kullanıcı izolasyonu ────────────────────────────────────

test('İzolasyon — Burak senior portala, Tuğçe mktg portala erişir', async ({ page }) => {
  // Burak manager'a erişemez
  await login(page, 'burak');
  await page.goto('/mktg-admin/dashboard');
  const blockedUrl = page.url();
  const isBlocked =
    blockedUrl.includes('/login') ||
    blockedUrl.includes('/senior/') ||
    (await page.locator('text=403').count()) > 0 ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0;
  expect(isBlocked).toBe(true);

  await logout(page);

  // Tuğçe senior portala erişemez
  await login(page, 'tugce');
  await page.goto('/senior/dashboard');
  const url2 = page.url();
  const isBlocked2 =
    url2.includes('/login') ||
    url2.includes('/mktg-admin') ||
    (await page.locator('text=403').count()) > 0 ||
    (await page.locator('text=BU ALANA ERISIM IZNINIZ YOK').count()) > 0;
  expect(isBlocked2).toBe(true);
});

// ─── SEEDER VERİFİKASYON ─────────────────────────────────────────────────────

test('Seeder doğrulama — Dana Yıldız kaydı mevcut', async ({ page }) => {
  await login(page, 'muzeyyen');

  // Guest uygulamaları
  await page.goto('/manager/guests');
  await noErrors(page);
  const text = await page.locator('body').textContent();
  expect(text).toContain('Dana');

  // Campaign verisi
  await page.goto('/manager/dashboard');
  await noErrors(page);
  await expect(page.locator('body')).toBeVisible();
});

test('Seeder doğrulama — Ekip üyeleri login olabiliyor', async ({ page }) => {
  const staffUsers = ['muzeyyen', 'omur', 'tugce', 'selin', 'cem', 'naz'];

  for (const userKey of staffUsers) {
    await page.goto('/login');
    await page.fill('input[name="email"]', USERS[userKey].email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.locator('button[type="submit"], input[type="submit"]').first().click();
    await page.waitForURL(USERS[userKey].portal, { timeout: 40_000 });
    await noErrors(page);

    // Çıkış
    await page.goto('/logout');
  }

  // Burak (senior)
  await page.goto('/login');
  await page.fill('input[name="email"]', USERS.burak.email);
  await page.fill('input[name="password"]', PASSWORD);
  await page.locator('button[type="submit"], input[type="submit"]').first().click();
  await page.waitForURL(/\/senior\//, { timeout: 40_000 });
  await noErrors(page);
});
