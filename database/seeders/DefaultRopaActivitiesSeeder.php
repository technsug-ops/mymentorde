<?php

namespace Database\Seeders;

use App\Models\ProcessingActivity;
use Illuminate\Database\Seeder;

/**
 * Default ROPA aktiviteleri — Mentorde'nin tipik 8 işlem süreci.
 *
 * DSGVO Art. 30 zorunlu Verarbeitungsverzeichnis. Yeni SaaS müşterisi
 * açıldığında benzer bir başlangıç listesi sağlanabilir; her müşteri
 * kendi süreçlerine göre düzenler.
 *
 * Idempotent — updateOrCreate (name + company_id key).
 */
class DefaultRopaActivitiesSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = (int) (env('COMPANY_ID') ?: 1);

        $activities = [
            // ─── 1. Aday öğrenci / iletişim formu ───────────────────────────
            [
                'name'                   => 'Aday öğrenci başvurusu (lead form)',
                'responsible'            => 'Sales / Senior Mentor',
                'purpose'                => 'Web sitesi ve dealer kanallarından gelen aday öğrenci kayıtlarının ön değerlendirilmesi, mentor ataması, iletişim kurulması',
                'data_categories'        => ['ad-soyad', 'email', 'telefon', 'doğum tarihi', 'eğitim durumu', 'hedef ülke', 'bütçe aralığı'],
                'subject_categories'     => ['aday öğrenciler', 'web ziyaretçileri'],
                'recipients'             => ['ALL-INKL.COM (hosting)', 'Resend (mail)', 'PostHog (anonim analytics)'],
                'legal_basis'            => 'art_6_1_b', // sözleşme öncesi (pre-contractual)
                'third_country_transfer' => true,
                'third_country_country'  => 'ABD (Resend mail + PostHog support; DPF + SCC)',
                'retention_days'         => 1095, // 3 yıl
                'security_measures'      => "TLS 1.3 zorunlu\nCSRF koruması\n2FA zorunlu (manager+ rolleri)\nAudit log",
                'notes'                  => 'Aday 6 ay içinde kayıt olmazsa otomatik anonimleştirilir.',
                'is_active'              => true,
            ],

            // ─── 2. Öğrenci kayıt + sözleşme ────────────────────────────────
            [
                'name'                   => 'Öğrenci kayıt + danışmanlık sözleşmesi',
                'responsible'            => 'Mentor / Manager',
                'purpose'                => 'Danışmanlık sözleşmesinin imzalanması, hizmet ifası, fatura kesimi, KPI takibi',
                'data_categories'        => ['ad-soyad', 'TC kimlik', 'pasaport bilgisi', 'doğum yeri-tarihi', 'fatura adresi', 'iletişim', 'aile durumu', 'eğitim geçmişi'],
                'subject_categories'     => ['kayıtlı öğrenciler', 'aile bireyleri (ödeme yapan veliler)'],
                'recipients'             => ['ALL-INKL.COM (hosting)', 'Resend (sözleşme mail)', 'doc-template PDF üretimi (iç)'],
                'legal_basis'            => 'art_6_1_b', // sözleşme ifası
                'third_country_transfer' => true,
                'third_country_country'  => 'ABD (sadece Resend transactional mail; DPF + SCC)',
                'retention_days'         => 3650, // 10 yıl HGB
                'security_measures'      => "Sözleşme PDF'leri private storage\nİmzalı kopyalar Storage::disk('local')\nRol-bazlı erişim (Manager only)\n2FA + audit log",
                'notes'                  => 'HGB §257(4) gereği 10 yıl ticari kayıt olarak saklanır.',
                'is_active'              => true,
            ],

            // ─── 3. Online ödeme alma ────────────────────────────────────────
            [
                'name'                   => 'Online ödeme + faturalandırma',
                'responsible'            => 'Manager / Finance',
                'purpose'                => 'Danışmanlık ücreti tahsilatı, taksitli ödeme, fatura kesimi, muhasebe kayıtları',
                'data_categories'        => ['ad-soyad', 'fatura adresi', 'IBAN', 'kart son 4 hane', 'Stripe transaction ID', 'işlem tutarı', 'fatura no'],
                'subject_categories'     => ['kayıtlı öğrenciler', 'ödeme yapan veliler'],
                'recipients'             => ['Stripe Payments Europe Ltd.', 'ALL-INKL.COM (hosting)', 'muhasebe (manuel export)'],
                'legal_basis'            => 'art_6_1_b',
                'third_country_transfer' => true,
                'third_country_country'  => 'ABD (Stripe fraud/compliance için corporate transfer; DPF + SCC)',
                'retention_days'         => 3650, // 10 yıl AO §147(3)
                'security_measures'      => "Kart bilgisi DB'ye yazılmaz (sadece Stripe token)\nWebhook secret doğrulama\nRol-bazlı erişim (Manager+Finance)\nAudit log",
                'notes'                  => 'AO §147(3) gereği 10 yıl mali kayıt. Stripe webhook signature doğrulanır.',
                'is_active'              => true,
            ],

            // ─── 4. Üniversite başvurusu ────────────────────────────────────
            [
                'name'                   => 'Almanya üniversite başvuru süreci',
                'responsible'            => 'Senior Mentor / Application Specialist',
                'purpose'                => 'Uni-Assist + üniversite portalları üzerinden başvuru, belge yükleme, sonuç takibi',
                'data_categories'        => ['ad-soyad', 'pasaport sureti', 'lise diploması + transkript', 'üniversite diploması', 'dil sertifikaları (TestDaF/DSH/IELTS/TOEFL)', 'motivasyon mektubu', 'CV', 'tercih sıralaması'],
                'subject_categories'     => ['kayıtlı öğrenciler'],
                'recipients'             => ['Uni-Assist e.V. (Berlin)', 'Almanya üniversiteleri (40+ kurum)', 'ALL-INKL.COM (hosting)'],
                'legal_basis'            => 'art_6_1_b',
                'third_country_transfer' => false,
                'third_country_country'  => null,
                'retention_days'         => 1825, // 5 yıl (mezuniyet sonrası ispat yükümlülüğü)
                'security_measures'      => "Belgeler Storage::disk('local') private\nPDF SHA-256 hash logged\nÜniversite portalına manuel upload (operator audit)\nRol-bazlı erişim",
                'notes'                  => 'Uni-Assist ve üniversiteler bağımsız Veri Sorumlusu (Joint Controller değil).',
                'is_active'              => true,
            ],

            // ─── 5. Vize + konsolosluk ──────────────────────────────────────
            [
                'name'                   => 'Vize başvurusu (Konsolosluk + Ausländerbehörde)',
                'responsible'            => 'Visa Specialist / Senior Mentor',
                'purpose'                => 'Almanya öğrenci vizesi başvuru desteği, randevu organizasyonu, belge hazırlığı',
                'data_categories'        => ['pasaport sureti', 'biyometrik fotoğraf', 'kabul mektubu', 'finansal kanıtlar', 'sağlık sigortası belgesi', 'adres belgesi', 'aile durumu', 'önceki seyahat geçmişi'],
                'subject_categories'     => ['kayıtlı öğrenciler', 'aile bireyleri (vize başvurusu için referans gösterilen)'],
                'recipients'             => ['Almanya Konsoloslukları (İstanbul, Ankara, İzmir)', 'Ausländerbehörde (yerel yabancılar dairesi)', 'ALL-INKL.COM'],
                'legal_basis'            => 'art_6_1_b',
                'third_country_transfer' => false,
                'third_country_country'  => null,
                'retention_days'         => 1825, // 5 yıl
                'security_measures'      => "Hassas belgeler private storage\nGörüntü dosyaları su işareti\nManager onayı zorunlu",
                'notes'                  => 'Adli sicil belgesi gerekirse Art. 9 GDPR (özel kategori) — açık rıza ile.',
                'is_active'              => true,
            ],

            // ─── 6. Bloke hesap + sigorta ───────────────────────────────────
            [
                'name'                   => 'Bloke hesap + sağlık sigortası işlemleri',
                'responsible'            => 'Operations Specialist',
                'purpose'                => 'Almanya yasal zorunluluğu olan bloke hesap (€11.904/yıl) ve sağlık sigortası başvuru süreci',
                'data_categories'        => ['ad-soyad', 'pasaport bilgisi', 'doğum tarihi', 'iletişim', 'finansal kaynak beyanı', 'doğum tarihi', 'sağlık beyanı (sigorta için)'],
                'subject_categories'     => ['kayıtlı öğrenciler', 'sponsor (varsa veli)'],
                'recipients'             => ['Expatrio GmbH (Berlin)', 'Coracle GmbH (Düsseldorf)', 'Fintiba GmbH (Frankfurt)', 'TK / DAK / AOK sağlık sigortaları'],
                'legal_basis'            => 'art_6_1_b',
                'third_country_transfer' => false,
                'third_country_country'  => null,
                'retention_days'         => 1825,
                'security_measures'      => "Sağlayıcılarla AVV imzalı\nBelgeler şifreli yüklenir (HTTPS)\nÖğrencinin onay verdiği listeyle sınırlı paylaşım",
                'notes'                  => 'Sağlık beyanı Art. 9 GDPR — açık rıza ile sigorta şirketine iletilir.',
                'is_active'              => true,
            ],

            // ─── 7. AI Labs içerik üretimi + asistan ────────────────────────
            [
                'name'                   => 'AI Labs sorgu + içerik üretimi',
                'responsible'            => 'Marketing / Content Team / Manager',
                'purpose'                => 'İç asistan sorguları, blog/SEO içerik üretimi, public FAQ asistanı (/sss)',
                'data_categories'        => ['kullanıcı promptları', 'oluşturulan içerikler', 'pseudonimleştirilmiş session ID', 'lead bilgisi (FAQ üzerinden gelirse)'],
                'subject_categories'     => ['mentorde personeli (iç asistan)', 'web ziyaretçileri (public /sss)'],
                'recipients'             => ['OpenAI Ireland Ltd.', 'Anthropic PBC', 'Google Ireland Ltd. (Gemini)'],
                'legal_basis'            => 'art_6_1_f', // iç kullanım için meşru menfaat; public için art_6_1_a (rıza)
                'third_country_transfer' => true,
                'third_country_country'  => 'ABD (Anthropic + OpenAI/Google parent transfers; DPF + SCC; zero-retention)',
                'retention_days'         => 365, // 1 yıl chat geçmişi
                'security_measures'      => "Zero-retention API mode\nUser prompts pseudonymized session ID ile\nKişisel veri girişi UI'da uyarılır\n5-15 req/dk throttle",
                'notes'                  => 'Sağlayıcılar promptları model eğitiminde kullanmaz (sözleşme garantisi).',
                'is_active'              => true,
            ],

            // ─── 8. Web analytics ───────────────────────────────────────────
            [
                'name'                   => 'Web analytics + ürün davranış analizi',
                'responsible'            => 'Product / Marketing',
                'purpose'                => 'Sayfa kullanım analizi, conversion funnel, feature kullanım metrikleri, ürün iyileştirme',
                'data_categories'        => ['anonim user ID', 'IP (anonimleştirilmiş)', 'sayfa yolu', 'tıklama yolu', 'browser/device meta', 'oturum süresi'],
                'subject_categories'     => ['web ziyaretçileri', 'kayıtlı kullanıcılar'],
                'recipients'             => ['PostHog Inc. (EU Cloud Frankfurt)'],
                'legal_basis'            => 'art_6_1_a', // açık rıza (cookie banner)
                'third_country_transfer' => true,
                'third_country_country'  => 'ABD (sadece support/maintenance için parent erişim; SCC)',
                'retention_days'         => 425, // ~14 ay (PostHog default)
                'security_measures'      => "EU bölgesi (Frankfurt) seçildi\nIP son oktet kırpılır\nCookie banner ile rıza zorunlu\nOpt-out her zaman mümkün",
                'notes'                  => 'Cookie banner reddedilirse PostHog snippet hiç yüklenmez.',
                'is_active'              => true,
            ],
        ];

        foreach ($activities as $row) {
            ProcessingActivity::query()->updateOrCreate(
                ['company_id' => $companyId, 'name' => $row['name']],
                $row + ['company_id' => $companyId]
            );
        }

        $this->command?->info("✅ ROPA seeded — " . count($activities) . " aktivite (company_id={$companyId})");
    }
}
