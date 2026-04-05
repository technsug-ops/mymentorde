# Entegrasyon: Workflow Motoru & Süreç Otomasyonu

---

## Amaç

İki ayrı workflow sistemi bulunur:
1. **AutomationWorkflow** — Guest/lead bazlı marketing otomasyon akışları (Mailchimp Automations benzeri)
2. **ProcessDefinition / WorkflowEngineService** — Öğrenci süreç adımları takibi

---

## Sistem 1 — AutomationWorkflow (Marketing Otomasyonu)

### Temel Kavramlar

| Model | Açıklama |
|-------|----------|
| `AutomationWorkflow` | Akış tanımı — trigger + node listesi + enrollment limiti |
| `AutomationWorkflowNode` | Tek node — tipi, konfigürasyonu, görsel konumu, bağlantıları |
| `AutomationEnrollment` | Bir guest'in akıştaki kaydı — mevcut node, sonraki kontrol zamanı |
| `AutomationEnrollmentLog` | Her node çalışmasının audit logu |

### AutomationWorkflow Alanları

| Alan | Açıklama |
|------|----------|
| `company_id` | Firma bağlantısı |
| `name` / `description` | Akış adı |
| `status` | `draft / active / paused / archived` |
| `trigger_type` | `guest_registered / contract_approved / manual / scheduled` vb. |
| `trigger_config` | JSON — tetikleyici parametreleri |
| `is_recurring` | Tekrarlayan enrollment izni |
| `enrollment_limit` | Maksimum enrollment sayısı |
| `approved_by` / `approved_at` | Onay bilgisi |

### AutomationWorkflowNode Alanları

| Alan | Açıklama |
|------|----------|
| `workflow_id` | Bağlı workflow |
| `node_type` | Node tipi (aşağıda) |
| `node_config` | JSON — node parametreleri |
| `sort_order` | Sıra numarası |
| `position_x/y` | Görsel builder koordinatı |
| `connections` | JSON — sonraki node bağlantıları (`{next, true, false, a, b}`) |

### 12 Node Tipi

| Node Tipi | Açıklama |
|-----------|----------|
| `send_email` | E-posta gönder |
| `send_notification` | In-app / WhatsApp bildirim |
| `wait` | Belirli süre bekle (`duration_hours`) |
| `wait_until` | Belirli tarih/saat bekle |
| `create_task` | Görev oluştur (TaskAutomationService) |
| `add_score` | Lead skoru güncelle |
| `update_field` | GuestApplication alanını güncelle |
| `condition` | Koşul → true/false dallanma |
| `goal_check` | Hedef gerçekleşti mi kontrolü |
| `ab_split` | Ağırlıklı A/B split (`split_a` %) |
| `move_to_segment` | E-posta segmentine taşı |
| `exit` | Akıştan çıkar |

### AutomationEnrollment Yaşam Döngüsü

```
enroll (trigger tetiklendi)
  → active (node'lar çalışıyor)
    → waiting (wait/wait_until node'u — next_check_at)
      → active (süre doldu)
    → completed (exit node veya son node)
    → exited (goal karşılandı)
    → errored (exception)
```

### Cron Görevleri

| Komut | Schedule | Açıklama |
|-------|----------|----------|
| `workflow:process-waiting` | Her 5 dakika | `next_check_at` geçmiş enrollment'ları işle |
| `workflow:check-goals` | Periyodik | Goal node kontrolü |

### A/B Split (Düzeltildi)

```php
case 'ab_split':
    $splitA = (int) ($config['split_a'] ?? 50);
    $rand   = random_int(1, 100);
    $branch = $rand <= $splitA ? 'a' : 'b';
    $this->advanceToNextNode($enrollment, $node, $branch);
    break;
```

### Builder UI

| Endpoint | Açıklama |
|----------|----------|
| `GET /api/workflows/{id}/builder` | Builder verisi (JSON) |
| `POST /api/workflows/{id}/builder/save` | Node pozisyon + bağlantı kaydet |
| `POST /api/workflows/{id}/simulate` | Simülasyon modu (enrollment gerçekleşmez) |

---

## Sistem 2 — ProcessDefinition (Öğrenci Süreç Takibi)

---

## WorkflowEngineService

**Dosya:** `app/Services/WorkflowEngineService.php`

### Node Tipleri

| Node Tipi | Açıklama |
|-----------|----------|
| `action` | Belirli bir aksiyon gerçekleştir (belge talebi, bildirim vb.) |
| `condition` | Koşul kontrolü → farklı dal |
| `ab_split` | A/B testi — ağırlıklı rastgele dallanma |
| `wait` | Belirli süre bekle |
| `notification` | Bildirim gönder |
| `add_score` | Lead skoruna puan ekle |
| `task` | Görev oluştur |

### A/B Split Akışı (düzeltildi)

```php
case 'ab_split':
    $splitA = (int) ($config['split_a'] ?? 50);
    $rand   = random_int(1, 100);
    $branch = $rand <= $splitA ? 'a' : 'b';
    $this->logAction($enrollment->id, $node->id, 'ab_split_' . $branch, [
        'split_a' => $splitA,
        'split_b' => 100 - $splitA,
        'rand'    => $rand,
        'chosen'  => $branch,
    ]);
    $this->advanceToNextNode($enrollment, $node, $branch);
    break;
```

**Config:** `{ "split_a": 50 }` → %50 A, %50 B dallanması

### advanceToNextNode()
Seçilen dal ('a', 'b', 'true', 'false') veya varsayılan 'next' ile bir sonraki node'a geçer.

### logAction()
`ProcessOutcome` tablosuna kayıt atar.

---

## ProcessDefinition Modeli

**Dosya:** `app/Models/ProcessDefinition.php`
**Tablo:** `process_definitions`

| Alan | Açıklama |
|------|----------|
| `company_id` | Firma bağlantısı |
| `name` | Akış adı |
| `trigger_type` | `guest_registered / contract_approved / manual` vb. |
| `nodes` | JSON — node dizisi |
| `is_active` | Aktif mi |

### Node Yapısı (JSON)
```json
{
    "id": "node_1",
    "type": "condition",
    "config": { "field": "lead_score", "operator": ">", "value": 50 },
    "next": { "true": "node_2", "false": "node_3" }
}
```

---

## ProcessOutcome Modeli

**Dosya:** `app/Models/ProcessOutcome.php`
**Tablo:** `process_outcomes`

| Alan | Açıklama |
|------|----------|
| `student_id` | Öğrenci ID |
| `process_step` | Hangi adım |
| `outcome_type` | `success / rejection / correction_request / info` |
| `details_tr` | Türkçe açıklama |
| `details_de` | Almanca açıklama |
| `visibility` | `senior_only / student_visible / dealer_visible` |
| `created_at` | Oluşturma zamanı |

**Middleware:** `CheckProcessOutcomeVisibility` — görünürlük kontrol

---

## ProcessOutcomeService

**Dosya:** `app/Services/ProcessOutcomeService.php`

- `create(student_id, step, type, details, visibility)` — yeni outcome oluştur
- Otomatik task tetiklemesi: `TaskAutomationService::ensureStudentOutcomeTask()`
- Bildirim tetiklemesi: `NotificationService`

---

## TaskAutomationService

**Dosya:** `app/Services/TaskAutomationService.php`

Olay tabanlı (event-driven) otomatik görev oluşturma servisi.

### Tetiklenme Noktaları

| Olay | Metod |
|------|-------|
| Guest kayıt formu | `ensureGuestRegistrationReviewTask()` |
| Guest ticket | `ensureGuestTicketTask()` |
| Guest sözleşme talebi | `ensureContractReviewTask()` |
| İmzalı sözleşme | `ensureSignedContractTask()` |
| Guest belge yükleme | `ensureGuestDocumentTask()` |
| Öğrenci atama | `ensureStudentAssignmentTask()` |
| Process outcome | `ensureStudentOutcomeTask()` |
| Öğrenci belge | `ensureStudentDocumentTask()` |
| Öğrenci onboarding | `ensureStudentOnboardingTasks()` |
| Manager request | `ensureManagerRequestTask()` |
| DM hızlı talep | `ensureConversationQuickRequestTask()` |
| DM yanıt bekleniyor | `ensureConversationResponseTask()` |

### Tekrar Önleme

`createTaskIfMissing()` — aynı `(company_id, source_type, source_id, assigned_user_id, status != done)` kombinasyonu varsa yeni görev oluşturmaz.

### Atanacak Kişi Önceliği

```
1. guest.assigned_senior_email → senior
2. ticket.assigned_user_id → atanmış kullanıcı
3. Departmana göre rol eşleştirme
4. manager → operations_admin → system_admin
```

---

## EscalationService

**Dosya:** `app/Services/EscalationService.php`

SLA tabanlı kural değerlendirme ve bildirim tetiklemesi.

### EscalationRule Modeli

| Alan | Açıklama |
|------|----------|
| `entity_type` | `guest_ticket / dm_thread / task` |
| `condition_hours` | Kaç saat sonra tetiklenir |
| `action_type` | `notify / reassign / escalate` |
| `target_role` | Bildirim gönderilecek rol |
| `is_active` | Aktif mi |

### Akış

```
Görev/ticket oluşturulur (SLA saati atanır)
→ EscalationService::checkPendingEscalations()
→ Süre aşıldıysa → EscalationEvent oluşturulur
→ NotificationService çağrılır (manager + ilgili)
```

**Command:** Periyodik cron ile tetiklenir

---

## Guest İlerleme Takibi (F5)

**Controller:** `Guest\PortalController::dashboard()`

### Timeline Adımları
1. Başvuru Formu
2. Ön Değerlendirme
3. Sözleşme
4. Belgeler
5. Onay
6. Kayıt Tamamlandı

### `$nextStep` CTA
```php
$nextStep = collect($progress['steps'])->firstWhere('done', false);
// → ['label' => 'Sözleşme', 'url' => '/guest/contract']
```

**View:** Yatay timeline (CSS flex, no JS) + "Sıradaki Adım" CTA card

---

## Workflow API (Manager)

| Route | Açıklama |
|-------|----------|
| `GET /manager/workflows` | Akış tanımı listesi |
| `POST /manager/workflows` | Yeni akış oluştur |
| `PUT /manager/workflows/{id}` | Akış güncelle |
| `DELETE /manager/workflows/{id}` | Akış sil |
| `POST /manager/workflows/{id}/activate` | Aktif yap |

---

## ProcessOutcome Görünürlük

`CheckProcessOutcomeVisibility` middleware:

| `visibility` | Erişim |
|-------------|--------|
| `senior_only` | Sadece senior görür |
| `student_visible` | Senior + student görür |
| `dealer_visible` | Senior + student + dealer görür |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Model (Workflow) | `app/Models/AutomationWorkflow.php` |
| Model (Node) | `app/Models/AutomationWorkflowNode.php` |
| Model (Enrollment) | `app/Models/AutomationEnrollment.php` |
| Model (Log) | `app/Models/AutomationEnrollmentLog.php` |
| Service (Engine) | `app/Services/WorkflowEngineService.php` |
| Service (Otomasyon) | `app/Services/TaskAutomationService.php` |
| Service (Escalation) | `app/Services/EscalationService.php` |
| Service (Outcome) | `app/Services/ProcessOutcomeService.php` |
| Model (Process) | `app/Models/ProcessDefinition.php` |
| Model (Outcome) | `app/Models/ProcessOutcome.php` |
| Model (Kural) | `app/Models/EscalationRule.php` |
| Model (Olay) | `app/Models/EscalationEvent.php` |
| Middleware | `app/Http/Middleware/CheckProcessOutcomeVisibility.php` |
| Controller (Admin) | `app/Http/Controllers/Api/ProcessDefinitionController.php` |
| Controller (Outcome) | `app/Http/Controllers/Api/ProcessOutcomeController.php` |
