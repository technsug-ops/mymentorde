# Sessizlik Check-In Touchpoint Akışı

**Başlangıç:** 2026-04-29
**Sahibi:** Manager (finans admin gibi ileride başka role devredilebilir, şimdilik manager)

## Hedef

Aday ve öğrencide **N gün** boyunca timeline'da hareket yoksa sistem otomatik
"süreciniz aktif olarak devam ediyor" tipi bir touchpoint (in-app notif +
event log) düşürür. Müşteri "unutulduk" hissinden kurtulur.

## Tasarım

### Stage haritası

**Aday (lead_status üzerinden):**
| Stage         | Default cadence |
|---------------|-----------------|
| new           | 7 gün           |
| contacted     | 7 gün           |
| qualified     | 7 gün           |
| converted     | EXCLUDED (student tarafına geçti) |
| lost          | EXCLUDED        |

**Öğrenci (kompozit stage tespiti):**
| Stage      | Tespit                                           | Default cadence |
|------------|--------------------------------------------------|-----------------|
| visa       | StudentVisaApplication aktif (status pending/active) | 14 gün       |
| uni_assist | StudentUniversityApplication aktif, visa yok    | 7 gün           |
| general    | Hiçbiri yok (genel takip)                        | 7 gün           |

### Override hiyerarşisi (priority)
1. **Kişi bazında**: `guest_applications.silence_checkin_days_override` / `users.silence_checkin_days_override` (smallint nullable)
2. **Şirket bazında**: `companies.silence_checkin_overrides` JSON (stage → days)
3. **Config default**: `config/brand.php` `silence_checkin_days` array

### Sessizlik tetik formülü
```
last_activity = MAX(updated_at, last_senior_action_at ?? 0, last_silence_checkin_at ?? 0)
trigger_if   = (now() - last_activity) >= effective_cadence_days
                AND silence_checkin_paused_at IS NULL
```

`last_silence_checkin_at` — dedup amacıyla kullanılır (üst üste check-in yok).

### Touchpoint içeriği

In-app notification body:
> 📍 Süreciniz aktif olarak devam ediyor — durum: **{stage_label}**, danışman: **{senior_name}**, son işlem: **{X gün önce}**. Önümüzdeki adım: {next_step}

`system_event_logs` event_type: `silence_checkin_posted`
- entity_type: `guest_application` / `student`
- meta: `{stage, cadence_days, days_silent}`

**Mail göndermez** — `guest:inactivity-reminder` zaten o tarafı dövüyor.

## Adımlar

- [ ] **1. DB migration** — 4 tablo:
  - `guest_applications`: `silence_checkin_days_override` (smallint null), `silence_checkin_paused_at` (datetime null), `last_silence_checkin_at` (datetime null)
  - `users`: aynı 3 kolon (sadece role='student' kullanılacak)
  - `companies`: `silence_checkin_overrides` (json null)
- [ ] **2. Config default** — `config/brand.php` içinde `silence_checkin_days` map
- [ ] **3. Servis sınıfı** — `app/Services/SilenceCheckinService.php`:
  - `resolveGuestStage(GuestApplication $g): ?string`
  - `resolveStudentStage(User $u): ?string`
  - `effectiveCadenceDays($entity, string $stage): int` — override hiyerarşisi
  - `daysSinceLastActivity($entity): int`
  - `shouldPostCheckin($entity, string $stage): bool`
  - `postCheckin($entity, string $stage): void` — notif + log + last_silence_checkin_at update
- [ ] **4. Console command** — `silence:checkin-guests` daily 09:30
- [ ] **5. Console command** — `silence:checkin-students` daily 09:35
- [ ] **6. Scheduler** — `routes/console.php`'ye 2 satır
- [ ] **7. Manager UI** — `/manager/silence-monitor`:
  - Tab: Aday | Öğrenci
  - Kolonlar: Kişi | Stage | Son aktivite | Cadence (effective + kaynak) | Son check-in
  - Aksiyonlar: [Şimdi check-in tetikle] [Cadence override] [Pause/Resume]
- [ ] **8. Global ayarlar** — `/manager/silence-monitor/settings` (company-level override)
- [ ] **9. Manager controller** — `Manager/SilenceMonitorController`:
  - `index` (list)
  - `triggerCheckin($entity)` — şimdi gönder
  - `setOverride($entity, days?)` — kişi bazında
  - `pause/resume($entity)`
  - `updateGlobalOverrides()` — company JSON
- [ ] **10. Routes + nav link**
- [ ] **11. Smoke test** — tinker'da bir aday seç, last_senior_action_at -10 gün, command --dry-run

## Bilinçli kapsam dışı (sonraki sprint)
- Stage başına touchpoint metni özelleştirme (UI ile düzenle)
- Aday/öğrenci paneline manager-tarafında "manuel check-in yap" mini buton (bir guest detayında)
- Senior'a "X kişide hareket yok — gerçek not ekleyin" haftalık özet
