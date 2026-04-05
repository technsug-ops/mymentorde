# MVP Deferred Notes (3/1)

Bu dosya, ilerlemeyi yavaslatmamak icin su an ertelenen iyilestirmeleri tutar.
Oncelik puani: 5 = olmazsa olmaz, 1 = sonra yapilir.

## 5 - MVP sonrasi ilk is (kilit) — TAMAMLANDI ✅

- ✅ Marketing External Integrations: Meta Ads + GA4 + Google Ads gercek API baglantisi + TikTok Ads + LinkedIn Ads + Instagram Insights eklendi. OAuth/token yenileme, scheduler sync (marketing:sync-external-metrics), retry/log, multi-company izolasyonu aktif. [ExternalMetricsSyncService::fetchMeta/fetchGA4/fetchGoogleAds/fetchTikTok/fetchLinkedIn/fetchInstagram + refreshTokenIfNeeded + MarketingIntegrationConnection OAuth callback]
- ✅ 3. parti entegrasyon API aktivasyonu: 14 concrete adapter gercek HTTP impl ile aktif. [MailchimpAdapter (GET /3.0/reports + POST campaigns), SendGridAdapter (POST singlesends + schedule), ZohoAdapter (GET campaign/stats), GoogleCalendarAdapter (POST events + freeBusy), CalComAdapter (GET event-types scheduling link), ZoomAdapter (POST /v2/users/me/meetings + cancel + getJoinUrl), GoogleMeetAdapter (conferenceData createRequest + joinUrl), TeamsAdapter (POST graph.microsoft.com/v1.0/me/onlineMeetings), PandaDocAdapter (POST documents + send + session), DocuSignAdapter (POST envelopes + views/recipient), HelloSignAdapter (POST signature_request/send), ClickUpAdapter (POST /v2/list/{listId}/task + updateStatus + assign), MondayAdapter (GraphQL create_item + change_column_value), NotionAdapter (POST /v1/pages + PATCH status)]
- ✅ To-Do Modulu Faz-2: tekrarlayan gorevler (daily/weekly/monthly recurrence), otomatik olusturma, deadline yaklasma bildirimleri (task_due_reminder queue), overdue escalation queue, event tabanli otomasyon (form submit/ticket/sozlesme olaylari). [TaskAutomationService + tasks:process-automation + routes/console.php scheduler]
- ✅ To-Do Modulu Rol Genisleme: senior + manager + admin panellerinde /tasks ortak board, Kanban gorunumu (4 kolon: todo/in_progress/blocked/done), drag-drop siralama (column_order), role/permission bazli gorunurluk, multi-company izolasyonu korunmus. [TaskController::kanban() + kanbanUpdate() + public/js/task-kanban.js + migration add_kanban_fields_to_marketing_tasks]

## Son Tamamlananlar (2026-02-17)
- To-Do rol genisleme temel surumu tamamlandi: `/tasks` ortak board (manager/senior/admin rolleri), dashboard baglantilari, role kontrollu CRUD.
- To-Do otomasyon temel surumu tamamlandi: `tasks:process-automation` (tekrarlayan task uretimi + overdue escalation queue), scheduler'e baglandi.
- External integration portal temel surumu tamamlandi: `/mktg-admin/integrations` (Meta/GA4/Google Ads + Calendly/Mailchimp/ClickUp ayar ve test).
- Multi-company external sync genisletmesi tamamlandi: `marketing:sync-external-metrics` console calismasinda tum aktif company'lerde provider bazli doner.
- To-Do otomasyon guclendirildi: recurrence pattern (`daily/weekly/monthly`) otomasyonda aktif, due-date yaklasan tasklar icin `task_due_reminder` queue eklendi.
- OAuth cekirdegi baglandi: Google Ads + Meta + LinkedIn icin authorize/callback/token exchange akisi panelden calisir hale getirildi.
- Integrations paneli genisletildi: TikTok Ads + LinkedIn Ads + Instagram Insights ayar/test/health satirlari eklendi.
- GA4 OAuth da baglandi: access/refresh token alanlari, callback token exchange ve sync servisinde OAuth token fallback akisi aktif.
- Event tabanli To-Do otomasyon eklendi: Guest form submit, ticket acma, sozlesme talebi ve imzali sozlesme yukleme olaylarinda otomatik task olusumu.

## 3 - Fayda var (MVP sonrasi ilk tur)
- ✅ Student Ownership: chip UX standardi. [config-panel.js + _analytics.blade.php]
- ✅ Snapshot ekrani: alici chip secimi. [manager/dashboard + manager-dashboard.js]
- ✅ Marketing Admin: hedef kitle icin kategori bazli akilli suggestion. [DashboardController::audienceSuggestions + campaigns/index panel]
- ✅ Public Apply: populer siralama. [buildApplySuggestions() popularDealers/recentDealers merge]
- ✅ Config: tum status mesajlarinda standart kodlu hata formati. [app/Support/ApiResponse.php — ERR_DEALER_404, ERR_GUEST_409, ERR_GUEST_423_LOCKED vs.]
- ✅ Marketing Team: ekipten cikarma senaryosunda "pasif/arsiv" secimli akis. [TeamController::remove() action=deactivate|remove + team/index.blade.php]
- ✅ Dealer Relations: dealer bazli broadcast/material dagitimi icin gercek alici kanali. [dealers.email/phone/whatsapp migration + broadcastOne/broadcast recipient routing]
- ✅ Social Module: Meta/Google/YouTube/TikTok API ile otomatik metrik cekimi. [SocialMetricsSyncCommand + social:sync-metrics scheduler 07:00]
- ✅ To-Do UX Faz-2: Kanban gorunumu. [tasks/index kanban board + task-kanban.js drag-drop + GET/PUT /tasks/kanban]

## 2 - Kucuk ama degerli
- ✅ "Virgullu alan" componentini ortak JS helper dosyasina tasima. [public/js/csv-field.js — csvSplit/csvAppend/csvRemove/csvChipRender; marketing-email-segments.js + manager-dashboard.js guncellendi]
- ✅ Suggestion endpoint cache (kisa sureli) ile daha hizli dropdown dolumu. [SuggestionController + GuestApplicationController, Cache::remember 120s/180s]
- ✅ Manager dashboard form satirlarinda mobilde daha sikisik yerlesim. [flex:1;min-width eklendi, btn-primary alias portal-unified'a eklendi]
- ✅ CMS Media: URL tabanli kayit yerine dogrudan storage upload + thumbnail üretim pipeline. [Storage::disk('public') ile gercek dosya yukleme tamamlandi]

## 1 - Sonra yapilir (kozmetik)
- ✅ Buton metinlerinin tamamen tek dil standardina alinmasi: config partials (_analytics, _content, _documents, _portal-users, _company-users, _processes-integrations) tum EN buton/placeholder TR'ye cevrildi. [37+ degisiklik, 6 partial]
- ✅ Kartlar arasi bosluk/typography: "ara..." → "Ara..." capitalize standardizasyonu. [senior/vault.blade.php + senior/process-tracking.blade.php]
- ✅ Datalist/select placeholder metinlerinde dil birligi: "Branch"→"Sube", "Student ID"→"Ogrenci ID", "Senior ID"→"Danisман ID", "Package Total"→"Paket Toplami", "Risk level"→"Risk seviyesi", "Payment status"→"Odeme durumu" vs. [config partials genelinde]

## Son Tamamlananlar (2026-03-04)
- ✅ E2E Playwright multi-rol: 6 dosya (student/guest/dealer/senior/manager/marketing-smoke.spec.js), E2EUserSeeder 7 rol, npm e2e:all script, docs/STUDENT_E2E_PLAYWRIGHT.md tam
- ✅ Auth: ForgotPasswordController + ResetPasswordController + blade view'lar (auth/forgot-password + reset-password)
- ✅ Guvenlik: SecurityHeaders global middleware, EnsureGuestOwnsDocument + EnsureGuestOwnsTicket IDOR middleware
- ✅ GDPR: GdprController (export+erasure student+guest), ConsentRecord+DataRetentionPolicy model+migration, AnonymizationService, PersonalDataExportService
- ✅ Student yeni sayfalar: notifications, payments, vault (controller metod + blade view)
- ✅ Manager detail sayfalar: dealer-detail, guest-detail, senior-detail, student-detail blade view'lar
- ✅ Test coverage: GdprFlowTest (8) + PasswordResetFlowTest (6) + SecurityMiddlewareTest (6) = 74/74 toplam
- ✅ DevUserSeeder + ApplyFormSettingController + LanguageSkills migration + performance indexes
- ✅ Deployment: HOSTING_DEPLOY.bat, scripts/hostinger/, nginx-mentorde.conf, supervisor-mentorde.conf

## Son Tamamlananlar (2026-03-09) — Task Board v3.0
- ✅ Task Board v2.1: state machine (ALLOWED_TRANSITIONS), 7 statü (todo/in_progress/in_review/on_hold/blocked/done/cancelled), buildStatusPayload() merkezi helper, 6 aksiyon route (request-review/approve/request-revision/hold/resume/cancel), self-approve engeli, hold_reason alanı
- ✅ v3 Checklist: task_checklists tablosu, TaskChecklist model, denormalize sayaçlar (checklist_total/done), toggle/add/delete/reorder endpointleri, progress bar UI, CSRF-safe JS
- ✅ v3 Task Templates: task_templates + task_template_items tabloları, TaskTemplate/TaskTemplateItem modeller, TaskTemplateService (applyTemplate + zincirleme bağımlılık + interpolate + resolveAssignee), TaskTemplateController (CRUD + item CRUD + apply), API route'ları (v1/config/task-templates), task board'da şablondan oluştur paneli + önizleme
- ✅ v3 Watchers: task_watchers tablosu, TaskWatcher model, watch/unwatch/watchersList controller metodları + route'lar, task kartında "👁 Takip Et" toggle butonu, eager-load entegrasyonu

## Task Board v3.11 — Ertelenen Özellikler

| # | Özellik | Açıklama |
|---|---------|----------|
| 1 | Saved Views | Filtre kombinasyonlarını kaydet/yükle (task_saved_views tablosu) |
| 2 | Time Tracking | task_time_logs, başlat/durdur timer, departman saati raporu |
| 3 | Custom Fields | task_field_definitions + task_field_values, dinamik alan tipleri |
| 4 | File Versioning | task_attachments versiyonlama, arşiv geçmişi paneli |

## Not
- 5 seviye kritik bir issue cikarsa bu dosyadaki maddeler bekletilir.
