# MentorDE / DGmarkt — Bayi & SaaS Hiyerarşisi

> Son güncelleme: 19 Haziran 2026 · Faz 1-2-3a canlı (commit `dde57a8` → `9240062`)

Bu belge, platformun **SaaS kontrol düzlemi** + **2 seviyeli bayi ağı** + **white-label mini-site**
yapısını şematik olarak gösterir.

---

## 1. Genel Hiyerarşi

```
┌─────────────────────────────────────────────────────────────────────────┐
│  KONTROL DÜZLEMİ (SaaS satıcısı)                                          │
│                                                                           │
│   👑 PLATFORM OWNER  (owner@mentorde.com · DGmarkt)                       │
│      • tier / modül aç-kapa · faturalama · sağlık skoru · duyuru          │
│      • GÖRÜR: sadece agregat (öğrenci sayısı, MRR…) → fiyatlandırma       │
│      • GÖREMEZ: tenant kişisel verisi · impersonation KAPALI              │
└───────────────────────────────┬───────────────────────────────────────────┘
                                 │ kiralar / yönetir (operasyona karışmaz)
        ┌────────────────────────┼────────────────────────┐
        ▼                        ▼                        ▼
   ┌─────────┐            ┌─────────────┐           ┌─────────┐
   │ Tenant  │            │  TENANT:    │           │ Tenant  │
   │ Firma B │            │  MentorDE   │           │ Firma C │
   └─────────┘            │ (company=1) │           └─────────┘
   (izole)                └──────┬──────┘            (izole)
                                 │
        ┌────────────────────────┼─────────────────────────────────┐
        ▼                        ▼                                  ▼
   ┌──────────┐          ┌──────────────┐                  ┌────────────────┐
   │ 🧑‍💼 MANAGER │◀────────│  OPERASYON    │                  │ 🎓 ÖĞRENCİ/ADAY │
   │ (admin)  │  yönetir │  (Mentorde    │   data toplanır  │  (guest →      │
   │          │─────────▶│   paneli)     │◀─────────────────│   student)     │
   └────┬─────┘          └──────────────┘                  └────────────────┘
        │ oluşturur / tier + override + mini-site onayı
        │
        │   ════════ BAYİ AĞI (2 SEVİYE) ════════
        ▼
   ┌────────────────────────────────────────────────────────────┐
   │ 🏢 BÖLGE BAYİSİ  (parent_dealer_id = NULL)                  │
   │    • kendi alt bayisini oluşturur (/dealer/sub-dealers)     │
   │    • alt bayilerinin LEAD'lerini görür (tam detay)          │
   │    • mini-site: /p/{slug} (kendi logo + renk)               │
   │    • KAZANÇ: kendi getirisi + alt getirisinden OVERRIDE     │
   └───────────────┬───────────────────────┬────────────────────┘
                   │ oluşturur             │ oluşturur
                   ▼                       ▼
        ┌────────────────────┐   ┌────────────────────┐
        │ 🤝 ALT BAYİ #1      │   │ 🤝 ALT BAYİ #2      │
        │ (parent = bölge)   │   │ (parent = bölge)   │
        │ • kendi lead'i     │   │ • kendi lead'i     │
        │ • kendi kazancı    │   │ • kendi kazancı    │
        │ • mini-site /p/..  │   │ • mini-site /p/..  │
        │ • alt AÇAMAZ ⛔     │   │ • alt AÇAMAZ ⛔     │
        └─────────┬──────────┘   └─────────┬──────────┘
                  │ yönlendirir            │ yönlendirir
                  ▼                        ▼
            🎓 Aday/Öğrenci          🎓 Aday/Öğrenci
            (dealer_code ile etiketli → Mentorde panelinde işlenir)
```

---

## 2. İki Ayrı Akış

```
VERİ AKIŞI (yukarı)                    PARA AKIŞI (komisyon)
─────────────────────                  ─────────────────────────────
Aday → form (/p/slug, /apply/partner)  Öğrenci ödeme → milestone "paid"
   │ dealer_code etiketi                   │
   ▼                                       ├─▶ ALT BAYİ: kendi komisyonu (€/%)
Alt bayi  ──görür──▶ Bölge bayisi          │      (kendi cüzdanı)
   │                    │                   └─▶ BÖLGE BAYİSİ: override üst pay
   └────────┬───────────┘                          (alt getirisinin %20'si vb.)
            ▼                                       ↑ ikisi de kendi
       Mentorde paneli (operasyon)                   payout'una akar
       (manager tüm ağı görür)
```

---

## 3. Roller Özeti

| Seviye | Kim | Yapar | Veri Erişimi |
|--------|-----|-------|--------------|
| Kontrol | **Platform Owner** | tier / modül / fatura / duyuru | sadece sayı/agregat |
| Tenant admin | **Manager** | operasyon + bayi/override/mini-site onayı | tüm tenant verisi |
| Bayi L1 | **Bölge Bayisi** | alt bayi açar, ağını görür | kendi + alt lead'leri; para: kendi + override |
| Bayi L2 | **Alt Bayi** | lead getirir | sadece kendi; alt açamaz |
| Uç | **Aday/Öğrenci** | başvurur | — |

---

## 4. Kritik Sınırlar (güvenlik / izolasyon)

- 🔒 **Tenant izolasyonu:** Firma B, MentorDE'nin verisini göremez (cross-tenant yok).
- 🔒 **Platform Owner ≠ veri:** sadece agregat görür (DSGVO); impersonation varsayılan KAPALI.
- 🔒 **Para ≠ görünürlük:** bölge bayisi alt lead'lerini *görür* ama alt'ın *parasını* almaz — yalnız override.
- ⛔ **2 seviye sabit:** alt bayi kendi altına bayi açamaz.

---

## 5. Teknik Karşılıklar

| Kavram | Kod / Şema |
|--------|-----------|
| Hiyerarşi | `dealers.parent_dealer_id` (self FK) · `Dealer::isRegional()/isSub()/scopeCodes()` |
| Alt bayi açma | `dealer.regional` middleware · `DealerSubDealerController` |
| Görünürlük roll-up | `DealerPortalTrait::dealerScopeCodes()` (lead/öğrenci ağ geneli; para sadece kendi code) |
| Override | `dealers.override_rate_*` + `override_basis` · `dealer_student_revenues.is_override/origin_dealer_id` · `DealerRevenueService::syncOverrideForStudent()` |
| Mini-site | `dealers.public_slug/site_*` · `/p/{slug}` → `Public\DealerMiniSiteController` → `dealer-landing.blade.php` |
| Lead attribution | `/apply/partner/{code}` → `guest_applications.dealer_code` |
| KVKK onayı | `ConsentRecord` (`dealer_subdealer_data_access`) |

---

## 6. Bekleyen (Faz 3b)

- **Custom domain** (`dealers.custom_domain*` kolonları hazır): bayinin kendi alan adı → mini-site.
  KAS shared hosting arbitrary CNAME + SSL desteklemediği için **ertelendi**; Cloudflare proxy veya
  hosting Addon Domain + Let's Encrypt gerekir. Path-based `/p/{slug}` her durumda çalışır.
