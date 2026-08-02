# Dal Düzeni ve Test Yayını

Son güncelleme: 2026-08-03

Bu doküman **kodun canlıya nasıl çıktığını** anlatır. KAS/production altyapısının
teknik detayları için `docs/DEPLOYMENT.md`, sunucuya özgü tuzaklar için
`DEV_HANDBOOK.md`.

---

## 1. Neden bu düzen var?

Önceden tek dal vardı (`main`) ve `main`'e her push **anında**
`panel.mentorde.com`'a deploy oluyordu. Yani yarım kalmış bir iş, denenmemiş bir
değişiklik ya da bir yazım hatası doğrudan müşterinin gördüğü siteye düşüyordu.

Artık iki dal var:

| Dal | Rolü | Push edilince ne olur? |
|---|---|---|
| `develop` | **Çalışma dalı.** Günlük iş burada. | Şimdilik hiçbir şey. (Katman 2'de `test.mentorde.com`'a deploy edecek.) |
| `main` | **Yayın dalı.** Sadece yayına hazır kod. | GitHub Actions → `panel.mentorde.com` canlı deploy. |

---

## 2. Günlük akış

```bash
# Her zaman develop'ta çalış
git switch develop

# ... kod yaz ...

git add -A
git commit -m "feat(partner): ..."
git push
```

Lokalde dene: `php artisan serve` + `npm run dev` → `http://127.0.0.1:8000`

> MySQL Windows servisi olarak kayıtlı **değil**. Bilgisayarı yeni açtıysan önce
> Laragon'dan "Start All" yap, yoksa Laravel "connection refused" verir.

---

## 3. Yayına alma (develop → main)

Sadece test edilmiş, bitmiş iş için:

```bash
git switch main
git pull                      # main'de baskasi bir sey degistirdiyse
git merge develop
git push                      # ← BU DEPLOY'U BASLATIR
git switch develop            # hemen develop'a geri don
```

Push'tan sonra GitHub → Actions sekmesinden deploy'u izle (~3-5 dk).
Bittiğinde `https://panel.mentorde.com` üzerinde doğrula.

**Deploy başarısız olursa:** `https://panel.mentorde.com/system/last-error`
(webhook logu için `?file=deploy`).

---

## 4. Yanlışlıkla main'e commit koruması

`githooks/pre-commit` main dalında commit'i durdurur. Her klonda **bir kez**
aktifleştirilmeli (git hook'ları repo ile birlikte kopyalanmaz):

```bash
git config core.hooksPath githooks
```

Kurulu olduğunu doğrula:

```bash
git config --get core.hooksPath      # githooks yazmali
```

Acil canlı düzeltme için bilerek atlatmak istersen: `git commit --no-verify`.

`develop → main` merge'i hook'u tetiklemez (merge commit'inde `pre-commit`
çalışmaz), yani normal yayın akışı engellenmez.

---

## 5. GitHub tarafında yapılması gerekenler

Bunlar web arayüzünden, tek seferlik:

1. **Varsayılan dalı `develop` yap**
   Settings → General → Default branch → `develop`
   Böylece yeni klonlar ve Pull Request'ler `develop`'a düşer.

2. **`main`'i korumaya al** (opsiyonel ama önerilir)
   Settings → Rules → New ruleset → Target: `main`
   → "Restrict deletions" + "Block force pushes" işaretle.
   Yanlışlıkla dal silme / geçmiş ezme olasılığını kapatır.

---

## 6. Sıradaki adım — Katman 2: `test.mentorde.com`

Henüz kurulmadı. Planlanan:

- KAS'ta yeni subdomain (PHP 8.4) + ayrı FTP kullanıcı + ayrı MySQL DB
- `.github/workflows/deploy-staging.yml` — `develop` push'unda tetiklenir
  (mevcut `deploy.yml`'ın kopyası; `_deploy.php` göreli yol kullandığı için
  script değişikliği gerekmez, sadece secret'lar ve curl URL'i değişir)
- Staging `.env` farkları — **atlanırsa canlıya zarar verir**:
  `APP_ENV=staging` · `MAIL_MAILER=log` (Resend gerçek müşteriye yazmasın) ·
  Stripe test anahtarları · PostHog kapalı · `noindex` · ayrı DB
- Staging DB: production'ın **anonimleştirilmiş** kopyası (DSGVO gereği isim /
  e-posta / telefon sahte verilerle değiştirilir)
