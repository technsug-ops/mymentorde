# Aday Öğrenci 3-Level Form Refactor

**Başlangıç:** 2026-04-27
**Onaylayan:** Kullanıcı (tüm öneriler kabul edildi)

## Kabul Edilen 5 Karar

1. ✅ 9 yeni Level 1 field — karma: 6 kolon + 3 JSON
2. ✅ `registration_form_level` enum kolonu (4 değer)
3. ✅ Level 2 route: `/student/full-registration`
4. ✅ Level 2 soft-zorunlu (banner uyarısı, hard block yok)
5. ✅ Mevcut 27 guest → `level_2_done` backfill

## Mimari Hiyerarşi (Cumulative Subset)

```
Level 0 (Apply Form)        — public, /apply
   ↓ 8 field + KVKK
Level 1 (Aday Öğrenci)       — /registration/form (mevcut URL)
   ↓ 6 wizard, Level 0 readonly + 9 yeni field
Level 2 (Öğrenci)            — /student/full-registration (yeni)
   ↓ 8 wizard, Level 0+1 readonly + 60 ek field
```

**Cumulative kural:** Level N field'ları Level N+1'de pre-filled.
**Validation tutarlılığı:** Level 2 kuralları otomatik Level 1'de de uygulanır (tek catalog, `level` tag).

## 6 Wizard Yapısı (Level 1)

| # | Başlık | Field'lar (* = yeni) |
|---|--------|----------------------|
| 1 | 👤 KİŞİSEL BİLGİLER | first_name, last_name, gender, email, phone, communication_language (apply readonly) + birth_date |
| 2 | 🎓 AKADEMİK PROFİL | high_school_type, high_school_grad_year*, high_school_grade, higher_education_status*, (devamsa) university_name, university_department, university_year* |
| 3 | 🎯 HEDEF VE PLANLAR | application_type (readonly) + target_program |
| 4 | 🗣️ DİL YETERLİLİĞİ | german_level, german_certificate_held*, german_certificate_type*, german_certificate_score*, english_level, english_certificate_score* |
| 5 | 💰 MALİ DURUM VE LOJİSTİK | finance_method (+ undecided opt), accommodation_contact_status*, accommodation_contact_city* |
| 6 | 💭 MOTİVASYON VE HAZIRLIK | motivation_thinking_duration*, biggest_concerns* |

## İmplementasyon Adımları (Commit Bazlı)

- [ ] **C1** — Migration + Model fillable + 27 guest backfill (`level_2_done`)
- [ ] **C2** — Catalog refactor: `level` tag eklenir, mevcut field'lara level=1/2 işaretlenir
- [ ] **C3** — Yeni 11 field catalog'a eklenir + yeni opsiyon listeleri (higherEducationStatus, universityYear, germanCertificateType, accommodationContact, motivationDuration, biggestConcerns)
- [ ] **C4** — Level 1 wizard rendering: `WorkflowController::form()` level parameter, view 6 step yapısına geçer, level=1 field'ları render
- [ ] **C5** — Level 2 student route + controller + view (yeni `/student/full-registration`)
- [ ] **C6** — Sözleşme akışı: Level 1 submit → `registration_form_level=level_1_done` → contract trigger
- [ ] **C7** — Manager/Senior guest-detail Level 1 + Level 2 verilerini ayrı section'larda göster (UI küçük güncelleme)

## Yeni Migration Şeması

```sql
ALTER TABLE guest_applications ADD COLUMN
    high_school_grad_year         SMALLINT NULL,
    higher_education_status       VARCHAR(20) NULL,    -- enrolled/dropped/not_started
    university_year               VARCHAR(20) NULL,    -- prep/1/2/3/4/5plus
    german_certificate_held       BOOLEAN DEFAULT NULL,
    german_certificate_type       VARCHAR(40) NULL,    -- testdaf/dsh/goethe/telc/other
    german_certificate_score      VARCHAR(20) NULL,
    english_certificate_score     VARCHAR(40) NULL,
    accommodation_contact_status  VARCHAR(20) NULL,    -- yes/no/maybe
    accommodation_contact_city    VARCHAR(100) NULL,
    biggest_concerns              JSON NULL,
    motivation_thinking_duration  VARCHAR(20) NULL,    -- under_1y / 1_2y / over_2y
    registration_form_level       VARCHAR(30) DEFAULT 'level_1_pending'
        -- level_1_pending / level_1_done / level_2_pending / level_2_done
INDEX idx_registration_form_level (registration_form_level);
```

**Backfill:** Tüm mevcut guest'lerde `registration_form_submitted_at IS NOT NULL` olanlar `level_2_done`, diğerleri `level_1_pending`.

## Risk / Geri Alma

- Her commit izole — sorun çıkarsa o commit revert edilir
- Migration'da `down()` metodu tüm yeni kolonları drop eder
- Mevcut Level 2 form **çalışmaya devam eder** (sadece URL'i student tarafına taşınıyor)
- Apply form **hiç dokunulmuyor**

---

# 📲 Premium "Belge Talep Linki" — Tüm Modül Aktivasyon Planı

**Modül kodu:** `doc_request` (premium)
**Başlangıç:** 2026-04-28
**Mevcut durum:** Polymorphic altyapı + Guest/Student modülleri tamamlandı.

## Phase 0 — TAMAMLANDI ✅
- [x] Migration: `document_upload_tokens` + polymorphic kolonlar + backfill
- [x] Model: `DocumentUploadToken` (4 sabit, helpers, dual-write)
- [x] Public controller + view: mobile-first kamera capture
- [x] Manager + Senior controller (generic `*For()` methodlar)
- [x] Guest/Student detail view'larda buton + modal
- [x] Permission: `doc_request.use` + manager senior'a yetki açma toggle'ı

**Şu an çalışan target'lar:** `guest_application`, `student`

## 🎯 Phase 1 — Yüksek değer (yapılacak: ~50 dk)

- [ ] **D1 — HR Onboarding (User target_type)** ~25 dk
  - `routes/manager.php`: 3 endpoint `/manager/hr/persons/{user}/document-tokens`
  - Controller: `indexForUser/storeForUser/destroyForUser` (mevcut `*For()`'a delegasyon)
  - View `manager/hr/persons/card.blade.php` — Hesap tab'ında onboarding bölümü + buton + modal
  - Saklama: `user-documents/{user_id}/`

- [ ] **D2 — Dealer KYC (Dealer target_type)** ~25 dk
  - `routes/manager.php`: 3 endpoint `/manager/dealers/{dealer}/document-tokens`
  - Controller: `indexForDealer/storeForDealer/destroyForDealer`
  - View `manager/dealer-detail.blade.php` — buton + modal
  - Saklama: `dealer-documents/{id}/`

## 🟡 Phase 2 — Orta değer (~60 dk)

- [ ] **D3 — Ticket eki** ~30 dk + yeni `TARGET_TICKET` sabiti
  - Saklama: `ticket-attachments/{ticket_id}/`
  - Custom message ile "ne istediğini yaz" alanı önemli

- [ ] **D4 — Sözleşme imzalı geri yükleme** ~30 dk + yeni `TARGET_CONTRACT` sabiti
  - Saklama: `contract-files/{id}/signed/`
  - Custom message sözleşme adıyla pre-filled

## 🔵 Phase 3 — Niş (~3-4 saat)

- [ ] **D5 — Toplu belge talebi** — `max_uses > 1` aktivasyonu, multi-category JSON
- [ ] **D6 — WhatsApp Business API** — Twilio veya Meta Cloud API entegrasyonu

## 🟣 Phase 4 — Premium iyileştirmeler (~8-10 saat, opsiyonel)

- [ ] **D7 — Hatırlatma SMS/email** — 24h cron job
- [ ] **D8 — OCR (pasaport/kimlik)** — Tesseract veya cloud vision
- [ ] **D9 — Manager dashboard widget** — bekleyen talep sayısı + haftalık istatistik

## 💰 SaaS Tier Stratejisi

| Tier | doc_request | Kapsam |
|---|---|---|
| Basic | ❌ Kapalı | — |
| Gold | ✅ Açık | Guest + Student. Aylık 50 link. |
| Premium | ✅ Açık | Tüm target'lar (HR, Dealer, Ticket, Contract). Sınırsız. WhatsApp API. |

**Implementation:** `companies` tablosuna `doc_request_tier` enum + `doc_request_monthly_limit` int. Token üretimi öncesi quota check. (~1 saat)

## 🔧 Genişletme Prosedürü (her modül)

3 dosya, ~25 satır kod:
1. **Token modeli sabiti:** `TARGET_X = 'x'` + `TARGET_TYPES` label + `resolveDocumentOwnerId()` case + `resolveStorageDir()` case
2. **Controller endpoint:** `storeFor()` çağıran wrapper
3. **View:** mevcut modal markup'ını kopyala, `STORE_URL` değiştir

## Risk / Geri Alma
- Her phase izole, küçük commit'ler
- Mevcut akışları bozmaz (dual-write geri uyum garantisi)
- Yeni target_type eklemek = sadece sabit + match case (DB değişikliği yok)
- Module flag (`doc_request`) tüm özelliği tek noktadan kapatır
