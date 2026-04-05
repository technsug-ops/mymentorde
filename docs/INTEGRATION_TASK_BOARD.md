# Entegrasyon: Task Board

---

## Amaç

Tüm departman görevlerini merkezi olarak yönetir. Rol tabanlı görünürlük ve yetki izolasyonu ile her departmandaki görevler ilgili kişilere otomatik atanır. Eskalasyon, tekrarlayan görev ve zaman takibi yetenekleri içerir.

---

## Temel Model: MarketingTask

**Dosya:** `app/Models/MarketingTask.php`
**Tablo:** `marketing_tasks`

| Alan | Tip | Açıklama |
|------|-----|----------|
| `title` | string(190) | Görev başlığı |
| `description` | text | Açıklama |
| `status` | enum | Durum (aşağıda) |
| `priority` | enum | Öncelik (aşağıda) |
| `department` | enum | Departman (aşağıda) |
| `due_date` | date | Son tarih |
| `completed_at` | timestamp | Tamamlanma zamanı |
| `assigned_user_id` | FK | Atanan kullanıcı |
| `created_by_user_id` | FK | Oluşturan kullanıcı |
| `source_type` | string | Kaynak tipi (19+ değer) |
| `source_id` | string | Kaynak kayıt ID |
| `depends_on_task_id` | FK | Bağımlı görev (bağımlıysa status=blocked) |
| `is_recurring` | bool | Tekrarlayan görev mi |
| `recurrence_pattern` | enum | `daily / weekly / monthly` |
| `recurrence_interval_days` | int | Tekrar aralığı (gün) |
| `next_run_at` | timestamp | Sonraki klon zamanı |
| `escalate_after_hours` | int | SLA eşiği (saat) |
| `escalation_level` | int | Mevcut eskalasyon seviyesi (0-3) |
| `last_escalated_at` | timestamp | Son eskalasyon zamanı |
| `is_auto_generated` | bool | Otomatik oluşturuldu (klon) |
| `parent_task_id` | FK | Klon kaynağı task |
| `template_id` | FK | Görev şablonu |
| `process_type` | string | Süreç tipi |
| `workflow_stage` | string | Workflow aşaması |
| `checklist_total` | int | Checklist madde sayısı |
| `checklist_done` | int | Tamamlanan checklist sayısı |
| `estimated_hours` | decimal | Tahmini saat |
| `actual_hours` | decimal | Gerçekleşen saat |
| `company_id` | FK | Multi-tenant izolasyon |

### Durum Değerleri

| Değer | Türkçe |
|-------|--------|
| `todo` | Yapılacak |
| `in_progress` | Devam Ediyor |
| `in_review` | İncelemede |
| `on_hold` | Beklemede |
| `blocked` | Bloke |
| `done` | Tamamlandı |
| `cancelled` | İptal |

### Öncelik Değerleri

`low` / `normal` / `high` / `urgent`

`MarketingTask::defaultSlaHours(priority)` — her öncelik için varsayılan SLA saat değeri

### Departman Değerleri

`operations` / `finance` / `advisory` / `marketing` / `system`

---

## Rol Tabanlı Erişim Matrisi

| Rol | Kategori | Erişim |
|-----|----------|--------|
| `manager`, `system_admin` | Global Viewer | Tüm departman + tüm görevler |
| `operations_admin`, `finance_admin`, `marketing_admin`, `sales_admin` | Dept Admin | Kendi departmanındaki tüm görevler |
| `operations_staff` | Dept Staff | Sadece kendi görevleri (operations dept) |
| `finance_staff` | Dept Staff | Sadece kendi görevleri (finance dept) |
| `marketing_staff`, `sales_staff` | Dept Staff | Sadece kendi görevleri (marketing dept) |
| `senior`, `mentor` | Advisory Staff | Kendi görevleri + atanmış öğrenci kaynak görevleri (advisory dept) |
| `system_staff` | Dept Staff | Sadece kendi görevleri (system dept) |

### Rol → Departman Eşleşmesi

```php
operations_admin / operations_staff → 'operations'
finance_admin / finance_staff       → 'finance'
marketing_admin / marketing_staff / sales_admin / sales_staff → 'marketing'
senior / mentor                     → 'advisory'
system_staff                        → 'system'
manager / system_admin              → null (tüm departmanlar)
```

### canManage() Kuralları

```
Global Viewer → her zaman true
Dept Admin → kendi departmanındaki tüm task'lar
Staff/Senior → sadece assigned_user_id == userId VEYA created_by_user_id == userId
```

**Atama Kısıtı:** Staff/senior kendi dışında birine atayamaz — `store()`/`update()` da `assigned_user_id = $userId` zorlanır.

---

## Controller: TaskBoardController

**Dosya:** `app/Http/Controllers/TaskBoardController.php`

### Tüm Metodlar

| Metod | Route | Açıklama |
|-------|-------|----------|
| `index()` | GET `/tasks[/{department}]` | Görev listesi (filtre + KPI stats) |
| `store()` | POST `/tasks` | Yeni görev oluştur |
| `update()` | PUT `/tasks/{id}` | Görev güncelle |
| `bulkUpdate()` | POST `/tasks/bulk` | Toplu durum güncelle |
| `markDone()` | POST `/tasks/{id}/done` | Tamamlandı olarak işaretle |
| `reopen()` | POST `/tasks/{id}/reopen` | Görevi yeniden aç (→ todo) |
| `destroy()` | DELETE `/tasks/{id}` | Görevi sil (soft delete) |
| `kanbanUpdate()` | PUT `/tasks/{id}/kanban` | Kanban sütun değişikliği (JSON) |
| `kanbanData()` | GET `/tasks/kanban` | Kanban verisi (JSON) |
| `gantt()` | GET `/tasks/gantt` | Gantt chart verisi (JSON) |
| `show()` | GET `/tasks/{id}` | Görev detay sayfası |
| `detail()` | GET `/tasks/{id}/detail` | Görev detay JSON |
| `myMetrics()` | GET `/tasks/my-metrics` | Kişisel KPI JSON |
| `activityLog()` | GET `/tasks/{id}/activity` | Aktivite logu JSON |
| `requestReview()` | POST `/tasks/{id}/review` | İncelemeye gönder (→ in_review) |
| `approve()` | POST `/tasks/{id}/approve` | Onayla (→ done) |
| `requestRevision()` | POST `/tasks/{id}/revision` | Revizyon iste (→ in_progress) |
| `hold()` | POST `/tasks/{id}/hold` | Beklet (→ on_hold) |
| `resume()` | POST `/tasks/{id}/resume` | Devam et (→ in_progress) |
| `cancel()` | POST `/tasks/{id}/cancel` | İptal et (→ cancelled) |
| `checklistStore()` | POST `/tasks/{id}/checklist` | Checklist maddesi ekle |
| `checklistToggle()` | PUT `/tasks/{id}/checklist/{itemId}` | Checklist toggle |
| `checklistDestroy()` | DELETE `/tasks/{id}/checklist/{itemId}` | Checklist sil |
| `checklistReorder()` | POST `/tasks/{id}/checklist/reorder` | Checklist sırala |
| `watch()` | POST `/tasks/{id}/watch` | Göreve abone ol |
| `unwatch()` | DELETE `/tasks/{id}/watch` | Abonelikten çık |
| `watchersList()` | GET `/tasks/{id}/watchers` | Abone listesi JSON |
| `timeStart()` | POST `/tasks/{id}/time/start` | Zaman sayacı başlat |
| `timeStop()` | POST `/tasks/{id}/time/stop` | Zaman sayacı durdur |
| `timeList()` | GET `/tasks/{id}/time` | Zaman girişleri listesi JSON |
| `taskReport()` | GET `/tasks/report` | Departman/kullanıcı bazlı rapor JSON |

### store() Validasyon Alanları

```
title (max:190), description (max:2000), status, priority, department,
due_date, assigned_user_id, depends_on_task_id,
is_recurring, recurrence_pattern (daily/weekly/monthly), recurrence_interval_days (1-365),
escalate_after_hours (1-720), process_type, workflow_stage,
template_id (exists:task_templates,id), actual_hours
```

**Bağımlılık varsa:** `status` otomatik olarak `blocked` yapılır.

**Template uygulanırsa:** `task_template_items` → `task_checklists` otomatik oluşturulur.

### index() Filtreler

`status`, `priority`, `assignee`, `source_type`, `department`, `sla` (normal/warn/overdue), `dependency` (yes/no), `recurring` (yes/no), `process_type`, `due_from`, `due_to`

**Limit:** 300 görev / sorgu

### index() Stats Alanları

`total`, `todo`, `in_progress`, `in_review`, `on_hold`, `blocked`, `done`, `cancelled`, `overdue`

---

## Source Type Değerleri (20 adet)

| source_type | Tetikleyici |
|-------------|-------------|
| `guest_registration_submit` | Guest kayıt formu gönderildi |
| `guest_ticket_opened` | Guest ticket açıldı |
| `guest_ticket_reply` | Guest ticket yanıtı |
| `guest_ticket_replied` | Ops tarafından yanıtlandı |
| `guest_contract_requested` | Guest sözleşme talep etti |
| `guest_contract_sales_followup` | Sözleşme satış takibi |
| `guest_contract_signed_uploaded` | İmzalı sözleşme yüklendi |
| `guest_document_uploaded` | Guest belge yükledi |
| `student_assignment_upsert` | Öğrenci atama güncellendi |
| `student_process_outcome_created` | Process outcome oluştu |
| `student_document_uploaded` | Öğrenci belge yükledi |
| `student_onboarding_auto` | Öğrenci onboarding otomasyonu |
| `student_step_request` | Öğrenci adım talebi |
| `manager_request_created` | Manager request oluştu |
| `conversation_quick_request` | DM hızlı talep |
| `conversation_response_due` | DM yanıt bekleniyor |
| `conversation_message` | DM mesaj bildirimi |
| `workflow_automation` | Marketing workflow düğümü |
| `lead_scoring_tier_change` | Lead skor tier değişimi |
| `recurring_clone` | Tekrarlayan görev klonu |

---

## TaskAutomationService

**Dosya:** `app/Services/TaskAutomationService.php`

Olay tabanlı otomatik görev oluşturma. `createTaskIfMissing()` — aynı `(company_id, source_type, source_id, assigned_user_id, status != done)` kombinasyonu varsa yeni görev oluşturmaz.

### Atanan Kişi Önceliği

```
1. guest.assigned_senior_email → senior kullanıcısı
2. ticket.assigned_user_id → atanmış kullanıcı
3. Departmana göre rol eşleştirme
4. manager → operations_admin → system_admin sırası
```

---

## TaskEscalationService

**Dosya:** `app/Services/TaskEscalationService.php`

```
MAX_LEVEL = 3
```

### checkAndEscalate()

```
Hedef: status ∈ {todo, in_progress, blocked} VE escalation_level < 3 VE completed_at NULL
shouldEscalate():
  threshold_hours = escalate_after_hours × (escalation_level + 1)
  baseline = last_escalated_at ?? created_at
  → baseline + threshold_hours geçti mi?

escalate():
  escalation_level++
  last_escalated_at = now()
  Level 3'te → priority = 'urgent'
  TaskActivityLog::record('escalated')
```

### Bildirim Mantığı

| Seviye | Alıcılar |
|--------|----------|
| Level 1 | Atanan kullanıcı + dept admin roller |
| Level 2 | Level 1 + manager |
| Level 3 | Level 2 (aynı, priority → urgent) |

**Dept Admin Rolleri:**
- `operations` → `operations_admin`, `manager`
- `finance` → `finance_admin`, `manager`
- `advisory` → `manager`
- `marketing` → `marketing_admin`, `sales_admin`, `manager`
- `system` → `system_admin`, `manager`

**Cron:** Her 30 dakikada bir çalışır.

---

## TaskRecurringService

**Dosya:** `app/Services/TaskRecurringService.php`

### processRecurring()

```
Hedef: is_recurring = true VE next_run_at <= now() VE deleted_at NULL

cloneTask():
  → Yeni task (is_auto_generated=true, is_recurring=false, source_type='recurring_clone', parent_task_id)
  → due_date = now() + escalate_after_hours
  → Orijinal task'ın next_run_at güncellenir:
      daily   → + interval gün
      monthly → + interval × 30 gün
      weekly  → + interval × 7 gün (varsayılan)
```

**Cron:** Her gün 06:00'da çalışır.

---

## TaskTemplateService

**Dosya:** `app/Services/TaskTemplateService.php`

- `task_templates` + `task_template_items` tabloları
- `store()` anında `template_id` verilirse → `TaskTemplateItem`'lar → `TaskChecklist`'e dönüştürülür
- `checklist_total` / `checklist_done` sayaçları güncellenir

---

## İlgili Modeller

| Model | Tablo | Açıklama |
|-------|-------|----------|
| `MarketingTask` | `marketing_tasks` | Ana görev |
| `TaskActivityLog` | `task_activity_logs` | Tüm değişikliklerin audit logu |
| `TaskChecklist` | `task_checklists` | Görev alt maddeleri |
| `TaskTimeEntry` | `task_time_entries` | Zaman takibi kayıtları |
| `TaskWatcher` | `task_watchers` | Görev aboneleri |
| `TaskTemplateItem` | `task_template_items` | Şablon checklist maddeleri |

---

## Kanban & Gantt

**Kanban:** `GET /tasks/kanban` — departman filtreli, sütun bazlı gruplama
**Kanban Update:** `PUT /tasks/{id}/kanban` — sütun (status) ve sıra değişikliği JSON

**Gantt:** `GET /tasks/gantt` — `created_at` bazlı zaman aralığı, her görev başlangıç/bitiş bar verisi

**Frontend:** `public/js/task-board.js`, `public/js/task-kanban.js` — HTML5 drag-drop, `switchView()`, `loadKanban()`

---

## taskReport() Verisi

`GET /tasks/report?start=YYYY-MM-DD&end=YYYY-MM-DD`

```json
{
  "period": { "start": "...", "end": "..." },
  "by_department": [{ "department": "...", "total": 0, "done": 0, "overdue": 0, "avg_actual_hours": 0 }],
  "by_user": [{ "user_id": 0, "total": 0, "done": 0, "avg_actual_hours": 0 }],
  "summary": { "total": 0, "done": 0, "overdue": 0, "accuracy": 95.2 }
}
```

**Tahmin Doğruluğu:** `1 - |actual - estimated| / estimated` (ortalama, actual>0 ve estimated>0 olanlar)

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller | `app/Http/Controllers/TaskBoardController.php` |
| Model | `app/Models/MarketingTask.php` |
| Service (Otomasyon) | `app/Services/TaskAutomationService.php` |
| Service (Eskalasyon) | `app/Services/TaskEscalationService.php` |
| Service (Tekrarlayan) | `app/Services/TaskRecurringService.php` |
| Service (Şablon) | `app/Services/TaskTemplateService.php` |
| Service (Feedback) | `app/Services/TaskFeedbackService.php` |
| View | `resources/views/tasks/index.blade.php` |
| JS | `public/js/task-board.js`, `public/js/task-kanban.js` |
