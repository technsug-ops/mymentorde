# MentorDE — Marketing & Sales Modülü
## İş Akışı, İlişkiler, Otomasyon ve Görev Takibi

**Versiyon:** 3.0
**Son Güncelleme:** 2026-03-08
**Stack:** Laravel 12 / PHP 8.4 / MySQL
**Panel URL:** `/mktg-admin/dashboard`

---

## 1. Modül Yapısı

Marketing & Sales paneli tek bir URL altında (`/mktg-admin`) iki ayrı mod olarak çalışır.

```
/mktg-admin/dashboard
        │
   Session: mktg_panel_mode
        │
   ┌────┴────┐
   │         │
PAZARLAMA   SATIŞ
 Marketing  Sales
 Dashboard  Dashboard
```

### Mod Geçişi
- Sidebar'daki Pazarlama / Satış toggle butonları
- `GET /mktg-admin/switch-mode/{marketing|sales}`
- Session'a kaydedilir — sayfa yenilense de korunur

---

## 2. Kullanıcı Rolleri ve Erişim

### 2.1 Rol Özet Tablosu

| Rol | Mod | Toggle | Admin Menü | Veri Kapsamı |
|-----|-----|--------|------------|--------------|
| `marketing_admin` | Pazarlama + Satış | Evet | Tümü | Tüm veriler |
| `marketing_staff` | Yalnızca Pazarlama | Hayır | — | Yalnızca kendi ürettiği/atandığı |
| `sales_admin` | Yalnızca Satış | Hayır | Ekip, Ayarlar, Scoring Config | Tüm satış verileri |
| `sales_staff` | Yalnızca Satış | Hayır | — | Yalnızca atandığı lead'ler ve kendi task'ları |
| `manager` | Pazarlama + Satış | Evet | Tümü | Tüm veriler |
| `system_admin` | Pazarlama + Satış | Evet | Tümü | Tüm veriler |

### 2.2 Veri İzolasyon İlkesi

Staff rolleri (marketing_staff, sales_staff) yalnızca kendileriyle ilişkili verileri görür. Scope filtreleme `DataScopeService` tarafından tüm query'lere otomatik uygulanır.

```
DataScopeService:
  - applyScope($query, $user)
    → Staff ise: WHERE created_by = $userId OR assigned_to = $userId
    → Admin ise: Filtre yok (tüm veriler)
```

### 2.3 marketing_staff — Detaylı Yetki Matrisi

**İçerik ve Kampanya:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| CMS içerik oluşturma | Evet | `draft` olarak başlar |
| Kendi içeriğini düzenleme | Evet | Yalnızca `created_by = self` |
| Başkasının içeriğini görme | Hayır | Listede yalnızca kendi içerikleri |
| İçerik yayınlama | Hayır | `review`'a gönderebilir, yayınlama admin'de |
| Kampanya oluşturma | Evet | `draft` olarak başlar |
| Kampanya aktifleştirme | Hayır | Admin onayı gerekir |
| Başkasının kampanyasını görme | Hayır | Listede yalnızca kendi kampanyaları |
| Email template oluşturma | Evet | Kendi template'leri |
| Email kampanya gönderme | Hayır | Admin onayı gerekir |
| Sosyal medya post oluşturma | Evet | `draft` olarak |
| Başkasının postunu görme | Hayır | Yalnızca kendi postları |

**Otomasyon ve Analiz:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Workflow oluşturma | Evet | `draft`, aktifleştirme admin'de |
| Başkasının workflow'unu görme | Hayır | Yalnızca kendi oluşturduğu |
| Workflow enrollment listesi | Hayır | Admin'e özel |
| A/B Test oluşturma | Evet | `draft` olarak |
| A/B Test sonuçlarını görme | Kendi testi | Başkasının testini göremez |
| Kazanan uygulama | Hayır | Admin'e özel |
| Lead Scoring kuralı değiştirme | Hayır | Admin'e özel |
| Scoring leaderboard görme | Hayır | Admin'e özel |
| Attribution dashboard | Hayır | Admin'e özel |

**Dashboard ve KPI:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Marketing Dashboard görme | Evet | Sadece kendi metriklerini görür |
| Bütçe bilgisi görme | Hayır | Admin'e özel |
| ROI / CPA / Spend görme | Hayır | Finansal veriler admin'e özel |
| Email open/click (kendi kampanyaları) | Evet | Yalnızca kendi gönderdiği |
| Genel KPI kartları | Hayır | Kendi performans özeti gösterilir |
| Zamanlanmış rapor alma | Hayır | Admin'e özel |

**Task:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Task görme | Evet | `assigned_to = self` VEYA `created_by = self` |
| Task oluşturma | Evet | Kendine veya aynı departmana |
| Başkasının task'ını görme | Hayır | Yalnızca kendi task'ları |
| Kanban board | Evet | Filtrelenmiş — sadece kendi task'ları |

### 2.4 sales_staff — Detaylı Yetki Matrisi

**Lead Yönetimi:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Lead listesi görme | Evet | Yalnızca `assigned_to = self` olan lead'ler |
| Tüm lead'leri görme | Hayır | Admin'e özel |
| Atanmış lead'e not ekleme | Evet | Kendi lead'leri |
| Lead status değiştirme | Evet | Kendi lead'leri, sınırlı geçişler |
| Lead'e tag ekleme | Evet | Kendi lead'leri |
| Lead detayı görme | Evet | Yalnızca atanmış lead'ler |
| Lead score görme | Evet | Kendi lead'lerinin puanı |
| Lead score kuralı değiştirme | Hayır | Admin'e özel |

**Pipeline ve Analiz:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Pipeline dashboard | Evet | Yalnızca kendi lead'lerinin pipeline durumu |
| Tüm pipeline'ı görme | Hayır | Admin'e özel |
| Loss analysis | Hayır | Admin'e özel |
| Conversion time raporu | Hayır | Admin'e özel |
| Re-engagement havuzu | Evet | Kendine atanmış olanlar |
| Score analysis | Hayır | Admin'e özel |
| Attribution dashboard | Hayır | Admin'e özel |
| Pipeline value (gelir) | Hayır | Finansal veri admin'e özel |

**Bayi:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Bayi listesi görme | Hayır | Admin'e özel |
| Bayi payout bilgisi | Hayır | Admin'e özel |
| Bayi detayı görme | Hayır | Admin'e özel |

**Task:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Task görme | Evet | `assigned_to = self` VEYA `created_by = self` |
| Task oluşturma | Evet | Kendine veya kendi lead'leriyle ilgili |
| Kanban board | Evet | Filtrelenmiş — sadece kendi task'ları |

**Dashboard ve KPI:**

| İşlem | Yetki | Kapsam |
|---|---|---|
| Sales Dashboard görme | Evet | Kendi lead metrikleri (benim dönüşüm, benim aktif lead) |
| Toplam gelir görme | Hayır | Admin'e özel |
| Genel dönüşüm oranı | Hayır | Admin'e özel (kendi dönüşüm oranını görür) |
| Zamanlanmış rapor alma | Hayır | Admin'e özel |

### 2.5 sales_admin — marketing_admin Farkları

| Özellik | sales_admin | marketing_admin |
|---|---|---|
| Lead yönetimi (tümü) | Evet | Evet |
| Pipeline analiz (tümü) | Evet | Evet |
| Bayi yönetimi | Evet | Evet |
| Payout onaylama | Hayır (Manager'a özel) | Hayır (Manager'a özel) |
| Scoring config | Evet | Evet |
| CMS / Email / Sosyal medya | Hayır (Pazarlama modu) | Evet |
| Workflow builder | Hayır | Evet |
| A/B Test yönetimi | Hayır | Evet |
| Attribution dashboard | Evet (okuma) | Evet (okuma + config) |
| Bütçe yönetimi | Hayır | Evet |
| Entegrasyon ayarları | Hayır | Evet |
| Ekip yönetimi | Kendi modu | Tüm modlar |

### 2.6 Middleware Zinciri

```
auth → company.context → marketing.access
                              │
                    ┌─────────┼──────────┐
                    │         │          │
              marketing.admin  │    marketing.scoring
              (tüm yazma +   │    (scoring config)
               yayınlama +   │
               silme)        │
                        marketing.publish
                        (yayınlama, aktifleştirme,
                         email gönderme)
```

**Ek middleware — veri izolasyonu:**
```
marketing.scope → DataScopeService::applyScope()
  Staff rolü ise → otomatik WHERE created_by/assigned_to filtresi
  Admin rolü ise → filtre yok
```

### 2.7 Sidebar Menü Görünürlüğü

| Menü Öğesi | marketing_admin | marketing_staff | sales_admin | sales_staff |
|---|---|---|---|---|
| Dashboard | ✅ (tam) | ✅ (kendi metrikleri) | ✅ (tam) | ✅ (kendi metrikleri) |
| Kampanyalar | ✅ | ✅ (kendi) | ❌ | ❌ |
| CMS İçerik | ✅ | ✅ (kendi) | ❌ | ❌ |
| Email | ✅ | ✅ (kendi) | ❌ | ❌ |
| Sosyal Medya | ✅ | ✅ (kendi) | ❌ | ❌ |
| Tracking Links | ✅ | ❌ | ❌ | ❌ |
| Etkinlikler | ✅ | ✅ (kendi) | ❌ | ❌ |
| Workflows | ✅ | ✅ (kendi) | ❌ | ❌ |
| A/B Testler | ✅ | ✅ (kendi) | ❌ | ❌ |
| Scoring Dashboard | ✅ | ❌ | ✅ | ❌ |
| Scoring Config | ✅ | ❌ | ✅ | ❌ |
| Attribution | ✅ | ❌ | ✅ | ❌ |
| Pipeline | ✅ | ❌ | ✅ (tam) | ✅ (kendi) |
| Lead Kaynakları | ✅ | ❌ | ✅ | ❌ |
| Bayi İlişkileri | ✅ | ❌ | ✅ | ❌ |
| Bütçe | ✅ | ❌ | ❌ | ❌ |
| KPI Raporlar | ✅ | ❌ | ✅ | ❌ |
| Zamanlanmış Raporlar | ✅ | ❌ | ✅ | ❌ |
| Görevler | ✅ (tüm) | ✅ (kendi) | ✅ (tüm) | ✅ (kendi) |
| Ekip | ✅ | ❌ | ✅ | ❌ |
| Ayarlar | ✅ | ❌ | ✅ | ❌ |
| Entegrasyonlar | ✅ | ❌ | ❌ | ❌ |
| Bildirimler | ✅ | ✅ (kendi) | ✅ | ✅ (kendi) |
| Profil | ✅ | ✅ | ✅ | ✅ |

---

## 3. Lead Scoring Sistemi

### 3.1 Genel Bakış

Her lead'e (GuestApplication) davranışlarına, demografik bilgilerine ve etkileşimlerine göre otomatik puan verilir. Puan belirli eşikleri geçtiğinde lead otomatik olarak bir sonraki aşamaya taşınır veya workflow tetiklenir.

### 3.2 Scoring Modeli

```
LeadScore = Davranış Puanı + Profil Puanı + Etkileşim Puanı − Bozunma Puanı
```

**Davranış Puanları (Behavioral):**

| Aksiyon | Puan | Tekrar | Açıklama |
|---|---|---|---|
| Portal'a giriş yaptı | +2 | Günde 1 kez sayılır | Aktif kullanıcı sinyali |
| Kayıt formunu tamamladı | +15 | Tek sefer | Ciddi niyet |
| Belge yükledi | +10 | Her belge için | Süreç ilerliyor |
| Paket sayfasını görüntüledi | +5 | Günde 1 kez | İlgi göstergesi |
| Paket seçti | +20 | Tek sefer | Güçlü satın alma niyeti |
| Sözleşme talep etti | +25 | Tek sefer | En güçlü sinyal |
| Ticket açtı | +3 | Her ticket | Etkileşim |
| Profil tamamladı | +5 | Tek sefer | Bağlılık |
| Email açtı | +1 | Her email | Hafif etkileşim |
| Email linkine tıkladı | +3 | Her tıklama | Orta etkileşim |
| Etkinliğe kayıt oldu | +5 | Her kayıt | İlgi |
| CMS içerik okudu | +1 | Günde 3 kez max | Araştırma yapıyor |

**Profil Puanları (Demographic):**

| Kriter | Puan | Açıklama |
|---|---|---|
| Almanca seviyesi B1+ | +10 | Başvuru olasılığı yüksek |
| Almanca seviyesi A1-A2 | +5 | Dil kursu potansiyeli |
| Lise notu 70+ | +5 | Bachelor başvuru uygunluğu |
| Üniversite notu 2.5+ (GPA) | +5 | Master başvuru uygunluğu |
| Pasaport mevcut | +5 | Hazırlık seviyesi |
| Bloke hesap bilgisi var | +10 | Finansal hazırlık |
| Başvuru ülkesi: Türkiye | +3 | Ana hedef pazar |
| Yaş 18-25 | +3 | Hedef demografik |

**Bozunma Puanı (Decay):**

| Hareketsizlik Süresi | Günlük Düşüş | Açıklama |
|---|---|---|
| 7-14 gün | -1/gün | Hafif bozunma |
| 15-30 gün | -2/gün | Orta bozunma |
| 30+ gün | -3/gün | Güçlü bozunma |
| Minimum puan | 0 | Negatife düşmez |

### 3.3 Scoring Eşikleri ve Otomatik Aksiyonlar

```
┌─────────────────────────────────────────────────────────────────┐
│  0 ──────── 20 ──────── 50 ──────── 80 ──────── 100+          │
│  COLD        WARM        HOT       SALES-READY   CHAMPION      │
│                                                                 │
│  Otomatik:   Otomatik:   Otomatik:  Otomatik:    Otomatik:     │
│  Nurture     Engagement  Senior'a    Manager'a   VIP muamele   │
│  drip başlar drip başlar  bildirim   bildirim    + öncelik      │
│              + task       + task     "Hemen       atama         │
│              oluşur       oluşur     iletişim                   │
│                                     kur!"                       │
└─────────────────────────────────────────────────────────────────┘
```

| Eşik | Seviye | lead_score_tier | Otomatik Aksiyon |
|---|---|---|---|
| 0-19 | Cold | `cold` | Nurture drip workflow başlar |
| 20-49 | Warm | `warm` | Engagement drip başlar, "Lead takip" task oluşur |
| 50-79 | Hot | `hot` | Senior'a bildirim, "Hızlı iletişim" task oluşur |
| 80-99 | Sales-Ready | `sales_ready` | Manager'a bildirim, öncelikli işlem bayrağı |
| 100+ | Champion | `champion` | VIP atama, en deneyimli Senior'a yönlendirme |

### 3.4 Scoring Veritabanı

```
guest_applications tablosuna eklenen alanlar:
  - lead_score (int, default 0)
  - lead_score_tier (string: cold/warm/hot/sales_ready/champion)
  - lead_score_updated_at (timestamp)

lead_score_logs tablosu (audit trail):
  - id
  - guest_application_id (FK)
  - action (string) — "portal_login", "document_uploaded", "decay", vb.
  - points (int) — +10, -2, vb.
  - score_before (int)
  - score_after (int)
  - metadata (JSON) — { document_type: "passport", ... }
  - created_at

lead_scoring_rules tablosu (config — admin düzenleyebilir):
  - id
  - action_code (string, unique) — "portal_login", "document_uploaded"
  - category (string) — "behavioral", "demographic", "decay"
  - points (int)
  - max_per_day (int | null) — günlük tekrar limiti
  - is_one_time (bool) — tek sefer mi
  - is_active (bool)
  - updated_by (FK users.id)
  - updated_at
```

### 3.5 LeadScoringService

```
LeadScoringService:
  - addScore($guestId, $actionCode, $metadata = [])
    → Kuralı kontrol et, tekrar limiti kontrol et, puan ekle, log yaz
    → Tier değişimi varsa otomatik aksiyonları tetikle
  - applyDecay()
    → Scheduler: her gece 02:00 → lead:apply-score-decay
    → Hareketsiz lead'lerin puanını düşür
  - recalculateScore($guestId)
    → Tüm loglardan yeniden hesapla (admin override sonrası)
  - getScoreBreakdown($guestId)
    → Puanın kaynağını kategorize ederek döndür (dashboard için)
```

---

## 4. Automation Workflows (Drip Campaign & Visual Builder)

### 4.1 Genel Bakış

Workflow sistemi, belirli tetikleyicilere (trigger) göre otomatik aksiyon zincirleri çalıştırır. Görsel bir builder ile "if-then-wait-branch" mantığında akışlar tasarlanır.

### 4.2 Workflow Yapısı

```
AutomationWorkflow
  ├── name (string)
  ├── status: draft → pending_approval → active → paused → archived
  ├── trigger_type (string) — tetikleyici türü
  ├── trigger_config (JSON) — tetikleyici parametreleri
  ├── created_by / approved_by (users.id)
  ├── is_recurring (bool) — aynı kişi tekrar girebilir mi
  ├── enrollment_limit (int | null) — max eş zamanlı enrollment
  └── nodes (AutomationWorkflowNode[])
```

### 4.3 Trigger (Tetikleyici) Tipleri

| Trigger | Açıklama | Örnek Config |
|---|---|---|
| `guest_created` | Yeni guest kaydı oluştuğunda | `{}` |
| `score_tier_changed` | Lead score tier'ı değiştiğinde | `{ "new_tier": "warm" }` |
| `score_reached` | Belirli puana ulaştığında | `{ "min_score": 50 }` |
| `status_changed` | Lead/contract status değiştiğinde | `{ "field": "contract_status", "new_value": "requested" }` |
| `document_uploaded` | Belge yüklendiğinde | `{ "category": "SOZLESME_IMZALI" }` |
| `inactivity` | Belirli süre hareketsizlik | `{ "days": 7 }` |
| `form_completed` | Kayıt formu tamamlandığında | `{}` |
| `package_selected` | Paket seçildiğinde | `{}` |
| `email_opened` | Belirli email açıldığında | `{ "email_campaign_id": 42 }` |
| `email_not_opened` | Email açılmadığında (süre sonra) | `{ "email_campaign_id": 42, "wait_hours": 48 }` |
| `tag_added` | Lead'e tag eklendiğinde | `{ "tag": "vip_candidate" }` |
| `date_based` | Belirli tarihe göre | `{ "field": "created_at", "offset_days": 30 }` |
| `manual` | Manuel enrollment | `{}` |

### 4.4 Node (Aksiyon) Tipleri

```
AutomationWorkflowNode
  ├── workflow_id (FK)
  ├── node_type (string) — aksiyon türü
  ├── node_config (JSON) — aksiyon parametreleri
  ├── position_x / position_y (int) — canvas konumu (visual builder)
  ├── sort_order (int) — sıralama
  └── connections (JSON) — [{ target_node_id, condition }]
```

| Node Type | Açıklama | Config Örneği |
|---|---|---|
| `send_email` | Email gönder | `{ "template_id": 5, "delay_hours": 0 }` |
| `send_notification` | In-app bildirim | `{ "title": "...", "body": "..." }` |
| `send_whatsapp` | WhatsApp mesajı | `{ "template_code": "welcome_01" }` |
| `wait` | Bekle | `{ "duration": 48, "unit": "hours" }` |
| `wait_until` | Koşul gerçekleşene kadar bekle | `{ "condition": "document_uploaded", "timeout_hours": 168 }` |
| `condition` | If/else dallanma | `{ "field": "lead_score", "operator": ">=", "value": 50 }` |
| `add_score` | Lead score ekle/çıkar | `{ "points": 10 }` |
| `add_tag` | Tag ekle | `{ "tag": "engaged_user" }` |
| `remove_tag` | Tag kaldır | `{ "tag": "cold_lead" }` |
| `assign_to` | Senior/Staff ata | `{ "strategy": "round_robin", "role": "senior" }` |
| `create_task` | Task oluştur | `{ "title": "...", "assigned_role": "senior", "priority": "high" }` |
| `update_field` | Lead alanı güncelle | `{ "field": "lead_status", "value": "qualified" }` |
| `move_to_segment` | Segmente ekle | `{ "segment_id": 12 }` |
| `webhook` | Dış servise HTTP çağrısı | `{ "url": "...", "method": "POST" }` |
| `ab_split` | A/B dallanma | `{ "variant_a_pct": 50, "variant_b_pct": 50 }` |
| `goal_check` | Hedefe ulaşıldı mı kontrol | `{ "goal": "contract_requested" }` |
| `exit` | Workflow'dan çıkar | `{}` |

### 4.5 Visual Workflow Builder

URL: `/mktg-admin/workflows/{id}/builder`

```
┌──────────────────────────────────────────────────────────────────┐
│  WORKFLOW BUILDER — "Yeni Guest Nurture Akışı"         [Kaydet] │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐                                                │
│  │ 🎯 TRIGGER  │                                                │
│  │ guest_created│                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐                                                │
│  │ ✉️ EMAIL    │                                                │
│  │ "Hoş geldin"│                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐                                                │
│  │ ⏳ WAIT     │                                                │
│  │ 3 gün       │                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐     ┌─────────────┐                           │
│  │ ❓ CONDITION│─YES─▶│ ✉️ EMAIL   │                           │
│  │ Form tamam? │     │ "Tebrikler" │                           │
│  └──────┬──────┘     └─────────────┘                           │
│         │ NO                                                     │
│  ┌──────▼──────┐                                                │
│  │ ✉️ EMAIL    │                                                │
│  │ "Formunu    │                                                │
│  │  tamamla"   │                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐                                                │
│  │ ⏳ WAIT     │                                                │
│  │ 7 gün       │                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐     ┌──────────────┐                          │
│  │ ❓ CONDITION│─YES─▶│ 🏁 GOAL     │                          │
│  │ Paket seçti?│     │ "Converted"  │                          │
│  └──────┬──────┘     └──────────────┘                          │
│         │ NO                                                     │
│  ┌──────▼──────┐                                                │
│  │ 📋 TASK     │                                                │
│  │ "Manuel      │                                                │
│  │  iletişim"  │                                                │
│  └──────┬──────┘                                                │
│         │                                                        │
│  ┌──────▼──────┐                                                │
│  │ 🚪 EXIT    │                                                │
│  └─────────────┘                                                │
│                                                                  │
│  [+ Node Ekle]  [Zoom: 100%]  [Undo] [Redo]                   │
└──────────────────────────────────────────────────────────────────┘
```

**Builder Teknolojisi:**
- Frontend: Canvas tabanlı (LeaderLine veya ReactFlow portu)
- Drag & drop node ekleme, bağlantı çizme
- Her node tıklanınca sağ panelde config formu açılır
- Gerçek zamanlı validasyon (bağlantısız node uyarısı, sonsuz döngü tespiti)

### 4.6 Enrollment Takibi

```
automation_enrollments tablosu:
  - id
  - workflow_id (FK)
  - guest_application_id (FK)
  - current_node_id (FK | null) — şu an hangi adımda
  - status: active / waiting / completed / exited / errored
  - enrolled_at (timestamp)
  - completed_at (timestamp | null)
  - exit_reason (string | null) — "goal_reached", "manual", "timeout", "error"
  - metadata (JSON)

automation_enrollment_logs tablosu:
  - id
  - enrollment_id (FK)
  - node_id (FK)
  - action (string) — "entered", "executed", "waiting", "condition_true", "condition_false"
  - result (JSON | null)
  - executed_at (timestamp)
```

### 4.7 Hazır Workflow Şablonları

| Şablon | Trigger | Açıklama |
|---|---|---|
| Yeni Guest Nurture | `guest_created` | Hoş geldin → 3g bekle → form kontrolü → 7g bekle → paket kontrolü → task |
| Hareketsiz Lead Recovery | `inactivity (7g)` | Hatırlatma email → 3g bekle → açtı mı? → evet: score+5 / hayır: 2.email → 7g → task |
| Sözleşme Takip | `status_changed (requested)` | Bilgilendirme → 5g bekle → imzaladı mı? → hayır: hatırlatma → 3g → task |
| Score Tier Upgrade | `score_tier_changed (hot)` | Senior'a bildirim → task oluştur → 24s bekle → meeting planlama email |
| Re-engagement | `inactivity (90g)` | "Seni özledik" email → 7g → açtı mı? → kampanya teklifi → 14g → task |
| Post-Conversion Welcome | `status_changed (approved)` | Tebrik email → 1g → belge hazırlık rehberi → 3g → ilk adımlar email |

### 4.8 WorkflowEngineService

```
WorkflowEngineService:
  - enroll($guestId, $workflowId)
    → Enrollment oluştur, ilk node'u çalıştır
  - processNode($enrollmentId)
    → Mevcut node'u çalıştır, sonraki node'a geç veya bekle
  - processWaitingEnrollments()
    → Scheduler: her 5 dk → workflow:process-waiting
    → Bekleme süresi dolan enrollment'ları ilerlet
  - checkGoals()
    → Scheduler: her saat → workflow:check-goals
    → Goal node'ları kontrol et, ulaşanları "completed" yap
  - evaluateCondition($enrollment, $nodeConfig)
    → Koşul değerlendir, doğru/yanlış dalına yönlendir
  - exitEnrollment($enrollmentId, $reason)
    → Enrollment'ı sonlandır

Scheduler Komutları:
  */5 * * * * → workflow:process-waiting
  0 * * * *  → workflow:check-goals
  0 2 * * *  → lead:apply-score-decay
```

---

## 5. Multi-Touch Attribution

### 5.1 Genel Bakış

Bir lead'in dönüşüm yolculuğunda birden fazla temas noktası olabilir. Multi-touch attribution, her temas noktasına dönüşüm kredisini adil şekilde dağıtır.

### 5.2 Touchpoint Kaydı

```
lead_touchpoints tablosu:
  - id
  - guest_application_id (FK)
  - touchpoint_type (string) — "ad_click", "organic_visit", "email_click",
                                "social_click", "dealer_referral", "event_registration",
                                "direct_visit", "content_view"
  - channel (string) — "google_ads", "meta_ads", "tiktok_ads", "instagram",
                        "linkedin", "email", "organic", "referral", "direct"
  - campaign_id (FK | null) — MarketingCampaign
  - utm_source / utm_medium / utm_campaign / utm_content / utm_term
  - referrer_url (string | null)
  - landing_page (string | null)
  - device_type (string) — "desktop", "mobile", "tablet"
  - touched_at (timestamp)
  - is_converting_touch (bool) — dönüşüm anındaki son temas mı
```

### 5.3 Attribution Modelleri

Sistem 5 attribution modelini paralel olarak hesaplar:

| Model | Mantık | En İyi Kullanım |
|---|---|---|
| **First Touch** | İlk temas noktasına %100 | Hangi kanal yeni lead getiriyor? |
| **Last Touch** | Son temas noktasına %100 | Hangi kanal dönüşümü tetikliyor? |
| **Linear** | Tüm temas noktalarına eşit | Genel kanal değerlendirmesi |
| **Time Decay** | Son temaslara daha fazla kredi (7 günlük yarı ömür) | Son dönem etkisi |
| **Position Based (U-shaped)** | İlk %40 + son %40 + aradakiler %20 paylaşır | Dengeli değerlendirme |

### 5.4 Attribution Hesaplama

```
AttributionService:
  - recordTouchpoint($guestId, $type, $channel, $metadata)
    → Her etkileşimde touchpoint kaydı oluştur
  - calculateAttribution($guestId, $model = 'position_based')
    → Dönüşüm anında tüm modeller için kredi dağılımı hesapla
  - getChannelROI($channel, $dateRange, $model)
    → Kanal bazlı ROI hesapla (attributed revenue / channel spend)
  - getCampaignAttribution($campaignId, $model)
    → Kampanya bazlı attribution raporu

Scheduler:
  0 3 * * * → attribution:recalculate-daily
  → Önceki günün dönüşümleri için tüm modelleri hesapla
```

### 5.5 Attribution Dashboard

URL: `/mktg-admin/attribution`

```
┌──────────────────────────────────────────────────────────────────┐
│  ATTRIBUTION RAPORU                    Model: [Position Based ▾] │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Kanal Performansı (son 30 gün):                                │
│                                                                  │
│  Google Ads    ████████████████████  42% — 12.600 EUR attributed │
│  Instagram     ██████████            21% —  6.300 EUR attributed │
│  Organic       ████████              17% —  5.100 EUR attributed │
│  Bayi Referral ██████                12% —  3.600 EUR attributed │
│  Email         ███                    6% —  1.800 EUR attributed │
│  Direct        █                      2% —    600 EUR attributed │
│                                                                  │
│  ─── Kanal Karşılaştırma (Model Bazlı) ──────                  │
│                                                                  │
│  | Kanal    | First | Last | Linear | Decay | Position |        │
│  |----------|-------|------|--------|-------|----------|        │
│  | Google   |  52%  | 35%  |  42%   |  38%  |   42%   |        │
│  | Instagram|  18%  | 28%  |  21%   |  24%  |   21%   |        │
│  | ...      |  ...  | ...  |  ...   |  ...  |   ...   |        │
│                                                                  │
│  ─── Tipik Dönüşüm Yolu ──────────────────────                  │
│                                                                  │
│  Google Ad → Instagram → Organic → Email → Paket Seçimi        │
│  (Ortalama 4.2 temas, 23 gün)                                   │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 6. A/B Testing Sistemi

### 6.1 Test Edilebilir Alanlar

| Alan | Ne Test Edilir | Metrik |
|---|---|---|
| Email subject line | 2-4 varyant | Open rate |
| Email içeriği | 2 varyant | Click rate |
| Landing page | 2 varyant (A/B redirect) | Form completion rate |
| CMS içerik başlığı | 2-4 varyant | Click-through rate |
| Workflow dalları | A/B split node ile | Goal completion rate |
| Paket sunumu | Fiyat/sıralama varyantları | Package selection rate |

### 6.2 Test Yapısı

```
ab_tests tablosu:
  - id
  - name (string)
  - test_type (string) — "email_subject", "email_content", "landing_page",
                          "cms_title", "workflow_split", "package_display"
  - status: draft → pending_approval → running → paused → completed → winner_applied
  - traffic_split (JSON) — { "A": 50, "B": 50 } veya { "A": 33, "B": 33, "C": 34 }
  - primary_metric (string) — "open_rate", "click_rate", "conversion_rate"
  - min_sample_size (int) — istatistiksel anlamlılık için minimum
  - confidence_level (float, default 0.95) — %95 güven aralığı
  - auto_winner (bool) — sample size + confidence sağlanınca otomatik kazanan uygula
  - winner_variant (string | null) — "A" veya "B"
  - started_at / completed_at
  - created_by / approved_by

ab_test_variants tablosu:
  - id
  - ab_test_id (FK)
  - variant_code (string) — "A", "B", "C"
  - variant_config (JSON) — { "subject": "Almanya'da eğitim fırsatı!" }
  - impressions (int, default 0)
  - conversions (int, default 0)
  - conversion_rate (float, default 0)

ab_test_assignments tablosu:
  - id
  - ab_test_id (FK)
  - guest_application_id (FK)
  - variant_code (string)
  - assigned_at (timestamp)
  - converted (bool, default false)
  - converted_at (timestamp | null)
```

### 6.3 İstatistiksel Anlamlılık

```
ABTestingService:
  - assignVariant($testId, $guestId)
    → Trafik split'e göre varyant ata (sticky — aynı kullanıcıya hep aynı)
  - recordConversion($testId, $guestId)
    → Dönüşüm kaydet, conversion_rate güncelle
  - checkSignificance($testId)
    → Z-test ile p-value hesapla
    → p < (1 - confidence_level) ise "significant" döndür
  - applyWinner($testId)
    → Kazanan varyantı tüm trafiğe uygula
  - getTestReport($testId)
    → Varyant performansları, confidence interval, önerilen aksiyon

Auto-winner Mantığı:
  Scheduler: her saat → abtest:check-winners
  → min_sample_size karşılandı mı?
  → Confidence sağlandı mı?
  → Evet + auto_winner=true → kazanan otomatik uygulanır
  → Evet + auto_winner=false → admin'e bildirim gönderilir
```

---

## 7. Pazarlama (Marketing) İş Akışı

### 7.1 Lead Edinimi → Kampanya Takibi

```
Dış Kaynak (Google Ads / Meta / Organic / Referral)
        │
        ▼
Tracking Link (/t/{code}) → UTM capture + Touchpoint kaydı
        │                    → Rate limit: 60 req/dk per IP
        │                    → Bot koruması: honeypot + fingerprint
        ▼
Apply Formu Doldurulur → GuestApplication oluşur
        │
        ▼
Eş zamanlı tetiklenenler:
  ├── LeadSourceDatum kaydedilir (UTM)
  ├── LeadScore: +15 (form_completed)
  ├── Touchpoint: "form_submission" kaydedilir
  ├── Workflow: "guest_created" trigger'lı akışlar enrollment olur
  └── Marketing Dashboard KPI'lara yansır
```

### 7.2 Tracking Link Güvenliği

| Tehdit | Koruma |
|---|---|
| Bot traffic | Honeypot field + browser fingerprint kontrolü |
| Rate abuse | `throttle:tracking,60,1` middleware |
| Click fraud | Aynı IP + fingerprint'ten 24 saat içinde tekrar tıklama sayılmaz |
| Invalid UTM | Bilinmeyen utm_source `unknown` olarak kaydedilir |

### 7.3 Kampanya Yönetimi

```
MarketingCampaign
  ├── status: draft → pending_approval → active → paused → completed
  ├── budget / spent_amount
  ├── created_by / approved_by
  ├── ab_test_id (FK | null) — bağlı A/B test varsa
  ├── LeadSourceDatum (UTM eşleşmesi)
  └── MarketingExternalMetric (platform sync)
```

### 7.4 E-posta Pazarlama Akışı

```
EmailTemplate → EmailSegment (filtre kriterleri)
        │
        ▼
EmailCampaign
  - status: draft → pending_approval → scheduled → sending → sent
  - ab_test_id (FK | null) — subject veya content A/B testi
        │
        ▼
EmailSendLog → her alıcı için kayıt
  - status: pending → sent → delivered → opened → clicked → bounced → failed
  - opened_at, clicked_at, bounce_type
  - Açma/tıklama → LeadScore: +1/+3
  - Açma/tıklama → Touchpoint kaydı
  - Açma/tıklama → Workflow trigger kontrolü
```

**Email KPI'lar:**

| Metrik | Hesaplama |
|---|---|
| Delivery Rate | delivered / sent * 100 |
| Open Rate | opened / delivered * 100 |
| Click Rate | clicked / delivered * 100 |
| Click-to-Open Rate | clicked / opened * 100 |
| Bounce Rate | bounced / sent * 100 |
| Unsubscribe Rate | unsubscribed / delivered * 100 |

### 7.5 Sosyal Medya Akışı

```
SocialMediaAccount (6 platform)
  └── SocialMediaPost
        - status: draft → scheduled → published → failed
        - SocialMediaMonthlyMetric (sync)
```

**Sync Frekansı:**

| Durum | Frekans |
|---|---|
| Aktif kampanya var | 4 saatte bir |
| Aktif kampanya yok | Günde 1 (07:00) |
| Kampanya bitiş günü | +1 final sync |

### 7.6 CMS İçerik Akışı

```
CmsContent
  - status: draft → review → revision_needed → scheduled → published → archived
  - created_by, reviewed_by, review_note
  - ab_test_id (FK | null) — başlık A/B testi
  - CmsContentRevision (sürüm geçmişi)
```

### 7.7 Etkinlik Akışı

```
MarketingEvent
  - status: draft → published → registration_closed → completed → cancelled
  - EventRegistration (status: registered → confirmed → attended → no_show → cancelled)
  - Hatırlatmalar: 7g, 1g, 1s önce → NotificationDispatch
  - Kayıt → LeadScore: +5 + Touchpoint kaydı
```

---

## 8. Satış (Sales) İş Akışı

### 8.1 Lead Durumu Akışı

```
GuestApplication (lead)
  lead_status:
    new ──→ in_review ──→ qualified ──→ meeting_scheduled
                                              │
                                    meeting_completed
                                              │
                              ┌───────────────┤
                              │               │
                           converted      not_interested
                           (→ Öğrenci)        │
                                              ▼
                                    re_engagement_pool (90g sonra)
                                              │
                                    ┌─────────┤
                                    │         │
                                 recovered   dead
                                 (→ new)    (kalıcı)
```

Lead status geçişleri hem manuel (sales staff) hem otomatik (scoring + workflow) olarak yapılabilir.

### 8.2 Sözleşme Pipeline

Master sözleşme dokümanıyla (v5.0) birebir uyumlu:

```
not_requested → pending_manager → requested → signed_uploaded
                                                    ├─► approved (→ student)
                                                    ├─► rejected (→ tekrar yükle)
                                                    └─► cancelled
```

**Geçerli status değerleri (NormalizeContractStatus):**
```php
['not_requested', 'pending_manager', 'requested', 'signed_uploaded', 'approved', 'rejected', 'cancelled']
```

### 8.3 Sales Dashboard KPI'ları

| KPI | Kaynak | Açıklama |
|-----|--------|----------|
| Yeni Lead (30g) | `guest_applications.created_at` | Son 30 gün |
| Aktif Pipeline | `contract_status NOT IN (approved, cancelled)` | Devam eden |
| Onaylanan (30g) | `contract_status = 'approved'` | Dönüşen |
| Dönüşüm Oranı | approved / total * 100 | % |
| Ort. Dönüşüm Süresi | AVG(approved_at - created_at) | Gün |
| Aylık Gelir | `student_revenues.total_earned` | 30g toplam |
| Re-engagement Başarı | recovered / pool * 100 | % |
| Ort. Lead Score | AVG(lead_score) by tier | Tier bazlı |
| Score→Conversion Correlation | Conversion rate by score range | Scoring etkinliği |

### 8.4 Pipeline Analiz Araçları

| Sayfa | URL |
|-------|-----|
| Genel Bakış | `/mktg-admin/pipeline` |
| Pipeline Value | `/mktg-admin/pipeline/value` |
| Loss Analysis | `/mktg-admin/pipeline/loss-analysis` |
| Conversion Time | `/mktg-admin/pipeline/conversion-time` |
| Re-engagement | `/mktg-admin/pipeline/re-engagement` |
| Score Analysis | `/mktg-admin/pipeline/score-analysis` |

### 8.5 Bayi (Dealer) İlişkileri

```
Dealer → UTM Link → Tracking + Touchpoint → GuestApplication
        │
        ▼ (öğrenci dönüşümü sonrası)
DealerStudentRevenue → DealerRevenueMilestone (4 aşama)
        │
        ▼
Payout Süreci:
  - Periyot: Aylık (1'inde hesap, 15'ine kadar ödeme)
  - DealerPayoutRequest (pending → manager_approved → paid / rejected / on_hold)
  - Manager onayı zorunlu
```

---

## 9. Görev (Task) Yönetimi

### 9.1 Task Yapısı

```
MarketingTask
  ├── assigned_to, created_by (users.id)
  ├── department: marketing / sales / content / operations
  ├── status: pending → in_progress → in_review → done → cancelled
  ├── priority: low / medium / high / urgent
  ├── due_at, completed_at
  ├── source: manual / campaign / automation / integration / re_engagement / scoring / workflow
  ├── source_ref (string | null) — "workflow:42:node:7" gibi referans
  └── kanban: column_order
```

### 9.2 Task Otomasyonu

| Tetikleyici | Task | Atanan | Öncelik |
|---|---|---|---|
| Yeni guest kaydı | "İlk iletişim kur" | Senior (round-robin) | high |
| Sözleşme talep edildi | "Sözleşme hazırla" | Manager | high |
| İmzalı sözleşme yüklendi | "Sözleşme incele" | Manager | urgent |
| Sözleşme iptal | "İptal analizi" | Manager | medium |
| Lead 7g hareketsiz | "Lead takip et" | Sales staff | medium |
| Lead 30g hareketsiz | "Lead değerlendir" | Sales staff | high |
| Lead → re_engagement_pool | "Re-engagement iletişim" | Sales staff (round-robin) | medium |
| Score tier → hot | "Hızlı iletişim kur" | Senior | high |
| Score tier → sales_ready | "Öncelikli görüşme planla" | Manager | urgent |
| Campaign ROI < %10 | "Kampanya optimizasyonu" | Marketing Admin | high |
| Email bounce > %5 | "Email listesi temizliği" | Marketing staff | medium |
| CMS review bekliyor | "İçerik inceleme" | Marketing Admin | medium |
| Etkinlik 3g kala kayıt < %50 | "Tanıtım artır" | Marketing staff | high |
| A/B test anlamlılık sağlandı | "Test sonucu değerlendir" | Marketing Admin | medium |
| Workflow enrollment hata | "Workflow hata inceleme" | Marketing Admin | high |

### 9.3 Kanban Board

URL: `/mktg-admin/tasks/kanban` — HTML5 Drag & Drop, filtreler: department, priority, assigned_to, source.

---

## 10. KPI & Raporlar

### 10.1 Marketing KPI

| KPI | Formül | Kaynak | Görünürlük |
|-----|--------|--------|------------|
| Guest Count (30g) | COUNT(lead_source_data) | LeadSourceDatum | Admin |
| Conversion Rate | verified / guest * 100 | Hesaplanan | Admin |
| CPA | totalSpent / verifiedCount | Hesaplanan | Admin |
| ROI | (revenue - spend) / spend * 100 | Hesaplanan | Admin |
| Email Open Rate | opened / delivered * 100 | EmailSendLog | Admin (staff: kendi kampanyaları) |
| Email Click Rate | clicked / delivered * 100 | EmailSendLog | Admin (staff: kendi kampanyaları) |
| Social Engagement | (likes+comments+shares) / impressions * 100 | SocialMediaMonthlyMetric | Admin |
| Workflow Completion Rate | completed / enrolled * 100 | AutomationEnrollment | Admin |
| A/B Test Win Rate | tests_with_winner / total_tests * 100 | ABTest | Admin |
| Attribution ROI by Channel | attributed_revenue / channel_spend | Touchpoint + Attribution | Admin |

**marketing_staff Dashboard'u:**
Staff kendi dashboard'unda yalnızca şunları görür: kendi oluşturduğu kampanya sayısı, kendi içeriklerinin durumu (draft/review/published), kendi email kampanyalarının open/click oranı, kendine atanmış task sayısı ve durumları. Finansal metrikler (ROI, CPA, spend, gelir) kesinlikle gösterilmez.

### 10.2 Sales KPI

| KPI | Formül | Kaynak | Görünürlük |
|-----|--------|--------|------------|
| Yeni Lead (30g) | COUNT(guest_applications) | GuestApplication | Admin (staff: kendi lead sayısı) |
| Onaylanan (30g) | COUNT(approved) | GuestApplication | Admin (staff: kendi dönüşenleri) |
| Dönüşüm Oranı | approved / total * 100 | Hesaplanan | Admin (staff: kendi oranı) |
| Ort. Lead Score | AVG(lead_score) | GuestApplication | Admin |
| Score→Conversion | Conversion % by score tier | Hesaplanan | Admin |
| Aylık Gelir | SUM(total_earned) | StudentRevenue | Admin (finansal — staff göremez) |
| Re-engagement Başarı | recovered / pool * 100 | GuestApplication | Admin |

**sales_staff Dashboard'u:**
Staff kendi dashboard'unda yalnızca şunları görür: bana atanmış aktif lead sayısı, benim lead'lerimden dönüşen sayısı, benim dönüşüm oranım, bana atanmış task'lar ve durumları, benim re-engagement havuzumdaki lead'ler. Toplam gelir, genel dönüşüm oranı, bayi bilgileri ve pipeline value gösterilmez.

### 10.3 Benchmark (Delta)

Tüm KPI'larda 30g vs 31-60g karşılaştırma: `↑ +X%` yeşil / `↓ -X%` kırmızı / `→ 0%` gri

### 10.4 Scheduled Reports

| Rapor | Frekans | Alıcılar | Not |
|---|---|---|---|
| Weekly Marketing Summary | Pazartesi 09:00 | marketing_admin, manager | Staff almaz |
| Monthly Sales Report | Ayın 1'i 09:00 | sales_admin, manager | Staff almaz |
| Campaign End Report | Kampanya bitiminde | Kampanya sahibi (created_by) + admin | Staff kendi kampanyasını alır |
| Dealer Payout Report | Ayın 1'i | manager | Yalnızca manager |
| Weekly Scoring Report | Pazartesi 09:00 | sales_admin, manager | Staff almaz |
| Monthly Attribution Report | Ayın 1'i | marketing_admin, manager | Staff almaz |

Staff kullanıcıları zamanlanmış rapor almaz. Kendi performans özetlerini dashboard'larından takip eder.

---

## 11. Veri Modelleri ve İlişkiler

### 11.1 Veri İzolasyon Servisi

```
DataScopeService (tüm controller'larda kullanılır)
  │
  ├── applyScope($query, $user)
  │     Staff → WHERE created_by = $userId OR assigned_to = $userId
  │     Admin → filtre yok
  │
  ├── canAccess($resource, $user)
  │     Staff → $resource->created_by == $userId || $resource->assigned_to == $userId
  │     Admin → true
  │
  └── getScopedDashboardData($user)
        Staff → kendi metrikleri
        Admin → tüm metrikler
```

### 11.2 Model İlişkileri

```
users
  ├── MarketingTeam (permissions)
  └── MarketingTask (assigned_to / created_by)

GuestApplication (ana lead)
  ├── lead_score / lead_score_tier
  ├── LeadSourceDatum (UTM)
  ├── lead_score_logs (puan geçmişi)
  ├── lead_touchpoints (multi-touch attribution)
  ├── automation_enrollments (aktif workflow'lar)
  ├── ab_test_assignments (A/B test varyantları)
  ├── Document (belgeler)
  └── DealerStudentRevenue (bayi)

MarketingCampaign
  ├── ab_test_id (FK | null)
  ├── LeadSourceDatum (utm eşleşmesi)
  └── MarketingExternalMetric (platform sync)

AutomationWorkflow → AutomationWorkflowNode
  └── automation_enrollments → automation_enrollment_logs

ABTest → ab_test_variants + ab_test_assignments

lead_scoring_rules (admin config)

lead_touchpoints → AttributionService (5 model)

EmailCampaign → EmailTemplate + EmailSegment → EmailSendLog
  email_unsubscribes

MarketingEvent → EventRegistration

SocialMediaAccount → SocialMediaPost → SocialMediaMonthlyMetric

CmsCategory → CmsContent → CmsContentRevision + CmsMedia

MarketingBudget
IntegrationConfig
MarketingTrackingLink → MarketingTrackingClick

DealerStudentRevenue → DealerRevenueMilestone → DealerPayoutRequest
  DealerPayoutAccount
```

---

## 12. Entegrasyon Noktaları

### 12.1 Email Marketing

| Provider | Adapter | Webhook |
|----------|---------|---------|
| Mailchimp | `MailchimpAdapter` | open/click/bounce/unsubscribe |
| SendGrid | `SendGridAdapter` | delivered/open/click/bounce |
| Zoho | `ZohoAdapter` | CRM sync |

### 12.2 Takvim

Calendly, Cal.com, Google Calendar — adapter pattern.

### 12.3 E-İmza

DocuSign, HelloSign, PandaDoc — adapter pattern.

### 12.4 Sosyal Medya Sync

```
07:00 daily        → social:sync-metrics
*/4h (aktif kamp.) → social:sync-metrics --active-campaigns-only
campaign end       → social:sync-metrics --campaign={id} --final
```

### 12.5 Webhook Güvenliği

Tüm webhook'lar signature doğrulama + `webhook_logs` tablosuna kayıt.

---

## 13. Scheduler Komutları Özeti

| Komut | Frekans | Açıklama |
|---|---|---|
| `workflow:process-waiting` | Her 5 dakika | Bekleyen enrollment'ları ilerlet |
| `workflow:check-goals` | Her saat | Goal kontrolü |
| `lead:apply-score-decay` | Her gece 02:00 | Hareketsiz lead'lerin puanını düşür |
| `abtest:check-winners` | Her saat | A/B test anlamlılık kontrolü |
| `attribution:recalculate-daily` | Her gece 03:00 | Attribution hesaplama |
| `social:sync-metrics` | 07:00 + 4h (aktif) | Sosyal medya metrikleri |
| `email:process-queue` | Her dakika | Email gönderim kuyruğu |
| `reports:generate-scheduled` | Config'e göre | Zamanlanmış raporlar |
| `lead:re-engagement-check` | Her gece 04:00 | 90g hareketsiz → pool'a taşı |

---

## 14. Route Referansı

Tüm route'lar `marketing.access` middleware'i altındadır. Ek middleware kısaltmaları: `[A]` = admin only, `[P]` = publish (admin + manager), `[S]` = scope filtreli (staff kendi verisini görür), `[SC]` = scoring config.

### Pazarlama Rotaları

```
GET  /mktg-admin/dashboard                    → Marketing Dashboard [S]
GET  /mktg-admin/campaigns                    → Kampanya listesi [S]
POST /mktg-admin/campaigns                    → Kampanya oluştur [S] (draft)
POST /mktg-admin/campaigns/{id}/approve       → Onayla [P]
GET  /mktg-admin/content                      → CMS içerik [S]
POST /mktg-admin/content/{id}/submit-review   → İncelemeye gönder [S] (kendi içeriği)
POST /mktg-admin/content/{id}/approve         → Onayla [P]
POST /mktg-admin/content/{id}/request-revision → Revizyon iste [P]
GET  /mktg-admin/email/templates              → E-posta şablonları [S]
GET  /mktg-admin/email/campaigns              → E-posta kampanyaları [S]
POST /mktg-admin/email/campaigns/{id}/approve → Email onayla [P]
GET  /mktg-admin/email/analytics              → Email metrikleri [S] (staff: kendi kampanyaları)
GET  /mktg-admin/social/metrics               → Sosyal medya [A]
GET  /mktg-admin/social/posts                 → Gönderi takvimi [S]
GET  /mktg-admin/tracking-links               → Tracking linkler [A]
GET  /mktg-admin/events                       → Etkinlikler [S]
GET  /mktg-admin/budget                       → Bütçe [A]
GET  /mktg-admin/kpi                          → KPI [A]
GET  /mktg-admin/reports/scheduled            → Zamanlanmış raporlar [A]
```

### Otomasyon Rotaları

```
GET  /mktg-admin/workflows                    → Workflow listesi [S]
POST /mktg-admin/workflows                    → Workflow oluştur [S] (draft)
GET  /mktg-admin/workflows/{id}/builder       → Visual Builder [S] (kendi workflow'u)
POST /mktg-admin/workflows/{id}/activate      → Aktifleştir [P]
GET  /mktg-admin/workflows/{id}/enrollments   → Enrollment takibi [A]
GET  /mktg-admin/workflows/{id}/analytics     → Workflow performansı [A]
GET  /mktg-admin/scoring                      → Lead Scoring dashboard [A]
GET  /mktg-admin/scoring/config               → Scoring kuralları [SC]
PUT  /mktg-admin/scoring/config/{id}          → Kural güncelle [SC]
GET  /mktg-admin/scoring/leaderboard          → Puanlı lead sıralaması [A]
GET  /mktg-admin/attribution                  → Attribution dashboard [A]
GET  /mktg-admin/attribution/compare          → Model karşılaştırma [A]
GET  /mktg-admin/abtests                      → A/B Test listesi [S]
POST /mktg-admin/abtests                      → Test oluştur [S] (draft)
GET  /mktg-admin/abtests/{id}                 → Test detay [S] (kendi testi)
POST /mktg-admin/abtests/{id}/apply-winner    → Kazananı uygula [P]
```

### Satış Rotaları

```
GET  /mktg-admin/pipeline                        → Pipeline [S] (staff: kendi lead'leri)
GET  /mktg-admin/pipeline/value                  → Değer analizi [A]
GET  /mktg-admin/pipeline/loss-analysis          → Kayıp analizi [A]
GET  /mktg-admin/pipeline/conversion-time        → Dönüşüm süresi [A]
GET  /mktg-admin/pipeline/re-engagement          → Re-engagement [S] (staff: atanmışlar)
GET  /mktg-admin/pipeline/score-analysis         → Score analizi [A]
GET  /mktg-admin/lead-sources                    → Lead kaynakları [A]
GET  /mktg-admin/lead-sources/funnel             → Funnel [A]
GET  /mktg-admin/lead-sources/utm                → UTM performansı [A]
GET  /mktg-admin/dealers                         → Bayi ilişkileri [A]
GET  /mktg-admin/dealers/payouts                 → Payout yönetimi [A] (onay: manager only)
GET  /mktg-admin/tasks                           → Görevler [S]
GET  /mktg-admin/tasks/kanban                    → Kanban [S]
```

### Ortak + Webhook + Tracking

```
GET  /mktg-admin/switch-mode/{marketing|sales}   → Mod geçiş (toggle yetkisi olanlara)
GET  /mktg-admin/notifications                    → Bildirimler [S] (kendi bildirimleri)
GET  /mktg-admin/profile                          → Profil (herkes)
GET  /mktg-admin/team                             → Ekip [A]
GET  /mktg-admin/settings                         → Ayarlar [A]
GET  /mktg-admin/integrations                     → Entegrasyonlar [A] (marketing_admin + manager)
POST /webhooks/email/{provider}                   → Email webhook (auth: signature)
POST /webhooks/calendly                           → Takvim webhook (auth: signature)
GET  /t/{code}                                    → Tracking (throttle:60/dk, public)
```

---

## 15. Özet: İki Modun Farkı

| | Pazarlama Modu | Satış Modu |
|--|---------------|------------|
| **Odak** | Marka, içerik, kampanya, otomasyon | Lead yönetimi, dönüşüm, scoring |
| **Ana KPI** | ROI, CPA, Email stats, Workflow completion, Attribution | Dönüşüm oranı, pipeline değeri, lead score, re-engagement |
| **Birincil araç** | CMS, Email, Sosyal Medya, Workflow Builder, A/B Test | Pipeline, Lead Scoring, Attribution, Bayi Yönetimi |
| **Otomasyon** | Drip kampanyalar, nurture akışları | Score-driven task oluşturma, re-engagement |
| **Raporlama** | Channel attribution, campaign ROI, A/B test sonuçları | Conversion time, loss analysis, score→conversion korelasyon |

---

*Bu doküman MentorDE Marketing & Sales Modülü master referansıdır (v3.0). Sözleşme pipeline'ı için mentorde-sozlesme-master-v5.0.md ile uyumludur.*
