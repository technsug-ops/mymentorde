# TODO — Aday öğrenci → süreç takibi köprüsü + görevli alanı

**Karar (26 Haziran, Filiz Özkan drozkanf@gmail.com ile başla):**
1. Aday öğrenci "evrak bekliyor" (docs_pending) → otomatik **StudentAssignment** (takip kaydı) oluş → Süreç Takibi + Student Pipeline kanban "başvuru hazırlık" (application_prep).
2. GuestApplication'a **görevli (sales staff)** alanı ekle (assigned_staff_email); takipçi=senior (assigned_senior_email) ayrı.

## Kök neden (Filiz'in aday öğrencileri görünmüyor)
`assignedStudentIds()` SADECE StudentAssignment'tan çekiyor (converted). Aday öğrenci GuestApplication.assigned_senior_email'de → süreç takibine düşmüyor. İki paralel dünya, köprü yok.

## Faz A — Görevli (sales staff) alanı [GÜVENLİ]
- [ ] migration: guest_applications.assigned_staff_email + assigned_staff_at + assigned_staff_by
- [ ] GuestApplication: fillable + assignedStaff() relation
- [ ] guestAssignStaff() endpoint (guestAssignSenior deseni) + route
- [ ] Lead pipeline / guest detay UI: görevli atama (senior ataması yanında)

## DURUM: Faz B çekirdeği TAMAM (commit bekliyor)
- [x] StudentBridgeService (idempotent, converted_to_student=false, kickoff task)
- [x] convert() reuse (mükerrer öğrenci önleme) — lokalde doğrulandı
- [x] guestPipelineMove docs_pending hook
- [x] /system/bridge-docs-pending backfill endpoint
- [x] Lokalde test: köprü+task+idempotent+süreç takibinde görünme ✓
- [ ] KALAN: Faz A görevli alanı; sales/manager pipeline move hook; student pipeline application_prep görsel doğrulama (prod)

## Faz B — docs_pending → application_prep köprüsü [KRİTİK]
- [ ] StudentBridgeService::bridgeFromGuest(guest): StudentAssignment oluştur (idempotent),
      guest.converted_student_id set et, converted_to_student=FALSE bırak, senior=assigned_senior_email.
      student_id üretimi convert() ile aynı (generateStudentIdentity — servise çıkar/paylaş).
- [ ] convert() REUSE: converted_student_id doluysa + StudentAssignment varsa yeni oluşturma,
      sadece finalize et (converted_to_student=true, lead_status, user rolü). MÜKERRER ÖĞRENCİ ÖNLE.
- [ ] Hook: lead_status → docs_pending olan yerlerde köprüyü çağır (senior guestPipelineMove +
      sales/manager pipeline move). assigned_senior_email yoksa köprü kurma (senior gerek).
- [ ] Backfill: mevcut docs_pending + assigned_senior_email guest'ler (Filiz'inkiler) → köprü.
      /system/bridge-docs-pending web endpoint (KAS SSH yok).
- [ ] student pipeline / process-tracking: bridged öğrenci ProcessOutcome'suz → application_prep'te görünür (doğrula).

## Riskler
- convert() + ContractWorkflowController conversion path'leri köprüyü yeniden kullanmalı (mükerrer önle).
- StudentAssignment::create yerleri: GuestApplicationAdminController:106 (ana), StudentAssignmentController upsert.
- addon-independence: köprü try/catch, fail olursa pipeline move bozulmasın.

## Notlar
- docs_pending = lead_status 'docs_pending' (Evrak Bekliyor)
- application_prep = PIPELINE_STEPS ilk kolon (Başvuru Hazırlık)
- Senior guest pipeline move: SeniorPipelineController::guestPipelineMove
