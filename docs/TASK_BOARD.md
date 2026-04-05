# MentorDE — Ortak Task Board (Görev Takibi)

**Versiyon:** 2.0
**Son Güncelleme:** 2026-03-08
**Stack:** Laravel 12 / PHP 8.4 / MySQL

> **Controller:** `TaskBoardController.php`
> **Model:** `MarketingTask.php` · `TaskComment.php` · `TaskActivityLog.php`
> **Otomasyon:** `TaskAutomationService.php`
> **Middleware:** `EnsureTaskAccess.php`
> **URL:** `/tasks` (ortak) · `/tasks/{department}` (departman kuyruğu)

---

## 1. Ne İşe Yarar?

Tüm iç ekiplerin görevlerini tek bir panelden yönettiği merkezi task takip sistemidir. Görevler hem manuel (form) hem otomatik (sistem eventi → `TaskAutomationService`) oluşturulur. Task'lar arasında bağımlılık kurulabilir, yorum/not eklenebilir ve her değişiklik aktivite loguna kaydedilir.

---

## 2. Veritabanı Şeması

### 2.1 `marketing_tasks` Tablosu

| Sütun | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `company_id` | bigint, nullable | Çok-şirket izolasyonu |
| `title` | string(190) | Görev başlığı |
| `description` | text, nullable | Açıklama |
| `status` | enum | `todo` · `in_progress` · `blocked` · `done` |
| `priority` | enum | `low` · `normal` · `high` · `urgent` |
| `department` | string | `operations` · `finance` · `advisory` · `marketing` · `system` |
| `due_date` | date, nullable | Son tarih |
| `assigned_user_id` | bigint FK→users | Atanan kişi |
| `created_by_user_id` | bigint FK→users | Oluşturan kişi |
| `completed_at` | datetime, nullable | Tamamlanma zamanı |
| `is_recurring` | boolean | Tekrarlayan görev mi? |
| `recurrence_pattern` | string, nullable | `daily` · `weekly` · `monthly` |
| `recurrence_interval_days` | int, nullable | Tekrar aralığı (gün) |
| `next_run_at` | datetime, nullable | Bir sonraki tekrar zamanı |
| `parent_task_id` | bigint FK→marketing_tasks, nullable | Üst görev (tekrarlayan klonlar veya bağımlılık) |
| `depends_on_task_id` | bigint FK→marketing_tasks, nullable | Bu task'ın başlaması için önce tamamlanması gereken task |
| `escalate_after_hours` | int | Eskalasyon eşiği (saat, default: priority'ye göre) |
| `last_escalated_at` | datetime, nullable | Son eskalasyon zamanı |
| `escalation_level` | int, default 0 | Kaç kez eskale edildi (0, 1, 2, 3) |
| `is_auto_generated` | boolean | Sistem tarafından mı oluşturuldu? |
| `source_type` | string, nullable | Tetikleyen event tipi |
| `source_id` | string, nullable | Tetikleyen kaydın ID'si |
| `column_order` | int, default 0 | Kanban sıralama |
| `created_at` | datetime | — |
| `updated_at` | datetime | — |
| `deleted_at` | datetime, nullable | Soft delete |

### 2.2 `task_comments` Tablosu

| Sütun | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `task_id` | bigint FK→marketing_tasks | İlişkili görev |
| `user_id` | bigint FK→users | Yazan kişi |
| `body` | text | Yorum metni |
| `attachment_path` | string, nullable | Ek dosya (storage path) |
| `is_internal` | boolean, default true | Staff görebilir mi (false = yalnızca admin+manager) |
| `created_at` | datetime | — |
| `updated_at` | datetime | — |

### 2.3 `task_activity_logs` Tablosu (Audit Trail)

| Sütun | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `task_id` | bigint FK→marketing_tasks | İlişkili görev |
| `user_id` | bigint FK→users | Aksiyonu yapan |
| `action` | string | `created` · `status_changed` · `assigned` · `priority_changed` · `commented` · `escalated` · `reopened` · `deleted` · `dependency_completed` |
| `old_value` | string, nullable | Önceki değer |
| `new_value` | string, nullable | Yeni değer |
| `metadata` | JSON, nullable | Ek bilgi |
| `created_at` | datetime | — |

### 2.4 Migrations

| Dosya | İçerik |
|---|---|
| `2026_02_17_000001_create_marketing_tasks_table` | Ana tablo |
| `2026_02_17_000002_add_automation_fields_to_marketing_tasks_table` | Otomasyon alanları |
| `2026_02_19_000200_add_source_fields_to_marketing_tasks_table` | Source tracking |
| `2026_02_19_042000_add_department_to_marketing_tasks_table` | Departman |
| `2026_02_27_000001_add_kanban_fields_to_marketing_tasks` | Kanban alanları |
| `2026_03_08_000001_add_dependency_and_escalation_to_marketing_tasks` | Bağımlılık + eskalasyon seviyesi |
| `2026_03_08_000002_create_task_comments_table` | Yorum tablosu |
| `2026_03_08_000003_create_task_activity_logs_table` | Aktivite log |

---

## 3. Departman Yapısı

Her görev bir departmana aittir. Departman hem erişimi hem görünürlüğü belirler.

| Departman | Türkçe | Aktif Roller | Gelecek Roller |
|-----------|--------|-------------|----------------|
| `operations` | Operasyon | manager, system_admin | operations_admin, operations_staff |
| `finance` | Finans | manager, system_admin | finance_admin, finance_staff |
| `advisory` | Danışmanlık | senior, mentor | — |
| `marketing` | Marketing/Sales | marketing_admin, marketing_staff, sales_admin, sales_staff | — |
| `system` | Sistem | system_admin | system_staff |

---

## 4. Rol Bazlı Erişim — Admin/Staff Tam Ayrım

### 4.1 Task Board'a Erişim (`TASK_ACCESS_ROLES`)

```
manager · senior · mentor · system_admin · system_staff
operations_admin · operations_staff · finance_admin · finance_staff
marketing_admin · sales_admin · sales_staff · marketing_staff
```

Erişimi olmayanlar: `guest`, `student`, `dealer`

### 4.2 Süper Roller (Tüm Departmanlara Tam Erişim)

| Rol | Tüm Dept. Görür | Tüm Task'ları Görür | Tüm Task'ları Düzenler |
|---|---|---|---|
| `manager` | Evet | Evet | Evet |
| `system_admin` | Evet | Evet | Evet |

### 4.3 Admin Roller (Kendi Departmanında Tam Erişim)

Admin roller kendi departmanındaki tüm görevleri görür — kime atandığına bakılmaz.

| Rol | Departman | Tüm Task'ları Görür | Başkasının Task'ını Düzenler | Staff'a Task Atar |
|---|---|---|---|---|
| `operations_admin` | operations | Evet | Evet | Evet |
| `finance_admin` | finance | Evet | Evet | Evet |
| `marketing_admin` | marketing | Evet | Evet | Evet |
| `sales_admin` | marketing | Evet | Evet | Evet |

### 4.4 Staff Roller (Kendi Departmanında Kısıtlı Erişim)

Staff roller kendi departmanında yalnızca kendileriyle ilişkili görevleri görür.

| Rol | Departman | Görev Görünürlüğü | Düzenleme | Atama |
|---|---|---|---|---|
| `operations_staff` | operations | `assigned_to = self` VEYA `created_by = self` | Yalnızca kendi task'ları | Yalnızca kendine |
| `finance_staff` | finance | `assigned_to = self` VEYA `created_by = self` | Yalnızca kendi task'ları | Yalnızca kendine |
| `marketing_staff` | marketing | `assigned_to = self` VEYA `created_by = self` | Yalnızca kendi task'ları | Yalnızca kendine |
| `sales_staff` | marketing | `assigned_to = self` VEYA `created_by = self` | Yalnızca kendi task'ları | Yalnızca kendine |
| `system_staff` | system | `assigned_to = self` VEYA `created_by = self` | Yalnızca kendi task'ları | Yalnızca kendine |

### 4.5 Danışman Roller

| Rol | Departman | Görev Görünürlüğü | Düzenleme |
|---|---|---|---|
| `senior` | advisory | Kendi atanmış/oluşturduğu + kendi öğrencileriyle ilgili task'lar | Kendi task'ları |
| `mentor` | advisory | Kendi atanmış/oluşturduğu task'lar | Kendi task'ları |

Senior için özel kural: `assigned_to = self` VEYA `created_by = self` VEYA `source_id IN (kendi öğrenci ID'leri)` — Senior, kendi öğrencisiyle ilgili otomatik oluşturulmuş görevleri de görür (başkasına atanmış olsa bile sadece okuma).

### 4.6 Departman URL Kuyruğu → Erişim

| URL | Erişebilen Roller |
|---|---|
| `/tasks` (tümü) | manager, system_admin |
| `/tasks/operations` | Süper roller + operations_admin, operations_staff |
| `/tasks/finance` | Süper roller + finance_admin, finance_staff |
| `/tasks/advisory` | Süper roller + senior, mentor |
| `/tasks/marketing` | Süper roller + marketing_admin/staff, sales_admin/staff |
| `/tasks/system` | Süper roller + system_admin, system_staff |

### 4.7 İzolasyon Özet Tablosu

```
┌───────────────────┬────────────────┬────────────────┬────────────────┐
│                   │ GÖRÜNÜRLÜK     │ DÜZENLEME      │ ATAMA          │
├───────────────────┼────────────────┼────────────────┼────────────────┤
│ manager           │ Tümü           │ Tümü           │ Herkese        │
│ system_admin      │ Tümü           │ Tümü           │ Herkese        │
├───────────────────┼────────────────┼────────────────┼────────────────┤
│ *_admin           │ Kendi dept.    │ Kendi dept.    │ Dept. içi      │
│                   │ tümü           │ tümü           │ herkese        │
├───────────────────┼────────────────┼────────────────┼────────────────┤
│ *_staff           │ Kendi dept.    │ Kendi task'ları│ Yalnızca       │
│                   │ kendi task'ları│                │ kendine        │
├───────────────────┼────────────────┼────────────────┼────────────────┤
│ senior            │ advisory dept. │ Kendi task'ları│ Kendine +      │
│                   │ kendi + kendi  │                │ kendi öğrenci  │
│                   │ öğrencileri    │                │ task'ları      │
├───────────────────┼────────────────┼────────────────┼────────────────┤
│ mentor            │ advisory dept. │ Kendi task'ları│ Yalnızca       │
│                   │ kendi task'ları│                │ kendine        │
└───────────────────┴────────────────┴────────────────┴────────────────┘
```

---

## 5. Görev Yönetimi — Kim Ne Yapabilir?

### 5.1 Oluşturma (`POST /tasks`)

- Board'a erişimi olan herkes kendi departmanında görev oluşturabilir.
- Staff roller yalnızca kendine atayabilir (assigned_user_id = self).
- Admin roller departman içindeki herkese atayabilir.
- Süper roller herhangi bir departmanda, herkese atayarak görev oluşturabilir.
- Departman otomatik kilitlenir: scoped rol kendi departmanı dışında görev açamaz.

### 5.2 Güncelleme (`POST /tasks/{id}/update`)

Bir görevi güncelleyebilmek için şu kontrollerden biri geçerli olmalı:

| Koşul | Açıklama |
|---|---|
| Süper rol | manager, system_admin → her zaman |
| Dept. admin | Görev kendi departmanındaysa |
| Task sahibi | `created_by_user_id = self` |
| Task atananı | `assigned_user_id = self` |

Şirket kısıtı: Aktif company_id context'i varsa, farklı şirketin görevi güncellenemez.

### 5.3 Atama Değiştirme

| Rol Tipi | Atama Yetkisi |
|---|---|
| Süper rol | Herhangi bir kullanıcıya |
| *_admin | Kendi departmanındaki kullanıcılara |
| *_staff | Yalnızca kendine (başkasına atayamaz) |
| senior | Kendine veya kendi öğrencisiyle ilgili task'ı başka senior'a |

### 5.4 Toplu Güncelleme (`POST /tasks/bulk-update`)

- Checkbox ile seçilen görevlerde toplu durum, öncelik, departman, atanan değiştirme.
- Her satır için `canManage()` kontrolü ayrı ayrı yapılır.
- Staff toplu atama değişikliği yapamaz — yalnızca status ve priority değiştirebilir.

### 5.5 Tamamlama / Yeniden Açma / Silme

| Aksiyon | Route | Kural |
|---|---|---|
| Tamamla | `POST /tasks/{id}/mark-done` | canManage + status→done, completed_at→now() |
| Yeniden aç | `POST /tasks/{id}/reopen` | canManage + status→todo, completed_at→null |
| Sil | `DELETE /tasks/{id}` | Yalnızca admin + süper roller (soft delete) |

Staff görev silemez — yalnızca tamamlayabilir.

---

## 6. Task Bağımlılık Sistemi

### 6.1 Bağımlılık Yapısı

Bir task başka bir task'ın tamamlanmasına bağımlı olabilir.

```
depends_on_task_id → gösterir: "Bu task, şu task tamamlanmadan başlanamaz"
```

**Kurallar:**
- Bağımlı task oluşturulduğunda status otomatik `blocked` olur.
- Bağımlılık task'ı `done` olduğunda → bağımlı task otomatik `todo`'ya geçer + bildirim gönderilir.
- Döngüsel bağımlılık engellenir (A→B→A kontrolü).
- Bir task'ın yalnızca 1 doğrudan bağımlılığı olabilir (depends_on_task_id tek FK). Zincirleme bağımlılık ise A→B→C şeklinde kurulur.

### 6.2 Bağımlılık Akışı

```
Task A (Onboarding kickoff)
    │ status: done
    ▼ tetikler
Task B (Belge kontrol) — depends_on: Task A
    │ blocked → todo (otomatik)
    │ status: done
    ▼ tetikler
Task C (İlk process adımı) — depends_on: Task B
    │ blocked → todo (otomatik)
```

### 6.3 Onboarding Bağımlılık Örneği

Öğrenci aktive edildiğinde oluşturulan 3 görev artık bağımlılıkla zincirlenir:

```
TaskAutomationService::createOnboardingChain($studentId):
  1. "Onboarding kickoff" → priority: high, dept: advisory, due: +1 gün
  2. "Onboarding belge kontrolu" → depends_on: #1, due: +2 gün
  3. "Ilk process adimi" → depends_on: #2, due: +3 gün
```

---

## 7. Yorum/Not Sistemi

### 7.1 Task Yorumları

Her görev üzerinde ekip üyeleri iç yazışma yapabilir.

```
POST /tasks/{id}/comments → TaskCommentController::store()
GET  /tasks/{id}/comments → TaskCommentController::index()
```

**Yorum Kuralları:**
- Görevi görebilen herkes yorum ekleyebilir.
- Yorum eklendiğinde → task_activity_logs'a `commented` kaydedilir.
- Yorum eklendiğinde → task'a atanan kişiye bildirim gönderilir (kendi yorumunu hariç).
- `is_internal = true` olan yorumlar: staff dahil herkes görebilir (varsayılan).
- `is_internal = false` olan yorumlar: yalnızca admin + süper roller görebilir (hassas notlar için).
- Dosya eki desteklenir (PDF, JPG, PNG — max 10 MB).
- Yorum silinemez, sadece admin soft-delete yapabilir.

### 7.2 Yorum Görünümü

```
┌──────────────────────────────────────────────────────┐
│  Task #142 — "Guest belge kontrolu"                  │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ┌─ Ahmet (Senior) — 08.03.2026 14:20 ──────────┐  │
│  │ Pasaport belgesi eksik, öğrenciye yazdım.      │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌─ Merve (Ops Admin) — 08.03.2026 15:45 ────────┐  │
│  │ Öğrenci yeni belgeyi yükledi, kontrol ettim.   │  │
│  │ 📎 belge_kontrol_notu.pdf                      │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Yorum ekle:                                         │
│  [________________________________] [Gönder]         │
│  [📎 Dosya Ekle]                                     │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## 8. Aktivite Logu (Audit Trail)

Her görev değişikliği otomatik olarak `task_activity_logs` tablosuna kaydedilir.

### 8.1 Kaydedilen Event'ler

| Action | Açıklama | old_value → new_value |
|---|---|---|
| `created` | Görev oluşturuldu | — → task_id |
| `status_changed` | Durum değişti | `todo` → `in_progress` |
| `assigned` | Atanan değişti | `user:5` → `user:12` |
| `priority_changed` | Öncelik değişti | `normal` → `urgent` |
| `department_changed` | Departman değişti | `operations` → `advisory` |
| `commented` | Yorum eklendi | — → comment_id |
| `escalated` | Eskalasyon yapıldı | `level:0` → `level:1` |
| `reopened` | Yeniden açıldı | `done` → `todo` |
| `deleted` | Silindi (soft) | — |
| `dependency_completed` | Bağımlılık tamamlandı | `blocked` → `todo` |
| `dependency_set` | Bağımlılık eklendi | — → depends_on:task_id |
| `due_date_changed` | Son tarih değişti | `2026-03-10` → `2026-03-15` |
| `bulk_updated` | Toplu güncelleme | metadata'da detay |

### 8.2 Aktivite Log Görünümü

Task detay sayfasında "Geçmiş" sekmesi altında kronolojik olarak gösterilir. Staff yalnızca kendi görebileceği task'ların logunu görür.

---

## 9. SLA ve Eskalasyon Sistemi

### 9.1 Priority Bazlı SLA Eşikleri

| Priority | Varsayılan SLA | Eskalasyon Eşiği | Max Eskalasyon |
|---|---|---|---|
| `urgent` | 4 saat | 4 saat | 3 seviye |
| `high` | 12 saat | 12 saat | 3 seviye |
| `normal` | 24 saat | 24 saat | 2 seviye |
| `low` | 72 saat | 48 saat | 1 seviye |

Görev oluşturulduğunda `escalate_after_hours` otomatik olarak priority'ye göre set edilir. Admin/manager override edebilir.

### 9.2 Eskalasyon Zinciri

```
Seviye 0: Task oluşturuldu → atanan kişiye bildirim
    │ SLA süresi doldu
    ▼
Seviye 1: Atanan kişiye hatırlatma + departman admin'e bildirim
    │ SLA x 2 süresi doldu
    ▼
Seviye 2: Departman admin'e hatırlatma + manager'a bildirim
    │ SLA x 3 süresi doldu
    ▼
Seviye 3: Manager'a acil bildirim + task "urgent" yapılır (priority upgrade)
```

### 9.3 Eskalasyon Scheduler

```
Scheduler: her 30 dakika → task:check-escalations

TaskEscalationService::checkAndEscalate():
  1. status IN (todo, in_progress, blocked) olan task'ları bul
  2. created_at + escalate_after_hours * (escalation_level + 1) < now() ?
  3. Evet → escalation_level++, last_escalated_at = now()
  4. Bildirim gönder (eskalasyon seviyesine göre alıcı)
  5. task_activity_logs'a "escalated" kaydet
  6. Seviye 3'te priority otomatik "urgent" yapılır
```

---

## 10. Bildirim Sistemi

### 10.1 Task Bildirimleri

| Olay | Alıcı | Kanal |
|---|---|---|
| Görev atandı | Atanan kişi | In-app + email |
| Görev durumu değişti | Oluşturan + atanan | In-app |
| Yorum eklendi | Atanan kişi (kendi yorumu hariç) | In-app |
| Due date yarın | Atanan kişi | In-app + email |
| Due date bugün | Atanan kişi | In-app + email + push |
| Due date geçti (SLA) | Atanan kişi | In-app + email |
| Eskalasyon seviye 1 | Atanan + dept. admin | In-app + email |
| Eskalasyon seviye 2 | Dept. admin + manager | In-app + email |
| Eskalasyon seviye 3 | Manager | In-app + email + push |
| Bağımlılık tamamlandı | Bağımlı task'ın atananı | In-app |
| Task toplu güncellendi | Etkilenen atananlar | In-app |

### 10.2 Due Date Hatırlatma Scheduler

```
Scheduler: her gün 08:00 → task:send-due-reminders
  → due_date = tomorrow → bildirim
  → due_date = today → bildirim
  → due_date < today AND status != done → gecikmiş uyarı
```

---

## 11. Otomasyon — Sistem Tarafından Görev Oluşturma

`TaskAutomationService` şu eventlerde otomatik görev oluşturur. Her event için duplicate kontrolü yapılır: `source_type + source_id + assigned_user_id + status != done`.

### 11.1 Guest Flow — Operasyon Görevleri

| Tetikleyen Event | Görev Başlığı | Dept. | Öncelik | SLA |
|---|---|---|---|---|
| Guest ön kayıt formu submit | Guest on kayit incelemesi | operations | high | 12 saat |
| Guest ticket açıldı | Guest ticket yaniti | operations (veya ticket.dept) | normal/high | 12-24 saat |
| Guest sözleşme talep etti | Guest sozlesme hazirlama | operations | high | 12 saat |
| Guest sözleşme talep etti | Sozlesme talebi - satis gorusmesi | marketing | high | 12 saat |
| Guest imzalı sözleşme yükledi | Imzali sozlesme onayi | operations | urgent | 4 saat |
| Guest belge yükledi | Guest belge kontrolu | operations | normal | 24 saat |

**Atanan Kişi Tespiti (Guest için):**
1. `guest.assigned_senior_email` → Senior bulunursa atanır
2. Yoksa → şirketteki `operations_admin` > `manager` > `system_admin` fallback

**Marketing/Sales Görev Ataması:**
1. `marketing_admin` > `sales_admin` > `marketing_staff` > `sales_staff` > `manager` fallback

### 11.2 Student Flow — Danışmanlık Görevleri

| Tetikleyen Event | Görev Başlığı | Dept. | Öncelik | SLA |
|---|---|---|---|---|
| Student atama güncellendi | Student atama takibi | advisory | normal | 24 saat |
| Process outcome (red/düzeltme) | Student process outcome aksiyonu | advisory | high | 12 saat |
| Process outcome (diğer) | Student process outcome aksiyonu | advisory | normal | 24 saat |
| Student belge yükledi | Student belge kontrolu | advisory | normal | 24 saat |
| Öğrenci aktive edildi | Onboarding kickoff | advisory | high | 12 saat |
| (bağımlı: kickoff done) | Onboarding belge kontrolu | advisory | normal | 24 saat |
| (bağımlı: belge done) | Ilk process adimi | advisory | normal | 24 saat |

Onboarding görevleri zincirleme bağımlılıkla oluşturulur (Bölüm 6.3).

**Atanan Kişi Tespiti (Student için):**
1. `StudentAssignment.senior_email` → Senior bulunursa atanır
2. Yoksa → `operations_admin` > `manager` > `system_admin` fallback

### 11.3 Manager Talebi

| Tetikleyen Event | Görev Başlığı | Dept. | Öncelik |
|---|---|---|---|
| Manager request oluşturuldu | Manager talebi: {subject} | request_type mapping | request.priority |

`request_type → department` eşlemesi: `finance` → finance, `operations` → operations, `approval`/`advisory` → advisory, `system` → system, `marketing` → marketing, diğer → operations.

### 11.4 Mesaj/Konuşma Görevleri

| Tetikleyen Event | Görev Başlığı | Dept. | Öncelik | SLA |
|---|---|---|---|---|
| Hızlı bilgi talebi (DM) | Guest/Student hizli bilgi talebi | advisory | high | 12 saat |
| Yanıt bekliyor (DM SLA) | DM yanit bekliyor | thread.dept | high/normal | sla_hours |

Mesaj yanıtlandığında: `markConversationResponseDone()` → ilgili task otomatik `done` olarak kapanır.

### 11.5 Cross-Department Otomasyon Kuralı

Bazı olaylar birden fazla departmanda görev oluşturur (örn: sözleşme talebi → operations + marketing). Bu otomasyon tarafından yönetilir ve manuel oluşturma kısıtlamasını (staff kendi departmanı dışında açamaz) bypass etmez — yalnızca `TaskAutomationService` cross-department görev oluşturabilir.

---

## 12. Source Type Kataloğu

| source_type | Açıklama |
|---|---|
| `guest_registration_submit` | Guest ön kayıt formu gönderildi |
| `guest_ticket_opened` | Guest ticket açıldı |
| `guest_ticket_reply` | Guest mesaj yanıtladı |
| `guest_ticket_replied` | Ops guest ticketına yanıt verdi |
| `guest_contract_requested` | Guest sözleşme talep etti |
| `guest_contract_sales_followup` | Satış görüşmesi gerekiyor |
| `guest_contract_signed_uploaded` | Guest imzalı sözleşme yükledi |
| `guest_document_uploaded` | Guest belge yükledi |
| `student_assignment_upsert` | Student atama güncellendi |
| `student_process_outcome_created` | Student süreç sonucu oluştu |
| `student_document_uploaded` | Student belge yükledi |
| `student_onboarding_auto` | Onboarding zinciri (3 görev) |
| `student_step_request` | Student adım talebi |
| `manager_request_created` | Manager talebi oluşturuldu |
| `conversation_quick_request` | DM hızlı bilgi talebi |
| `conversation_response_due` | DM yanıt SLA süresi dolmak üzere |
| `conversation_message` | Konuşma mesajı bildirimi |
| `workflow_automation` | Marketing workflow'dan oluşturulan task |
| `lead_scoring_tier_change` | Lead score tier değişimi |
| `recurring_clone` | Tekrarlayan görev klonu |

---

## 13. Tekrarlayan Görevler

### 13.1 Yapı

| Alan | Açıklama |
|---|---|
| `is_recurring` | true |
| `recurrence_pattern` | `daily` · `weekly` · `monthly` |
| `recurrence_interval_days` | Tam aralık (gün), 1–365 |
| `next_run_at` | Bir sonraki klon zamanı |
| `parent_task_id` | Klonlanan ana görev |

### 13.2 Klonlama Scheduler

```
Scheduler: her gün 06:00 → task:clone-recurring

TaskRecurringService::processRecurring():
  1. is_recurring = true AND next_run_at <= now() olan task'ları bul
  2. Her biri için:
     a. Yeni task oluştur (title, description, department, priority, assigned_user_id kopyalanır)
     b. parent_task_id = orijinal task
     c. source_type = "recurring_clone"
     d. status = "todo"
     e. due_date = now() + orijinal SLA süresi
  3. Orijinal task'ın next_run_at'ını güncelle:
     - daily → +interval gün
     - weekly → +7 * interval gün
     - monthly → +30 * interval gün
  4. task_activity_logs'a kaydet
```

---

## 14. Görünüm Modları

### 14.1 Liste Görünümü

- `/tasks` veya `/tasks/{department}` — varsayılan
- Sıralama: done olmayan önce → due_date → id desc
- Limit: 300 görev
- SLA durumu renk kodu: yeşil (süre var), sarı (bugün), kırmızı (geçmiş)

### 14.2 Kanban Görünümü

- `task-kanban.js` ile yönetilir
- Sütunlar: Yapılacak · Devam Ediyor · Bloke · Tamamlandı
- HTML5 drag-and-drop ile sütun arası taşıma
- `PATCH /tasks/{id}/kanban` → status + column_order güncelleme
- LocalStorage'da son tercih saklanır

### 14.3 Filtreler

| Filtre | Seçenekler | Staff Kısıtı |
|---|---|---|
| Departman | operations, finance, advisory, marketing, system | Staff: kendi departmanı kilitli |
| Durum | todo, in_progress, blocked, done | — |
| Öncelik | low, normal, high, urgent | — |
| Atanan | Kullanıcı listesi | Staff: yalnızca kendisi |
| Kaynak | Manuel, otomasyon, source_type listesi | — |
| Tarih aralığı | created_at / due_date range | — |
| SLA durumu | Normal, uyarı (bugün), gecikmiş | — |
| Bağımlılık | Bağımlılığı var / yok | — |
| Tekrarlayan | Evet / hayır | — |

### 14.4 Kanban Görünüm Varyantları

| Görünüm | Sütunlar | Açıklama |
|---|---|---|
| Status bazlı (varsayılan) | todo, in_progress, blocked, done | Standart |
| Priority bazlı | low, normal, high, urgent | Öncelik odaklı |
| Departman bazlı | operations, finance, advisory, marketing, system | Sadece süper roller |

---

## 15. KPI ve Raporlama

### 15.1 Task Board Metrikleri

| Metrik | Hesaplama | Görünürlük |
|---|---|---|
| Toplam açık görev | COUNT(status != done) | Admin: tüm dept. / Staff: kendi |
| Bugün due olan | COUNT(due_date = today, status != done) | Admin: tüm / Staff: kendi |
| Gecikmiş görev | COUNT(due_date < today, status != done) | Admin: tüm / Staff: kendi |
| Ort. tamamlanma süresi | AVG(completed_at - created_at) | Admin |
| SLA uyum oranı | completed_within_sla / total_completed * 100 | Admin |
| Departman bazlı yük | COUNT by department, status != done | Süper roller |
| Kişi bazlı yük | COUNT by assigned_user_id, status != done | Admin (kendi dept.) |
| Eskalasyon sayısı (30g) | COUNT(escalation_level > 0) | Admin |
| Otomatik vs manuel oranı | is_auto_generated distribution | Admin |

### 15.2 Staff Kendi Dashboard'u

Staff kullanıcılar yalnızca kendi metriklerini görür:
- Benim açık görevlerim (sayı)
- Bugün due olan görevlerim
- Gecikmiş görevlerim
- Son 30 günde tamamladığım görev sayısı
- Benim ortalama tamamlanma sürem

---

## 16. Marketing Admin Panel — Ayrı Task Görünümü

`/mktg-admin/tasks` → `MarketingAdmin\TaskController`

Ortak task board ile aynı tabloyu kullanır ancak:
- Otomatik `department = 'marketing'` filtresi
- marketing_staff: kendi task'ları (scope filtreli)
- marketing_admin/sales_admin: tüm marketing task'ları
- Liste + Kanban toggle
- Marketing v3.0 dokümanındaki `DataScopeService` ile uyumlu

---

## 17. Route Referansı

```
GET    /tasks                       → Liste (tüm dept. — süper roller)
GET    /tasks/{department}          → Departman kuyruğu
POST   /tasks                       → Görev oluştur
POST   /tasks/{id}/update           → Güncelle
POST   /tasks/bulk-update           → Toplu güncelle
POST   /tasks/{id}/mark-done        → Tamamla
POST   /tasks/{id}/reopen           → Yeniden aç
DELETE /tasks/{id}                   → Sil (admin + süper)
PATCH  /tasks/{id}/kanban           → Kanban sıralama
POST   /tasks/{id}/comments         → Yorum ekle
GET    /tasks/{id}/comments         → Yorumları getir
GET    /tasks/{id}/activity          → Aktivite logu
GET    /tasks/metrics                → KPI dashboard (admin)
GET    /tasks/my-metrics             → Kendi metriklerim (herkes)
```

---

## 18. Scheduler Komutları Özeti

| Komut | Frekans | Açıklama |
|---|---|---|
| `task:check-escalations` | Her 30 dakika | SLA kontrolü + eskalasyon |
| `task:send-due-reminders` | Her gün 08:00 | Due date hatırlatma |
| `task:clone-recurring` | Her gün 06:00 | Tekrarlayan görev klonlama |

---

## 19. İlgili Dosyalar

| Dosya | Rol |
|---|---|
| `app/Http/Controllers/TaskBoardController.php` | Ana controller — CRUD, filtre, yetki |
| `app/Http/Controllers/TaskCommentController.php` | Yorum CRUD |
| `app/Http/Controllers/MarketingAdmin/TaskController.php` | Marketing panel task görünümü |
| `app/Services/TaskAutomationService.php` | Otomatik görev oluşturma |
| `app/Services/TaskEscalationService.php` | SLA kontrol ve eskalasyon |
| `app/Services/TaskRecurringService.php` | Tekrarlayan görev klonlama |
| `app/Services/DataScopeService.php` | Admin/staff veri izolasyonu |
| `app/Http/Middleware/EnsureTaskAccess.php` | Giriş kapısı — TASK_ACCESS_ROLES |
| `app/Models/MarketingTask.php` | Görev modeli |
| `app/Models/TaskComment.php` | Yorum modeli |
| `app/Models/TaskActivityLog.php` | Aktivite log modeli |
| `resources/views/tasks/index.blade.php` | Ortak task board UI |
| `resources/views/marketing-admin/tasks/index.blade.php` | Marketing panel UI |
| `public/js/task-board.js` | Liste görünümü JS |
| `public/js/task-kanban.js` | Kanban HTML5 drag-drop |
| `storage/logs/task-automation.log` | Otomasyon log dosyası |

---

## 20. Özet: Versiyon Farkları (v1 → v2)

| Özellik | v1 (Mevcut) | v2 (Yeni) |
|---|---|---|
| Admin/Staff ayrımı | system_staff = tam erişim (hata) | Staff = sadece kendi task'ları |
| Task bağımlılık | Yok | depends_on_task_id ile zincirleme |
| Yorum sistemi | Yok | task_comments tablosu + dosya eki |
| Aktivite logu | Yok | task_activity_logs (tam audit trail) |
| SLA/Eskalasyon | Yarım (scheduler yok) | Priority bazlı 3 seviye + scheduler |
| Tekrarlayan görev | next_run_at var, klonlama yok | Scheduler ile otomatik klonlama |
| Bildirim sistemi | Tanımsız | Due date hatırlatma + eskalasyon + yorum |
| Filtreler | Basit | Tarih, SLA, source_type, bağımlılık, tekrarlayan |
| Kanban varyantları | Sadece status | Status + priority + departman bazlı |
| KPI/Metrik | Yok | Tamamlanma süresi, SLA uyum, yük dağılımı |
| Cross-dept otomasyon | Tanımsız | Belgelenmiş kural (sadece otomasyon bypass) |
| Senior özel kuralı | Yok | Kendi öğrenci task'larını görür (okuma) |

---

*Bu doküman MentorDE Ortak Task Board master referansıdır (v2.0). Marketing modülü (v3.0) ve Sözleşme sistemi (v5.0) ile uyumludur.*
