# MentorDE E2E Browser Smoke Tests (Playwright)

## 1) Kurulum

```powershell
npm install
npm run e2e:install
```

## 2) Laravel sunucusunu başlat

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

## 3) Testleri çalıştır

### Tek rol
```powershell
npm run e2e:student
npm run e2e:guest
npm run e2e:dealer
npm run e2e:senior
npm run e2e:manager
npm run e2e:marketing
```

### Tüm roller birden
```powershell
npm run e2e:all
```

---

## Test dosyaları

| Dosya | Rol | Sayfa sayısı | Test sayısı |
|---|---|---|---|
| `tests/e2e/student-smoke.spec.js` | student | 13 | 1 |
| `tests/e2e/guest-smoke.spec.js` | guest | 9 | 3 |
| `tests/e2e/dealer-smoke.spec.js` | dealer | 10 | 4 |
| `tests/e2e/senior-smoke.spec.js` | senior | 17 | 4 |
| `tests/e2e/manager-smoke.spec.js` | manager | 9 | 5 |
| `tests/e2e/marketing-smoke.spec.js` | marketing_admin + staff | 21+5 | 6 |

---

## Her test neyi kontrol eder?

1. **Smoke** — Her sayfada `Internal Server Error`, `404`, `BU ALANA ERISIM IZNINIZ YOK` yok
2. **Key elements** — Sidebar visible; `Call to undefined`, `SQLSTATE` yok
3. **Cross-role izolasyon** — Her rol kendi dışındaki portale erişemez (login veya 403'e yönlenir)
4. **Admin-only** (marketing) — Admin sayfaları staff ile smoke pass geçer

---

## Opsiyonel ENV değişkenleri

```powershell
$env:E2E_BASE_URL        = 'http://127.0.0.1:8000'

$env:E2E_STUDENT_EMAIL   = 'student@mentorde.local'
$env:E2E_STUDENT_PASSWORD= 'ChangeMe123!'

$env:E2E_GUEST_EMAIL     = 'guest@mentorde.local'
$env:E2E_GUEST_PASSWORD  = 'ChangeMe123!'

$env:E2E_DEALER_EMAIL    = 'dealer@mentorde.local'
$env:E2E_DEALER_PASSWORD = 'ChangeMe123!'

$env:E2E_SENIOR_EMAIL    = 'seniorww@mentorde.local'
$env:E2E_SENIOR_PASSWORD = 'ChangeMe123!'

$env:E2E_MANAGER_EMAIL   = 'manager@mentorde.local'
$env:E2E_MANAGER_PASSWORD= 'ChangeMe123!'

$env:E2E_MKTG_ADMIN_EMAIL   = 'marketing.admin@mentorde.local'
$env:E2E_MKTG_ADMIN_PASSWORD= 'ChangeMe123!'
$env:E2E_MKTG_STAFF_EMAIL   = 'marketing.staff1@mentorde.local'
$env:E2E_MKTG_STAFF_PASSWORD= 'ChangeMe123!'
```

---

## Başarısız test debug

Hata ekran görüntüsü ve video `test-results/` altına kaydedilir (sadece başarısız testlerde).

```powershell
# Tek test debug için
playwright test tests/e2e/manager-smoke.spec.js --debug
```
