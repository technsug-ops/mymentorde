<?php

namespace Database\Seeders;

use App\Models\DataProcessingAgreement;
use Illuminate\Database\Seeder;

/**
 * Default AVV Registry — Mentorde'nin gerçek 3. taraf sağlayıcıları.
 *
 * privacy_de.txt'deki tedarikçi listesinden çıkarılmıştır. Yeni SaaS müşterisi
 * açıldığında benzer bir başlangıç listesi sağlanabilir; müşteri kendi
 * sağlayıcılarını eder/siler/günceller.
 *
 * Idempotent — updateOrCreate (provider_name + company_id key).
 *
 * NOT: avv_pdf_path null kalır. Çoğu cloud sağlayıcının DPA'sı click-through
 * yoluyla zaten kabul edilmiş sayılır, ancak resmi PDF'i provider compliance
 * portal'ından indirip burada yüklemek best practice'tir.
 */
class DefaultAvvRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $companyId = (int) (env('COMPANY_ID') ?: 1);

        $providers = [
            // ─── Hosting ────────────────────────────────────────────────────
            [
                'provider_name'        => 'ALL-INKL.COM (KASSERVER)',
                'provider_url'         => 'https://all-inkl.com',
                'contact_email'        => 'datenschutz@all-inkl.com',
                'country'              => 'Almanya',
                'eu_based'             => true,
                'purpose_summary'      => 'Web hosting, MySQL veritabanı, FTP, SSL/TLS termination',
                'processed_categories' => ['IP adresleri', 'kullanıcı oturum verileri', 'tüm DB içeriği', 'sunucu logları'],
                'status'               => 'active',
                'notes'                => 'Sunucular Almanya\'da. Üçüncü ülke aktarımı yok. Standart hosting AVV kabul edilmiş.',
            ],

            // ─── Ödeme ──────────────────────────────────────────────────────
            [
                'provider_name'        => 'Stripe Payments Europe Ltd.',
                'provider_url'         => 'https://stripe.com/de/privacy',
                'contact_email'        => 'privacy@stripe.com',
                'country'              => 'İrlanda',
                'eu_based'             => true,
                'purpose_summary'      => 'Online ödeme alma, kart işleme, fatura, fraud prevention',
                'processed_categories' => ['ad-soyad', 'email', 'fatura adresi', 'kart son 4 hane', 'işlem ID', 'tutar'],
                'status'               => 'active',
                'notes'                => 'EU controller İrlanda; ABD\'ye fraud/compliance için aktarım var (DPF + SCC). Click-through DPA: stripe.com/legal/dpa',
            ],

            // ─── Email (Transactional) ──────────────────────────────────────
            [
                'provider_name'        => 'Resend, Inc.',
                'provider_url'         => 'https://resend.com/legal/dpa',
                'contact_email'        => 'privacy@resend.com',
                'country'              => 'ABD',
                'eu_based'             => false,
                'purpose_summary'      => 'Transactional e-posta gönderimi (welcome, password reset, sözleşme tamamlama, bildirimler)',
                'processed_categories' => ['email adresi', 'alıcı adı', 'mail içeriği', 'gönderim metadata'],
                'status'               => 'active',
                'notes'                => '3. ülke (ABD). DPF zertifiziert + SCC. DPA: resend.com/legal/dpa. PDF indirip yükle.',
            ],

            // ─── Web Analytics ─────────────────────────────────────────────
            [
                'provider_name'        => 'PostHog Inc.',
                'provider_url'         => 'https://posthog.com/dpa',
                'contact_email'        => 'privacy@posthog.com',
                'country'              => 'ABD (parent) / EU Cloud Frankfurt',
                'eu_based'             => true, // EU Cloud bölgesi kullanıldığı için
                'purpose_summary'      => 'Anonim kullanıcı davranış analitiği, ürün iyileştirme',
                'processed_categories' => ['anonim user ID', 'IP (anonimleştirilmiş)', 'sayfa görüntüleme', 'tıklama yolu', 'browser/device info'],
                'status'               => 'active',
                'notes'                => 'EU Cloud bölgesi (Frankfurt) seçili. Support için ABD parent erişebilir → SCC. 14 ay saklama.',
            ],

            // ─── AI Sağlayıcıları ───────────────────────────────────────────
            [
                'provider_name'        => 'OpenAI Ireland Ltd.',
                'provider_url'         => 'https://openai.com/policies/data-processing-addendum',
                'contact_email'        => 'privacy@openai.com',
                'country'              => 'İrlanda (parent ABD)',
                'eu_based'             => true,
                'purpose_summary'      => 'AI Labs içerik üretimi, manager AI asistanı (writer provider)',
                'processed_categories' => ['kullanıcı promptları', 'oluşturulan içerikler', 'sohbet geçmişi'],
                'status'               => 'active',
                'notes'                => 'Zero-retention API kullanımı (eğitim için kullanılmıyor). EU controller İrlanda; ABD aktarımı SCC kapsamında.',
            ],
            [
                'provider_name'        => 'Anthropic PBC',
                'provider_url'         => 'https://www.anthropic.com/legal/dpa',
                'contact_email'        => 'privacy@anthropic.com',
                'country'              => 'ABD',
                'eu_based'             => false,
                'purpose_summary'      => 'AI Labs Claude modeli (yazma + asistan)',
                'processed_categories' => ['kullanıcı promptları', 'oluşturulan içerikler', 'sohbet geçmişi'],
                'status'               => 'active',
                'notes'                => '3. ülke (ABD). Zero-retention API. DPF + SCC. DPA: anthropic.com/legal/dpa',
            ],
            [
                'provider_name'        => 'Google Ireland Ltd. — Gemini API',
                'provider_url'         => 'https://cloud.google.com/terms/data-processing-addendum',
                'contact_email'        => 'data-protection-office@google.com',
                'country'              => 'İrlanda (parent ABD)',
                'eu_based'             => true,
                'purpose_summary'      => 'AI Labs Gemini modeli (yurt dışı eğitim bilgi havuzu)',
                'processed_categories' => ['kullanıcı promptları', 'oluşturulan içerikler'],
                'status'               => 'active',
                'notes'                => 'Google Workspace DPA kapsamında. EU controller İrlanda; ABD aktarımı SCC.',
            ],

            // ─── Takvim ─────────────────────────────────────────────────────
            [
                'provider_name'        => 'Google Ireland Ltd. — Calendar',
                'provider_url'         => 'https://workspace.google.com/terms/dpa_terms.html',
                'contact_email'        => 'data-protection-office@google.com',
                'country'              => 'İrlanda (parent ABD)',
                'eu_based'             => true,
                'purpose_summary'      => 'Opsiyonel randevu / mentoring senkronizasyonu',
                'processed_categories' => ['randevu başlığı', 'tarih/saat', 'katılımcı email', 'opsiyonel notlar'],
                'status'               => 'active',
                'notes'                => 'Sadece opsiyonel — kullanıcı bağladığında etkinleşir. Google Workspace DPA + SCC.',
            ],

            // ─── İletişim Platformu ─────────────────────────────────────────
            [
                'provider_name'        => 'Meta Platforms Ireland Ltd. — WhatsApp',
                'provider_url'         => 'https://www.whatsapp.com/legal/business-data-transfer-addendum',
                'contact_email'        => 'EU-DPO@meta.com',
                'country'              => 'İrlanda (parent ABD)',
                'eu_based'             => true,
                'purpose_summary'      => 'Müşteri iletişimi (telefon: +49 157 84218282 üzerinden)',
                'processed_categories' => ['telefon numarası', 'mesaj içeriği', 'iletişim zaman damgaları'],
                'status'               => 'active',
                'notes'                => 'EU-U.S. Data Privacy Framework + SCC. Hassas konular için email tercih edilir.',
            ],
        ];

        foreach ($providers as $row) {
            DataProcessingAgreement::query()->updateOrCreate(
                ['company_id' => $companyId, 'provider_name' => $row['provider_name']],
                $row + ['company_id' => $companyId]
            );
        }

        $this->command?->info("✅ AVV Registry seeded — " . count($providers) . " sağlayıcı (company_id={$companyId})");
    }
}
