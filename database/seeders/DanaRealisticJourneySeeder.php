<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingTask;
use App\Models\MarketingTrackingClick;
use App\Models\MarketingTrackingLink;
use App\Models\Marketing\SocialMediaAccount;
use App\Models\Marketing\SocialMediaPost;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dana Yıldız — GERÇEKÇİ SERÜVEN (Aksaklıklarla)
 *
 * MentorDE ekibinin gerçekçi isimleri ve görev unvanlarıyla,
 * ara sıra aksaklıklar yaşanan, task'larla takip edilen tam süreç.
 *
 * Çalıştır: php artisan db:seed --class=DanaRealisticJourneySeeder
 */
class DanaRealisticJourneySeeder extends Seeder
{
    // ── MentorDE Ekibi ────────────────────────────────────────────────────
    private Company $company;

    /** Müzeyyen Aksoy — Genel Müdür */
    private User $muzeyyen;

    /** Cem Arslan — Operasyon Yöneticisi */
    private User $cem;

    /** Selin Kaya — Süreç Koordinatörü */
    private User $selin;

    /** Ömür Demir — Pazarlama & Satış Yöneticisi */
    private User $omur;

    /** Tuğçe Yıldırım — Dijital Pazarlama Uzmanı */
    private User $tugce;

    /** Burak Şahin — Kıdemli Yurt Dışı Danışmanı */
    private User $burak;

    /** Naz Çelik — Finans Koordinatörü */
    private User $naz;

    // Gün 1 = 155 gün önce (kampanya başlangıcı)
    private function d(int $day): Carbon
    {
        return Carbon::now()->subDays(155 - $day);
    }

    public function run(): void
    {
        $this->company  = Company::query()->where('is_active', true)->orderBy('id')->first()
            ?? Company::query()->create(['name' => 'MentorDE', 'is_active' => true]);

        $this->muzeyyen = $this->user('muzeyyen.aksoy@mentorde.com',   'Müzeyyen Aksoy',   'manager');
        $this->cem      = $this->user('cem.arslan@mentorde.com',       'Cem Arslan',       'operations_admin');
        $this->selin    = $this->user('selin.kaya@mentorde.com',       'Selin Kaya',       'operations_staff');
        $this->omur     = $this->user('omur.demir@mentorde.com',       'Ömür Demir',       'marketing_admin');
        $this->tugce    = $this->user('tugce.yildirim@mentorde.com',   'Tuğçe Yıldırım',  'marketing_staff');
        $this->burak    = $this->user('burak.sahin@mentorde.com',      'Burak Şahin',      'senior');
        $this->naz      = $this->user('naz.celik@mentorde.com',        'Naz Çelik',        'finance_admin');

        $this->info('');
        $this->info('┌──────────────────────────────────────────────────────────────────┐');
        $this->info('│  DANA YILDIZ — GERÇEKÇİ SERÜVEN (Aksaklıklarla)                │');
        $this->info('│  MentorDE Ekibi: Müzeyyen, Cem, Selin, Ömür, Tuğçe, Burak, Naz │');
        $this->info('└──────────────────────────────────────────────────────────────────┘');

        [$campaign, $link] = $this->faz0_kampanya();
        $guest             = $this->faz0b_lead($campaign, $link);
        $this->faz1_kabul($guest);
        [$guest, $sid]     = $this->donusum($guest);
        $this->faz2_evrak($sid);
        $this->faz3_uniassist($sid);
        $this->faz4_vize($sid, $guest);
        $this->faz5_ikamet($sid);

        $this->info('');
        $this->info('✅ Öğrenci ID: ' . $sid . ' | Süre: 155 gün');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 0 — KAMPANYA (Gün 1–28)  →  TUĞÇE + ÖMÜR
    // ══════════════════════════════════════════════════════════════════════════
    private function faz0_kampanya(): array
    {
        $this->info('');
        $this->info('📣 FAZ 0 — KAMPANYA (Gün 1–28)  |  Ömür Demir + Tuğçe Yıldırım');

        $campaign = MarketingCampaign::create([
            'company_id'      => $this->company->id,
            'name'            => 'WS 2026/27 Bachelor Kazanım Kampanyası',
            'description'     => 'Instagram + Email üzerinden WS 2026/27 bachelor öğrenci adayı kazanımı.',
            'channel'         => 'social_media',
            'channels'        => ['instagram', 'email'],
            'budget'          => 15000,
            'spent_amount'    => 13250,
            'currency'        => 'TRY',
            'start_date'      => $this->d(1)->toDateString(),
            'end_date'        => $this->d(45)->toDateString(),
            'target_audience' => 'TR, 18-24 yaş, yurt dışı eğitim ilgili',
            'target_country'  => 'TR',
            'status'          => 'completed',
            'utm_params'      => ['utm_source' => 'instagram', 'utm_medium' => 'social', 'utm_campaign' => 'ws2026-bachelor'],
            'metrics'         => ['impressions' => 91400, 'clicks' => 2103, 'leads' => 71, 'conversions' => 14],
            'created_by'      => $this->omur->id,
        ]);

        $igAccount = SocialMediaAccount::firstOrCreate(
            ['account_name' => 'mentorde_official'],
            ['platform' => 'instagram', 'account_url' => 'https://www.instagram.com/mentorde_official', 'followers' => 12400, 'total_posts' => 187, 'api_connected' => false, 'is_active' => true]
        );

        SocialMediaPost::create([
            'account_id'             => $igAccount->id,
            'platform'               => 'instagram',
            'caption'                => '🇩🇪 Almanya\'da üniversite hayalinizi gerçeğe dönüştürün! #MentorDE #Bachelor #AlmanyaEğitim',
            'post_type'              => 'story',
            'status'                 => 'published',
            'scheduled_at'           => $this->d(15),
            'published_at'           => $this->d(15),
            'metric_views'           => 10240,
            'metric_likes'           => 334,
            'metric_click_through'   => 4.1,
            'metric_guest_registrations' => 9,
            'linked_campaign_id'     => $campaign->id,
            'created_by'             => $this->tugce->id,
        ]);

        $link = MarketingTrackingLink::create([
            'company_id'      => $this->company->id,
            'title'           => 'Instagram Story — WS2026 Bachelor',
            'code'            => 'IG-BACH-WS26-' . strtoupper(Str::random(5)),
            'category_code'   => 'social',
            'platform_code'   => 'instagram',
            'placement_code'  => 'story',
            'destination_path'=> '/apply',
            'campaign_id'     => $campaign->id,
            'utm_source'      => 'instagram',
            'utm_medium'      => 'social',
            'utm_campaign'    => 'ws2026-bachelor',
            'status'          => 'active',
            'click_count'     => 389,
            'last_clicked_at' => $this->d(29),
        ]);

        // Normal akış taskları
        $this->task('mktg_strategy',   (string) $campaign->id, 'Tuğçe Yıldırım',
            'WS2026 kampanya stratejisi ve içerik takvimi oluştur',
            'Hedef kitle: 18-24 yaş TR. Kanal: Instagram + Email. Bütçe: 15.000 TL. A/B/C story varyasyonu planlandı.',
            'marketing', 'normal', 'guest_intake', 'intake_received',
            $this->tugce, $this->d(2), $this->d(3));

        $this->task('mktg_content',    (string) $campaign->id, 'Tuğçe Yıldırım',
            'Instagram story görselleri hazırla (3 varyasyon)',
            'Canva Pro ile 3 story varyasyonu tamamlandı. Copywriting: 5 farklı CTA metni. Ömür onayladı.',
            'marketing', 'normal', 'guest_intake', 'intake_received',
            $this->tugce, $this->d(10), $this->d(11));

        // ⚠️ AKSAKLIK 1: Görsellerde hata bulundu, revizyon gerekti
        $this->task('mktg_content_fix', (string) $campaign->id, 'Tuğçe Yıldırım',
            '⚠️ REVIZYON — Story görsellerinde logo hatası düzelt (Ömür geri çevirdi)',
            'Ömür incelemesinde: logo eski versiyon kullanılmış + telefon numarası yanlış. 3 varyasyon yeniden hazırlandı.',
            'marketing', 'high', 'guest_intake', 'intake_received',
            $this->tugce, $this->d(12), $this->d(13));

        $this->task('mktg_utm_setup',  (string) $link->id, 'Tuğçe Yıldırım',
            'UTM takip linkleri oluştur, QA test et',
            '3 varyasyon için UTM parametreli linkler. Google Analytics + MentorDE CRM entegrasyon testi: ✅',
            'marketing', 'normal', 'guest_intake', 'intake_received',
            $this->tugce, $this->d(13), $this->d(14));

        $this->task('mktg_publish',    (string) $campaign->id, 'Tuğçe Yıldırım',
            'Instagram story ve email kampanyasını yayınla',
            'Story: 09:00/12:00/20:00 varyasyonlar. Email: 4.240 kişi — açılma %34.2, tıklama %8.7.',
            'marketing', 'high', 'guest_intake', 'intake_received',
            $this->tugce, $this->d(15), $this->d(15));

        $this->task('mktg_report',     (string) $campaign->id, 'Ömür Demir',
            'İlk 2 hafta performans raporu — Müzeyyen\'e sun',
            'C varyasyonu en yüksek CTR (%4.9). 38 lead geldi. Müzeyyen onayladı: C varyasyonu ana kanal.',
            'marketing', 'normal', 'guest_intake', 'intake_received',
            $this->omur, $this->d(22), $this->d(23));

        $this->task('mktg_qualify',    (string) $campaign->id, 'Ömür Demir',
            '38 lead\'i qualify et ve CRM\'e gir, sıcakları Selin\'e aktar',
            '12 sıcak lead belirlendi. Dana Yıldız: skor 92/100 — en yüksek. Selin\'e acil bildirim gönderildi.',
            'marketing', 'high', 'guest_intake', 'needs_assessment',
            $this->omur, $this->d(28), $this->d(28));

        $this->info('  → Kampanya + 7 task (1 revizyon dahil) [done]');
        return [$campaign, $link];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 0.5 — LEAD YAKALAMA (Gün 29)
    // ══════════════════════════════════════════════════════════════════════════
    private function faz0b_lead(MarketingCampaign $campaign, MarketingTrackingLink $link): GuestApplication
    {
        $this->info('');
        $this->info('🎯 FAZ 0.5 — LEAD (Gün 29)  |  Dana tıkladı → Selin Kaya alert');

        MarketingTrackingClick::create([
            'company_id'       => $this->company->id,
            'tracking_link_id' => $link->id,
            'tracking_code'    => $link->code,
            'ip_address'       => '88.241.xxx.xxx',
            'user_agent'       => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3) Instagram/318.0',
            'referrer_url'     => 'https://www.instagram.com/stories/mentorde_official/',
            'landing_url'      => 'https://mentorde.com/apply?utm_source=instagram&utm_medium=social&utm_campaign=ws2026-bachelor',
            'query_params'     => ['utm_source' => 'instagram', 'utm_medium' => 'social', 'utm_campaign' => 'ws2026-bachelor'],
            'created_at'       => $this->d(29),
            'updated_at'       => $this->d(29),
        ]);

        $guest = GuestApplication::create([
            'company_id'             => $this->company->id,
            'tracking_token'         => Str::uuid(),
            'first_name'             => 'Dana',
            'last_name'              => 'Yıldız',
            'email'                  => 'dana.yildiz2@example.com',
            'phone'                  => '+90 533 112 7788',
            'gender'                 => 'female',
            'application_country'    => 'TR',
            'communication_language' => 'tr',
            'application_type'       => 'bachelor',
            'language_level'         => 'b2',
            'lead_source'            => 'instagram',
            'utm_source'             => 'instagram',
            'utm_medium'             => 'social',
            'utm_campaign'           => 'ws2026-bachelor',
            'tracking_link_code'     => $link->code,
            'landing_url'            => 'https://mentorde.com/apply',
            'target_term'            => 'WS 2026/27',
            'target_city'            => 'Berlin',
            'lead_status'            => 'new',
            'priority'               => 'high',
            'lead_score'             => 92,
            'lead_score_tier'        => 'hot',
            'kvkk_consent'           => true,
            'notes'                  => 'Formda: "Goethe B2 var, lise ortalaması 1.8, TU Berlin Bilg. Müh. hedefliyorum." Instagram story\'den geldi.',
            'registration_form_submitted_at' => $this->d(29),
            'created_at'             => $this->d(29),
            'updated_at'             => $this->d(29),
        ]);

        LeadSourceDatum::create([
            'company_id'              => $this->company->id,
            'guest_id'                => $guest->id,
            'initial_source'          => 'instagram',
            'initial_source_detail'   => 'story-ws2026-cta',
            'initial_source_platform' => 'instagram',
            'verified_source'         => 'instagram',
            'source_match'            => true,
            'campaign_id'             => $campaign->id,
            'utm_source'              => 'instagram',
            'utm_medium'              => 'social',
            'utm_campaign'            => 'ws2026-bachelor',
            'utm_params'              => ['utm_source' => 'instagram', 'utm_medium' => 'social'],
            'created_at'              => $this->d(29),
            'updated_at'              => $this->d(29),
        ]);

        $this->task('lead_hot_alert', (string) $guest->id, 'Selin Kaya',
            '🔴 HOT LEAD — Dana Yıldız (Skor 92) — 2 saat içinde ara!',
            'Ömür\'den aktarıldı. En yüksek skor bu hafta. B2 Goethe + 1.8 not + TU Berlin hedefi + aile finansmanı.',
            'operations', 'urgent', 'guest_intake', 'intake_received',
            $this->selin, $this->d(29), $this->d(29));

        $this->info('  → Tıklama + GuestApp + LeadSource + 1 urgent task [done]');
        return $guest;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 1 — DANIŞAN KABUL & SÖZLEŞME (Gün 30–42)  →  SELİN + ÖMÜR + NAZ
    // ══════════════════════════════════════════════════════════════════════════
    private function faz1_kabul(GuestApplication $guest): void
    {
        $this->info('');
        $this->info('🤝 FAZ 1 — KABUL & SÖZLEŞME (Gün 30–42)  |  Selin, Ömür, Naz');

        // İlk iletişim denemesi
        $this->task('guest_first_contact', (string) $guest->id, 'Selin Kaya',
            'Dana Yıldız — İlk iletişim: telefon + WhatsApp',
            'Saat 11:30\'da arandı — açmadı. WhatsApp bırakıldı. Email atıldı.',
            'operations', 'high', 'guest_intake', 'intake_received',
            $this->selin, $this->d(30), $this->d(30));

        // ⚠️ AKSAKLIK 2: Dana 3 gün cevap vermedi
        $this->task('guest_followup_2', (string) $guest->id, 'Selin Kaya',
            '⚠️ TAKIP — Dana 3 gündür cevap vermiyor, 2. deneme (WhatsApp + email)',
            'Gün 33: 2. WhatsApp mesajı gönderildi + farklı saatte arandı. Ömür\'e bildirildi: "lead soğuyabilir".',
            'operations', 'high', 'guest_intake', 'intake_received',
            $this->selin, $this->d(33), $this->d(33));

        $this->task('guest_followup_3', (string) $guest->id, 'Ömür Demir',
            '⚠️ TAKIP — Ömür Demir bizzat aradı (son deneme)',
            'Ömür saat 19:00\'da aradı. Dana açtı: "yoğundum, özür dilerim." Zoom toplantı tarihi belirlendi: Gün 35.',
            'marketing', 'urgent', 'guest_intake', 'intake_received',
            $this->omur, $this->d(34), $this->d(34));

        $guest->update(['lead_status' => 'contacted', 'assigned_senior_email' => $this->burak->email,
            'assigned_at' => $this->d(34), 'assigned_by' => $this->muzeyyen->id]);

        $this->task('sales_meeting', (string) $guest->id, 'Ömür Demir',
            'Dana Yıldız — Zoom tanışma görüşmesi (45 dk)',
            'TU Berlin Informatik B.Sc. stratejisi. BACHELOR_FULL paket 4.500 EUR sunuldu. Dana kabul etti. Ödeme planı: 3 taksit.',
            'marketing', 'high', 'guest_intake', 'needs_assessment',
            $this->omur, $this->d(35), $this->d(35));

        $this->task('contract_prep', (string) $guest->id, 'Selin Kaya',
            'Dana Yıldız — Sözleşme taslağını hazırla ve portale yükle',
            'BACHELOR_FULL şablonundan oluşturuldu. Fiyat: 4.500 EUR / 3 taksit. Portale yüklendi, Dana\'ya email bildirim gitti.',
            'operations', 'high', 'guest_intake', 'contract_prep',
            $this->selin, $this->d(37), $this->d(37));

        // ⚠️ AKSAKLIK 3: Sözleşmede tutar hatası
        $this->task('contract_fix', (string) $guest->id, 'Selin Kaya',
            '⚠️ HATA — Sözleşmede 2. taksit tutarı yanlış (1.600 yazılmış, 1.500 olmalı) — düzelt',
            'Dana portal\'da fark etti ve bildirdi. Selin hemen düzeltti, yeni versiyon yüklendi, Dana\'dan özür dilendi.',
            'operations', 'urgent', 'guest_intake', 'contract_prep',
            $this->selin, $this->d(38), $this->d(38));

        $this->task('contract_approve', (string) $guest->id, 'Cem Arslan',
            'Dana Yıldız — İmzalı sözleşmeyi incele ve onayla',
            'Dana imzalı sözleşmeyi portal\'a yükledi. Cem inceledi: imzalar tamam, KVKK onayı var. Onaylandı. Öğrenciye dönüştürme başlatıldı.',
            'operations', 'urgent', 'guest_intake', 'onboarding',
            $this->cem, $this->d(41), $this->d(42));

        $this->task('payment_1', (string) $guest->id, 'Naz Çelik',
            'Dana Yıldız — 1. taksit tahsilat takibi (1.500 EUR)',
            'Havale alındı. Gönderen adı kontrol edildi (Anne: Serap Yıldız — tam yetkili). Muhasebe kaydı oluşturuldu. Makbuz gönderildi.',
            'finance', 'high', 'guest_intake', 'contract_prep',
            $this->naz, $this->d(41), $this->d(42));

        $guest->update(['lead_status' => 'converted', 'contract_status' => 'approved',
            'contract_requested_at' => $this->d(36), 'contract_signed_at' => $this->d(40),
            'contract_approved_at' => $this->d(42),
            'selected_package_code' => 'BACHELOR_FULL', 'selected_package_title' => 'Bachelor Tam Danışmanlık Paketi',
            'selected_package_price' => 4500, 'package_selected_at' => $this->d(35)]);

        $this->info('  → 8 task (3 aksaklık/takip dahil) [done]');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DÖNÜŞÜM
    // ══════════════════════════════════════════════════════════════════════════
    private function donusum(GuestApplication $guest): array
    {
        $sid = 'STU-DANA-R-' . str_pad((string) $guest->id, 4, '0', STR_PAD_LEFT);
        $guest->update(['converted_student_id' => $sid, 'converted_to_student' => true, 'converted_at' => $this->d(42)]);
        StudentAssignment::create(['company_id' => $this->company->id, 'student_id' => $sid, 'senior_email' => $this->burak->email]);
        return [$guest->fresh(), $sid];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 2 — EVRAK YÖNETİMİ (Gün 44–65)  →  BURAK + SELİN + CEM
    // ══════════════════════════════════════════════════════════════════════════
    private function faz2_evrak(string $sid): void
    {
        $this->info('');
        $this->info('📄 FAZ 2 — EVRAK (Gün 44–65)  |  Burak Şahin, Selin Kaya, Cem Arslan');

        $this->task('onboarding_kickoff', $sid, 'Burak Şahin',
            'Dana Yıldız — Onboarding açılış görüşmesi (60 dk Zoom)',
            'Burak: süreç takvimi, evrak listesi, TU Berlin stratejisi. Dana\'ya detaylı kontrol listesi PDF gönderildi.',
            'advisory', 'high', 'guest_intake', 'onboarding',
            $this->burak, $this->d(44), $this->d(44), $sid);

        $this->task('doc_diploma', $sid, 'Selin Kaya',
            'Dana Yıldız — Diploma, noter onayı ve apostil takibi',
            'Fotokopi + noter onayı aldı. Apostil için MEB İl Müdürlüğü\'ne yönlendirildi. Tahmini: 5-7 iş günü.',
            'operations', 'normal', 'document_management', 'doc_collection',
            $this->selin, $this->d(46), $this->d(48), $sid);

        // ⚠️ AKSAKLIK 4: MEB apostilde isim hatası
        $this->task('doc_diploma_meb_error', $sid, 'Selin Kaya',
            '⚠️ ACİL — MEB apostilinde isim yanlış: "Dana" yerine "Dina" yazılmış',
            'Dana MEB\'den gelen apostili kontrol etti, isim yanlış. Selin MEB\'i aradı. Dilekçe + yeni başvuru gerekiyor. Ek süre: 5 iş günü.',
            'operations', 'urgent', 'document_management', 'doc_correction',
            $this->selin, $this->d(50), $this->d(51), $sid);

        $this->task('doc_diploma_meb_reapply', $sid, 'Cem Arslan',
            '⚠️ MEB düzeltme dilekçesi hazırla, Cem takip et',
            'Cem bizzat MEB İl Müdürlüğü\'nü arayıp takip etti. Düzeltilmiş apostil 5 iş gününde alındı.',
            'operations', 'high', 'document_management', 'doc_correction',
            $this->cem, $this->d(51), $this->d(56), $sid);

        $this->task('doc_transcript', $sid, 'Selin Kaya',
            'Dana Yıldız — Transkript kontrolü ve TR→DE not dönüşümü',
            'Onaylı transkript geldi. Dönüşüm: 100\'lük TR → Almanya 6\'lık skalası. Abitur karşılığı: 1.8. Uni-Assist için yeterli.',
            'operations', 'normal', 'document_management', 'doc_review',
            $this->selin, $this->d(52), $this->d(53), $sid);

        $this->task('doc_goethe', $sid, 'Selin Kaya',
            'Dana Yıldız — Goethe B2 sertifikası doğrulama',
            'Orijinal görüldü. Sertifika no: GI-2025-B2-881234. Geçerlilik: Mart 2030. Onaylı fotokopi hazırlandı.',
            'operations', 'normal', 'document_management', 'doc_review',
            $this->selin, $this->d(55), $this->d(55), $sid);

        $this->task('doc_passport', $sid, 'Selin Kaya',
            'Dana Yıldız — Pasaport geçerlilik + biyometrik fotoğraf',
            'Pasaport geçerli (Mart 2030). Biyometrik fotoğraf (4 adet) teslim edildi. Fotokopi hazırlandı.',
            'operations', 'normal', 'document_management', 'doc_review',
            $this->selin, $this->d(57), $this->d(57), $sid);

        $this->task('doc_complete', $sid, 'Burak Şahin',
            'Dana Yıldız — Evrak seti tam kontrol, Burak\'ın onayı',
            '✅ Diploma (apostilli — DÜZELTİLMİŞ) ✅ Transkript ✅ Pasaport ✅ Goethe B2 ✅ Biyometrik foto. Burak onayladı: Uni-Assist\'e geç.',
            'advisory', 'high', 'document_management', 'doc_submission',
            $this->burak, $this->d(63), $this->d(64), $sid);

        ProcessOutcome::create([
            'student_id' => $sid, 'process_step' => 'application_prep',
            'outcome_type' => 'acceptance',
            'details_tr' => 'Tüm evraklar eksiksiz tamamlandı (MEB apostil düzeltmesiyle birlikte). Uni-assist sürecine geçildi.',
            'is_visible_to_student' => true, 'created_at' => $this->d(64),
        ]);

        $this->info('  → 8 task (2 MEB aksaklık/düzeltme dahil) + 1 process outcome [done]');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 3 — UNI-ASSIST (Gün 66–105)  →  SELİN + NAZ + BURAK
    // ══════════════════════════════════════════════════════════════════════════
    private function faz3_uniassist(string $sid): void
    {
        $this->info('');
        $this->info('🏛 FAZ 3 — UNI-ASSIST (Gün 66–105)  |  Selin, Naz, Burak');

        $this->task('ua_package', $sid, 'Selin Kaya',
            'Dana Yıldız — TU Berlin Uni-Assist paketi hazırla',
            'TU Berlin Informatik B.Sc. Paket: diploma, transkript, apostil, Goethe B2, pasaport kopyası, CV. Motivasyon mektubunu Burak yazacak.',
            'operations', 'high', 'uni_assist', 'ua_prep',
            $this->selin, $this->d(66), $this->d(68), $sid);

        $this->task('ua_mot_letter', $sid, 'Burak Şahin',
            'Dana Yıldız — Motivasyon mektubu yaz ve Dana\'ya onayla',
            'Burak Almanca motivasyon mektubu taslağını yazdı. Dana ile Zoom\'da revize edildi. 3. versiyonda onaylandı.',
            'advisory', 'normal', 'uni_assist', 'ua_prep',
            $this->burak, $this->d(67), $this->d(70), $sid);

        $this->task('ua_payment', $sid, 'Naz Çelik',
            'Dana Yıldız — Uni-assist ücreti 75 EUR takibi',
            'Dana IBAN ile ödedi. Referans kodu doğrulandı. Makbuz sisteme yüklendi.',
            'finance', 'normal', 'uni_assist', 'ua_prep',
            $this->naz, $this->d(70), $this->d(71), $sid);

        $this->task('ua_send', $sid, 'Selin Kaya',
            'Dana Yıldız — Uni-Assist paketi DHL Express kargo',
            'DHL Express: Hannover. Takip no: DHL-DE-2026-993344. Tahmini varış: 3 iş günü.',
            'operations', 'high', 'uni_assist', 'ua_submitted',
            $this->selin, $this->d(72), $this->d(72), $sid);

        // ⚠️ AKSAKLIK 5: Paket iade edildi — eksik belge
        $this->task('ua_returned', $sid, 'Selin Kaya',
            '⚠️ KRİTİK — Uni-Assist paketi iade edildi: motivasyon mektubunda ISLAK imza eksik',
            'Uni-Assist iade bildirimi geldi: motivasyon mektubunda öğrenci el imzası yok (baskı imzası kabul edilmiyor). Acil düzeltme gerekiyor!',
            'operations', 'urgent', 'uni_assist', 'ua_prep',
            $this->selin, $this->d(81), $this->d(81), $sid);

        $this->task('ua_fix_signature', $sid, 'Cem Arslan',
            '⚠️ ÇÖZÜM — Islak imzalı motivasyon mektubunu kargo ile al',
            'Dana imzaladı, kargo ile gönderdi (Ankara → İstanbul). Cem teslim aldı. Paket yeniden hazırlandı.',
            'operations', 'urgent', 'uni_assist', 'ua_prep',
            $this->cem, $this->d(82), $this->d(83), $sid);

        $this->task('ua_resend', $sid, 'Selin Kaya',
            'Uni-Assist paketi yeniden gönderildi (ıslak imzalı)',
            'Yeni DHL: DHL-DE-2026-994871. Not: teslim alındı onayı bekleniyor.',
            'operations', 'high', 'uni_assist', 'ua_submitted',
            $this->selin, $this->d(84), $this->d(84), $sid);

        $this->task('ua_status', $sid, 'Burak Şahin',
            'Uni-Assist durum takibi — 3. hafta',
            'Portal: "in Bearbeitung" (incelemede). Tahmini sonuç: 3-4 hafta. Dana bilgilendirildi.',
            'advisory', 'normal', 'uni_assist', 'ua_under_review',
            $this->burak, $this->d(95), $this->d(96), $sid);

        $this->task('ua_vpd', $sid, 'Selin Kaya',
            'Dana Yıldız — VPD geldi, TU Berlin portale yükle',
            'VPD (Vorprüfungsdokumentation) hazırlandı. TU Berlin Bewerbungsportal\'a yüklendi. Son bekleme: üniversite kararı.',
            'operations', 'high', 'uni_assist', 'ua_under_review',
            $this->selin, $this->d(101), $this->d(102), $sid);

        $this->task('ua_result', $sid, 'Burak Şahin',
            '🎉 TU Berlin KABUL! Burak Dana\'yı aradı, vize sürecini başlat',
            'Zulassung WS 2026/27 — Informatik B.Sc. geldi! Burak Dana\'yı aradı. Gözyaşları vardı 😊 Vize sürecine ACİL geçiliyor.',
            'advisory', 'urgent', 'uni_assist', 'ua_result',
            $this->burak, $this->d(105), $this->d(105), $sid);

        ProcessOutcome::create([
            'student_id' => $sid, 'process_step' => 'uni_assist', 'outcome_type' => 'acceptance',
            'university' => 'Technische Universität Berlin', 'program' => 'Informatik B.Sc.',
            'details_tr' => 'TU Berlin Informatik B.Sc. kabulü alındı (WS 2026/27). Not: 1.8 — NC 2.1\'in altında. İmza aksaklığı nedeniyle paket 1 kez iade edildi ancak toplamda süreç başarılı tamamlandı.',
            'deadline' => $this->d(135)->toDateString(), 'is_visible_to_student' => true, 'created_at' => $this->d(105),
        ]);

        $this->info('  → 10 task (2 krit. aksaklık dahil) + 1 kabul process outcome [done]');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 4 — VİZE (Gün 107–133)  →  CEM + NAZ + SELİN
    // ══════════════════════════════════════════════════════════════════════════
    private function faz4_vize(string $sid, GuestApplication $guest): void
    {
        $this->info('');
        $this->info('✈ FAZ 4 — VİZE (Gün 107–133)  |  Cem, Naz, Selin');

        $this->task('visa_appt_attempt1', $sid, 'Cem Arslan',
            'Büyükelçilik vize randevusu al — 1. deneme',
            'Büyükelçilik randevu sistemi (AIS) çökmüş, saat 14:00-18:00 arası erişilemedi. Ertesi gün tekrar denenecek.',
            'operations', 'urgent', 'visa_application', 'visa_appointment',
            $this->cem, $this->d(107), $this->d(107), $sid);

        // ⚠️ AKSAKLIK 6: Randevu sistemi çöküşü
        $this->task('visa_appt_attempt2', $sid, 'Cem Arslan',
            '⚠️ SİSTEM — AIS yeniden açıldı, randevu alındı (2. deneme)',
            'Ertesi gün sabah 08:00\'de sisteme girildi. Randevu alındı: ' . $this->d(116)->format('d.m.Y') . ' saat 11:00. Ankara Büyükelçiliği.',
            'operations', 'urgent', 'visa_application', 'visa_appointment',
            $this->cem, $this->d(108), $this->d(108), $sid);

        $this->task('sperrkonto', $sid, 'Naz Çelik',
            'Fintiba sperrkonto aç + 11.208 EUR yatırma takibi',
            'Fintiba hesabı açıldı. 11.208 EUR (934×12) yatırılması gerekiyor. Aile bankası: Ziraat Bankası.',
            'finance', 'high', 'visa_application', 'visa_docs_prepared',
            $this->naz, $this->d(109), $this->d(110), $sid);

        // ⚠️ AKSAKLIK 7: Banka transferi gecikti
        $this->task('sperrkonto_delay', $sid, 'Naz Çelik',
            '⚠️ GECİKME — Ziraat Bankası döviz transferi 5 gün sürdü (SWIFT gecikme)',
            'Transfer beklenen 2 gün yerine 5 gün sürdü. Naz Ziraat\'ı aradı, SWIFT referans numarasıyla takip etti. Para 5. günde geldi. Vize randevusu 3 gün ertelendi.',
            'finance', 'urgent', 'visa_application', 'visa_docs_prepared',
            $this->naz, $this->d(111), $this->d(116), $sid);

        $this->task('visa_appt_reschedule', $sid, 'Cem Arslan',
            '⚠️ RANDEVU ERTELEME — Sperrkonto gecikmesi nedeniyle randevu kaydırdı',
            'Sperrkonto belgesi gelmeden mülakat yapılamaz. Randevu: ' . $this->d(116)->format('d.m.Y') . ' → ' . $this->d(119)->format('d.m.Y') . '. AIS\'ten ertelendi.',
            'operations', 'urgent', 'visa_application', 'visa_appointment',
            $this->cem, $this->d(116), $this->d(116), $sid);

        $this->task('visa_docs', $sid, 'Selin Kaya',
            'Dana Yıldız — Vize dosyası tam hazırlık (kontrol listesi)',
            '✅ Pasaport ✅ 3 biyometrik fotoğraf ✅ TU Berlin Zulassung ✅ Sperrkonto (Fintiba) ✅ Sigorta ✅ Finansal taahhüt. Tümü tamam.',
            'operations', 'high', 'visa_application', 'visa_docs_prepared',
            $this->selin, $this->d(117), $this->d(117), $sid);

        $this->task('visa_interview', $sid, 'Cem Arslan',
            'Dana Yıldız — Mülakat takibi: Ankara Büyükelçiliği',
            'Mülakat 25 dk. Konsolos: finansal durum, TU Berlin seçim sebebi, dönüş planı. Dana harika yanıtlar verdi. Pasaport bırakıldı.',
            'operations', 'high', 'visa_application', 'visa_submitted',
            $this->cem, $this->d(119), $this->d(119), $sid);

        $this->task('visa_approved', $sid, 'Cem Arslan',
            '✅ VİZE ONAYLANDI — Pasaport teslim alındı, uçuş planla',
            'Ulusal D vizesi (Studienvisum). Geçerlilik: 1 yıl. DHL courier ile teslim edildi. Dana ağladı 😊 Uçuş: TK, ' . $this->d(138)->format('d.m.Y'),
            'operations', 'urgent', 'visa_application', 'visa_approved',
            $this->cem, $this->d(130), $this->d(130), $sid);

        $this->task('predeparture', $sid, 'Burak Şahin',
            'Dana Yıldız — Gidiş öncesi briefing (pre-departure paketi)',
            'Burak ile 45 dk Zoom: Berlin haritası, Anmeldung süreci, TK sigortası, ilk hafta yapılacaklar. PDF rehber gönderildi.',
            'advisory', 'normal', 'visa_application', 'visa_approved',
            $this->burak, $this->d(133), $this->d(134), $sid);

        ProcessOutcome::create([
            'student_id' => $sid, 'process_step' => 'visa_application', 'outcome_type' => 'acceptance',
            'details_tr' => 'Almanya öğrenci vizesi (Ulusal D) onaylandı. Banka gecikmesi nedeniyle randevu 3 gün ertelendi, ancak süreç sorunsuz tamamlandı.',
            'is_visible_to_student' => true, 'created_at' => $this->d(130),
        ]);

        $this->info('  → 9 task (3 aksaklık dahil) + 1 vize process outcome [done]');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FAZ 5 — OTURUM & KAYIT (Gün 138–155)  →  BURAK + NAZ + SELİN
    // ══════════════════════════════════════════════════════════════════════════
    private function faz5_ikamet(string $sid): void
    {
        $this->info('');
        $this->info('🎓 FAZ 5 — İKAMET & KAYIT (Gün 138–155)  |  Burak, Naz, Selin');

        $this->task('arrival', $sid, 'Burak Şahin',
            'Dana Yıldız — Berlin\'e varış, karşılama mesajı',
            'Dana BER\'e indi. WhatsApp: "heyecandan uyuyamadım 😊". Hostel\'e yerleşti. İlk hafta listesi hatırlatıldı.',
            'advisory', 'high', 'residence_permit', 'res_registration',
            $this->burak, $this->d(138), $this->d(138), $sid);

        $this->task('anmeldung', $sid, 'Burak Şahin',
            'Dana Yıldız — Anmeldung takibi (Berlin Mitte Bürgeramt)',
            'Randevu: Gün 141. Tescil belgesi alındı. Adres: Invalidenstraße 123, 10115 Berlin. Burak SMS ile teyit aldı.',
            'advisory', 'high', 'residence_permit', 'res_registration',
            $this->burak, $this->d(140), $this->d(141), $sid);

        $this->task('tk_insurance', $sid, 'Burak Şahin',
            'Dana Yıldız — TK sağlık sigortası üyelik belgesi',
            'TK üyeliği aktive edildi. Bescheinigung alındı (üniversite kaydı için zorunlu). Prim: 119.78 EUR/ay.',
            'advisory', 'high', 'residence_permit', 'res_documents',
            $this->burak, $this->d(142), $this->d(142), $sid);

        $this->task('sperrkonto_activate', $sid, 'Naz Çelik',
            'Dana Yıldız — Fintiba sperrkonto Almanya\'da aktive et',
            'Anmeldung belgesiyle Fintiba aktive edildi. İlk 934 EUR serbest. N26 banka hesabı açma sürecinde.',
            'finance', 'normal', 'residence_permit', 'res_documents',
            $this->naz, $this->d(143), $this->d(143), $sid);

        // ⚠️ AKSAKLIK 8: Ausländerbehörde randevusu iptal
        $this->task('ab_appt_cancelled', $sid, 'Selin Kaya',
            '⚠️ RANDEVU — Ausländerbehörde randevusu sistem arızasıyla iptal edildi',
            'Randevu onayı gelmedi, sistem arızası. Selin Berlin ABH\'yı aradı. Yeni randevu: 3 gün sonraya alındı.',
            'operations', 'urgent', 'residence_permit', 'res_appointment',
            $this->selin, $this->d(146), $this->d(146), $sid);

        $this->task('ab_permit', $sid, 'Selin Kaya',
            'Dana Yıldız — Oturma izni randevusu başarılı',
            'Ausländerbehörde randevusu tamamlandı. Fiktion belgesi verildi (izin işlemdeyken geçerli). Kalıcı oturma izni: 4 hafta.',
            'operations', 'high', 'residence_permit', 'res_appointment',
            $this->selin, $this->d(149), $this->d(149), $sid);

        $this->task('payment_2', $sid, 'Naz Çelik',
            'Dana Yıldız — 2. taksit tahsilat takibi (1.500 EUR)',
            '2. taksit zamanında ödendi. Muhasebe kaydı. Makbuz gönderildi.',
            'finance', 'normal', 'guest_intake', 'contract_prep',
            $this->naz, $this->d(150), $this->d(150), $sid);

        $this->task('immatriculation', $sid, 'Burak Şahin',
            '🎓 Dana Yıldız — TU Berlin immatriküle TAMAMLANDI',
            'Studienbüro\'da: Zulassung + TK belgesi + Anmeldung + pasaport teslim. Öğrenci no: 456789. Matrikelkarte + student email + Semesterticket alındı. İlk ders: 14 Ekim 2026. SERÜVEN BİTTİ! 🎓',
            'advisory', 'high', 'residence_permit', 'res_completed',
            $this->burak, $this->d(153), $this->d(153), $sid);

        ProcessOutcome::create([
            'student_id' => $sid, 'process_step' => 'official_services', 'outcome_type' => 'acceptance',
            'university' => 'Technische Universität Berlin', 'program' => 'Informatik B.Sc.',
            'details_tr' => 'TU Berlin\'e kayıt tamamlandı. Öğrenci No: 456789. Toplam süre: 155 gün (kampanya yayından üniversite kaydına). ABH randevu iptali dışında son faz sorunsuz tamamlandı.',
            'is_visible_to_student' => true, 'created_at' => $this->d(153),
        ]);

        foreach ([$this->muzeyyen, $this->burak] as $u) {
            NotificationDispatch::create([
                'company_id' => $this->company->id, 'user_id' => $u->id, 'channel' => 'in_app',
                'category' => 'student_success',
                'subject'  => '🎓 TU Berlin Kaydı Tamamlandı — Dana Yıldız',
                'body'     => 'Dana Yıldız, TU Berlin Informatik B.Sc.\'e başarıyla kayıt oldu. Süre: 155 gün. Öğrenci No: 456789.',
                'status' => 'sent', 'student_id' => $sid, 'source_type' => 'student_journey', 'triggered_by' => 'system',
            ]);
        }

        $this->info('  → 8 task (1 ABH aksaklık dahil) + final process outcome + 2 bildirim [done]');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  YARDIMCI METODLAR
    // ══════════════════════════════════════════════════════════════════════════
    private function task(
        string $srcType, string $srcId, string $assigneeName,
        string $title, string $desc,
        string $dept, string $priority,
        string $process, string $stage,
        User $assignee, Carbon $due, Carbon $done,
        ?string $sid = null,
    ): MarketingTask {
        return MarketingTask::create([
            'company_id'          => $this->company->id,
            'title'               => '[' . $assigneeName . '] ' . $title,
            'description'         => $desc,
            'status'              => 'done',
            'priority'            => $priority,
            'department'          => $dept,
            'process_type'        => $process,
            'workflow_stage'      => $stage,
            'related_student_id'  => $sid,
            'source_type'         => $srcType,
            'source_id'           => $srcId,
            'assigned_user_id'    => $assignee->id,
            'created_by_user_id'  => $this->muzeyyen->id,
            'is_auto_generated'   => false,
            'due_date'            => $due->toDateString(),
            'completed_at'        => $done,
            'created_at'          => $due,
            'updated_at'          => $done,
        ]);
    }

    private function user(string $email, string $name, string $role): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => Hash::make('ChangeMe123!'), 'is_active' => true, 'company_id' => $this->company->id]
        );
    }

    private function info(string $msg): void { $this->command->info($msg); }
}
