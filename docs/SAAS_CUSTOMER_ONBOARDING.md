# SaaS Müşteri Onboarding Playbook

> **Hedef kitle:** MentorDE platform sahibi (sen) — yeni bir SaaS müşterisini (company) ilk kez sisteme eklerken adım-adım rehber.
>
> **Süre:** Tipik bir onboarding **15-30 dakika**. (Self-service signup flow v2'ye ertelendi; ilk müşterilerde manuel ekleme tek yol.)

---

## Ön hazırlık (müşteriden bilgi al)

| Bilgi | Format | Notu |
|---|---|---|
| Şirket adı | "Acme Eğitim Danışmanlık Ltd." | Resmi unvan, faturada görünür |
| Şirket kodu | `acme` veya `acme-edu` | Kısa, lowercase, URL-safe; sonradan değişmesi zor |
| Tier (paket) | Solo / Büyüyen / Kurumsal | Müşteri ne istiyor? |
| Yıllık mı aylık mı? | aylık veya yıllık | Yıllık → %20 indirim |
| Admin email | `info@acme.com` | İlk manager kullanıcı |
| Admin ad-soyad | "Mehmet Yılmaz" | Profil için |
| Logo (opsiyonel) | PNG/SVG | Branding sonrası eklenir |

---

## Adım 1 — Company kaydı oluştur (DB)

Şu an manager UI'sında "yeni company" formu yok (v1 single-tenant odaklı). **Tinker** ile oluştur:

```bash
php artisan tinker --execute="
\$c = \App\Models\Company::create([
    'name'      => 'Acme Eğitim Danışmanlık Ltd.',
    'code'      => 'acme',
    'is_active' => true,
    'enabled_modules' => ['core'], // tier'a göre 2. adımda güncelleyeceğiz
    'doc_request_monthly_limit' => null, // null = sınırsız (premium)
]);
echo 'Company id: ' . \$c->id . PHP_EOL;
"
```

**Notlar:**
- `code` alanını sonradan değiştirmek zor — URL'lerde, dealer kodlarında, vb. kullanılır
- `is_active=true` olmazsa kullanıcılar login olamaz

---

## Adım 2 — Tier'a göre modülleri aktif et

`/manager/companies/modules` sayfasından (Manager portal sidebar → "🧩 SaaS Modül Yönetimi"):

### Solo (€199/ay) — minimal
- ✅ core (zorunlu)
- ✅ ai_labs (basit AI asistan)
- Quota: `doc_request_monthly_limit = 25`

→ `Solo preset` butonuna basabilirsin (eğer eklediysek), yoksa elle seç + Kaydet.

### Büyüyen (€499/ay) — popüler
- ✅ core
- ✅ booking
- ✅ dam
- ✅ content_hub
- ✅ multi_provider_ai
- ✅ doc_builder_ai
- ✅ ai_labs
- ✅ contracts_hub (sözleşme imza)
- ✅ marketing_admin
- Quota: `doc_request_monthly_limit = 200`

→ `Gold preset` butonu uygun.

### Kurumsal (€999/ay) — sınırsız
- ✅ Tüm 14 modül
- Quota: `doc_request_monthly_limit = null` (sınırsız)

→ `Premium preset` butonu uygun.

---

## Adım 3 — İlk admin kullanıcı (Manager) oluştur

İki yol:

### Yol A — Manager UI (önerilen)

Şu an SEN giriş yapmış durumdaysan: **`/manager/staff/create`** sayfasından ekle.

⚠ **Sorun:** UI muhtemelen senin company_id'ni varsayılan olarak alır. Yeni müşterinin company_id'si farklı.

### Yol B — Tinker (güvenli)

```bash
php artisan tinker --execute="
\$company = \App\Models\Company::where('code', 'acme')->first();
\$user = \App\Models\User::create([
    'name'              => 'Mehmet Yılmaz',
    'email'             => 'info@acme.com',
    'password'          => Hash::make('GeçiciSifre123!'), // ilk girişte değiştirsin
    'role'              => \App\Models\User::ROLE_MANAGER,
    'company_id'        => \$company->id,
    'is_active'         => true,
    'email_verified_at' => now(),
]);
echo 'User id: ' . \$user->id . ' / role: ' . \$user->role . PHP_EOL;
"
```

---

## Adım 4 — (Opsiyonel) Branding

Şu an MentorDE marka rengi/logo'su tüm portallarda gösteriliyor. White-label (kendi marka) **Kurumsal** tier'da var.

### Logo

Şu an logo upload UI'sı yok — manuel yol:
1. Müşteriden PNG/SVG iste (200×60px civarı)
2. `public/img/brands/{company_code}.png` olarak yükle
3. `config/brand.php` veya company-level setting → ileride yapılır (v2)

**MVP için:** MentorDE logo'su kalır; müşteri özel logo yatırımı yapacaksa ileride white-label setup yapılır.

### Kendi domain (opsiyonel — Kurumsal)

Müşteri kendi `panel.acme.com` ile erişmek isterse:
1. Müşteri DNS'inde `panel.acme.com` → `panel.mentorde.com` (CNAME)
2. KAS panelinde domain alias ekle
3. SSL sertifikası al
4. Laravel `APP_URL` veya company-level `custom_domain` field'i (v2)

---

## Adım 5 — Müşteriye welcome email gönder

Manuel email taslağı:

```
Konu: MentorDE'ye Hoş Geldiniz — Hesabınız Hazır 🎯

Merhaba Mehmet Bey,

MentorDE platformuna hoş geldiniz. Acme Eğitim Danışmanlık Ltd. hesabınız aktif edildi.

🔐 Giriş bilgileriniz:
URL:     https://panel.mentorde.com/login
Email:   info@acme.com
Şifre:   GeçiciSifre123! (ilk girişte değiştirin lütfen)

📦 Aktif paket: Büyüyen Ekip (€499/ay)
Bu pakette aktif olan modüller:
- Core platform (Aday + Öğrenci + Senior portal)
- AI Asistan (NotebookLM-benzeri bilgi havuzu)
- Marka Kütüphanesi (DAM)
- Doküman Oluşturucu (AI destekli)
- Marketing Admin (drip kampanya)
- Sözleşmeler Hub
- ... (tier'a göre modül listesi)

📚 İlk gün rehberi:
1. Manager Dashboard'unuza giriş yapın
2. Belge kategorilerini gözden geçirin: /manager/document-categories
3. İlk senior kullanıcıyı ekleyin: /manager/staff/create
4. Test bir aday başvurusu oluşturun: /apply

Soru olursa: WhatsApp +49 152 03253691

Halil Aktas
Horizon STS GmbH (Geschäftsführer)
```

---

## Adım 6 — Smoke test (10 dakika)

Müşteriye gönderdiğin login bilgileri ile **kendi browser'ında** dene:

1. Login → manager dashboard açılıyor
2. `/manager/companies/modules` → kendi company'sini görüyor (sadece kendininkini, başkasınınki sızmıyor)
3. `/manager/staff/create` → senior ekleyebiliyor (company_id otomatik kendi company'si)
4. Yeni senior'ın email'iyle login → senior portal açılıyor

**⚠ KRİTİK:** Cross-tenant data isolation testi! Müşteri A'nın manager'ı, müşteri B'nin verisini görmemelidir. Şu an sistem her DB query'sinde `company_id` filtreliyor; ama yeni eklenen feature'larda bunu doğrula.

---

## Adım 7 — Faturalandırma (manuel)

Stripe Subscriptions v1'de yok. İlk müşterilerde:

1. Müşteriye **manuel fatura** gönder (Word/PDF)
2. **Banka transferi** veya PayPal ile tahsilat
3. Tahsilat aldıktan sonra `companies.is_active=true` veya quota'yı uygun set et
4. Aylık 1'inde manuel fatura — **kalendere reminder kur** (Google Calendar'da `Acme — fatura` her ayın 1'i)

**v2 için:** Stripe Subscriptions entegrasyonu (Booking Phase 5* ile aynı altyapı).

---

## Adım 8 — Customer success takibi

İlk 14 gün boyunca:
- Hafta 1: WhatsApp'tan "ihtiyacın var mı" check-in
- Hafta 2: kullanım analitiği bak (PostHog'da `company_id` filtrele) — login sayısı, lead create, doc upload
- Hafta 3: feedback al + churn risk değerlendirmesi

PostHog'da kullanım metrikleri:
- Property: `company_id={Company id}`
- Sıkça gerçekleşmesi gereken event'ler: `lead_created`, `lead_qualified`, `lead_converted`, `cta_clicked`
- Eğer 14 günde hiç event yoksa → müşteri kullanmıyor → outreach yap

---

## Sorun giderme

### Müşteri login olamıyor
1. `companies.is_active=true` mı?
2. `users.is_active=true` ve `email_verified_at != null` mı?
3. Email doğru mu, şifre reset gönder: `/forgot-password`

### Müşteri belirli bir modülü göremiyor
1. `/manager/companies/modules` → o modül company için aktif mi?
2. Cache temizleme: `ModuleAccess::flushCache($companyId)` (otomatik kayıt anında temizleniyor ama emin ol)
3. Browser hard refresh (Ctrl+Shift+R)

### Müşteri quota'ya takıldı
- doc_request: `companies.doc_request_monthly_limit` artır veya null yap
- Aylık reset: 1'inde otomatik (created_at filtresi)

### Müşteri kendi company'sinden başkasının verisini görüyor
**KRİTİK SECURITY:** Hemen bana ileti, yeni bir feature'da `company_id` filtresi eksik kalmış demektir. Hızlı patch gerekir.

---

## Onboarding checklist (tek bakışta)

- [ ] Müşteri bilgilerini topla (şirket adı, kod, tier, admin email)
- [ ] Tinker ile `Company::create(...)` çalıştır
- [ ] `/manager/companies/modules` → tier preset uygula
- [ ] Tinker ile manager user oluştur (geçici şifre)
- [ ] (Opsiyonel) Logo yükle
- [ ] Welcome email gönder (yukarıdaki taslak)
- [ ] Smoke test: kendi tarayıcında 5 dk dene
- [ ] Cross-tenant isolation kontrol et (başka company verisini görmüyor olmalı)
- [ ] Manuel fatura gönder
- [ ] Calendar reminder kur (aylık fatura için)
- [ ] Hafta 1/2/3 check-in plana al

---

## v2 için notlar (otomatik onboarding)

- **Self-service signup** `/saas/signup` route → company + admin user otomatik
- **Stripe Checkout** + Subscriptions → kart ile aylık otomatik tahsilat
- **14 gün trial** → `companies.trial_ends_at` field
- **Onboarding wizard** → giriş sonrası first-run setup (logo, ekip, modül seçimi)
- **Self-service modül upgrade** → "Premium'a yükselt" butonu Stripe ile
- **DPA download** → DSGVO B2B için sub-processor sözleşmesi PDF
