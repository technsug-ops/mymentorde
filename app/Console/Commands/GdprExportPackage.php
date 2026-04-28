<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DataProcessingAgreement;
use App\Models\PolicyDocument;
use App\Models\ProcessingActivity;
use Illuminate\Console\Command;
use ZipArchive;

/**
 * GDPR/DSGVO Yasal Paket Export — Anwalt incelemesi için tek ZIP halinde
 * çıktı verir. İçerik:
 *  - README.md (kapak + envanter + placeholder uyarıları)
 *  - policies/{kind}_{lang}.md (18 doküman: privacy/cookie/terms/imprint/tom/incident_plan × DE/EN/TR)
 *  - avv-registry.csv (Art. 28 — 9 sağlayıcı)
 *  - ropa-activities.csv (Art. 30 — 8 aktivite)
 *
 * Kullanım: php artisan gdpr:export-package
 *           php artisan gdpr:export-package --company=2
 */
class GdprExportPackage extends Command
{
    protected $signature = 'gdpr:export-package {--company=1 : company_id}';
    protected $description = 'GDPR/DSGVO yasal paketini Anwalt incelemesi için ZIP olarak çıkar';

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $stamp     = now()->format('Y-m-d_His');
        $zipPath   = storage_path("app/gdpr-export-c{$companyId}-{$stamp}.zip");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("ZIP oluşturulamadı: {$zipPath}");
            return self::FAILURE;
        }

        $companyName = Company::query()->withoutGlobalScopes()->find($companyId)?->name ?? "Company #{$companyId}";

        // ─── README ──────────────────────────────────────────────────────
        $zip->addFromString('README.md', $this->buildReadme($companyId, $companyName, $stamp));

        // ─── Policy Documents (6 kind × 3 dil) ───────────────────────────
        $docs = PolicyDocument::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('kind')
            ->orderBy('locale')
            ->get();

        foreach ($docs as $doc) {
            $markdown = "# {$doc->title}\n\n"
                     . "_Son güncelleme: {$doc->updated_at?->format('d.m.Y H:i')}_\n\n"
                     . "---\n\n"
                     . $this->htmlToMarkdown($doc->body);

            $zip->addFromString("policies/{$doc->kind}_{$doc->locale}.md", $markdown);
        }

        // ─── AVV Registry (Art. 28) ──────────────────────────────────────
        $zip->addFromString('avv-registry.csv', $this->buildAvvCsv($companyId));

        // ─── ROPA (Art. 30) ──────────────────────────────────────────────
        $zip->addFromString('ropa-activities.csv', $this->buildRopaCsv($companyId));

        $zip->close();

        $sizeKb = number_format(filesize($zipPath) / 1024, 1);
        $this->info("✅ GDPR paket hazır:");
        $this->line("   Path: {$zipPath}");
        $this->line("   Boyut: {$sizeKb} KB");
        $this->line("   İçerik: " . count($docs) . " policy doküman + AVV (" . DataProcessingAgreement::query()->withoutGlobalScopes()->where('company_id', $companyId)->count() . " satır) + ROPA (" . ProcessingActivity::query()->withoutGlobalScopes()->where('company_id', $companyId)->count() . " satır)");

        return self::SUCCESS;
    }

    private function buildReadme(int $companyId, string $companyName, string $stamp): string
    {
        $docCount = PolicyDocument::query()->withoutGlobalScopes()->where('company_id', $companyId)->count();
        $avvCount = DataProcessingAgreement::query()->withoutGlobalScopes()->where('company_id', $companyId)->count();
        $ropaCount = ProcessingActivity::query()->withoutGlobalScopes()->where('company_id', $companyId)->count();

        return <<<MD
# GDPR / DSGVO Yasal Paket — {$companyName}

**Export tarihi:** {$stamp}
**Company ID:** {$companyId}

Bu paket, mentorde.com platformunun DSGVO/GDPR uyumluluk altyapısının bir snapshot'ıdır.
Anwalt / Datenschutzbeauftragter incelemesi için hazırlanmıştır.

## İçerik

| Klasör / Dosya | Açıklama | Adet |
|---|---|---|
| `policies/` | Yasal metinler (Markdown) | {$docCount} |
| `avv-registry.csv` | Veri işleme sözleşmeleri (DSGVO Art. 28) | {$avvCount} |
| `ropa-activities.csv` | İşlem aktiviteleri sicili (DSGVO Art. 30) | {$ropaCount} |

## Yasal Metinler (`policies/`)

Her metin 3 dilde mevcuttur (DE = Almanca = bağlayıcı sürüm, EN = İngilizce, TR = Türkçe):

| Kind | Doküman Tipi | Public URL | DSGVO Atfı |
|---|---|---|---|
| `privacy` | Datenschutzerklärung / Privacy Policy / KVKK | /datenschutz, /privacy | Art. 12-14, 15-22 |
| `cookie` | Cookie-Richtlinie / Cookie Policy / Çerez Politikası | /cookies | § 25 TDDDG |
| `terms` | AGB / Terms / Kullanım Koşulları | /agb, /terms | BGB §§ 305-310, 312g |
| `imprint` | Impressum / Legal Notice / Yasal Bildirim | /impressum, /imprint | § 5 TMG, § 18 MStV |
| `tom` | Technische und Organisatorische Maßnahmen | iç doküman | Art. 32 |
| `incident_plan` | Datenpannen-Notfallplan | iç doküman | Art. 33-34 |

## Verifiye Edilen Şirket Bilgileri

- **Şirket:** Horizon STS GmbH
- **Adres:** Herwigstraße 24, 35683 Dillenburg, Deutschland
- **Geschäftsführer:** Halil Aktas
- **Handelsregister:** Amtsgericht Wetzlar, HRB 9127
- **Yetkili Otorite:** Hessischer Beauftragter für Datenschutz (Wiesbaden)

## ⏳ Anwalt Tarafından Doldurulacak / Onaylanacak Yerler

1. **USt-IdNr.** — şu an `DE 999 999 999` (muster) → gerçek numara ile değiştirilmeli
   - Etkilenen 8 yer: privacy_de/en/tr, terms_de/en, imprint_de/en/tr
2. **Aktif sosyal medya hesapları** — `policies/privacy_*.md` Bölüm 16 (Social-Media-Präsenzen) — kullanılmayan platformlar silinmeli
3. **Newsletter sağlayıcı** — şu an "kullanılmıyor" beyanı; ileride aktive edilirse Bölüm 9 güncellenmeli
4. **Datenschutzbeauftragter ataması** — şirket büyüyüp 20+ otomatik işlemeli çalışana ulaşırsa zorunlu

## ⚠ Bilinen Açık Konular

- Sahip değişimi **Mayıs ilk hafta 2026** planlanıyor → Geschäftsführer alanı yeniden güncellenecek
- TOM Bölüm 6.1 — yıllık dış pentest (büyüme ile birlikte aktive edilecek)
- Incident Plan Bölüm 2 — "Teknik Koordinasyon" rolü için kişi atanmalı
- Imprint Bölüm 7 — VSBG sonrası "tüketici tahkim red" kararı doğrulanmalı

## Teknik Altyapı (TOM ile Uyumlu)

- Hosting: ALL-INKL.COM (Friedersdorf, DE) — sunucular Almanya'da
- Şifreleme: TLS 1.3 (transit), LUKS (rest), bcrypt cost ≥ 12
- 2FA: TOTP zorunlu (admin rolleri)
- Backup: günlük (mysqldump) + haftalık snapshot, 30 gün retention
- Audit log: append-only, ≥ 12 ay
- Self-hosted fonts (LG München I 2022 uyumu)
- Cookie banner: 3-button DSGVO uyumlu
- Veri merkezi: Frankfurt (PostHog EU Cloud) + Almanya (hosting)

## 3. Ülke Aktarımları (`avv-registry.csv`)

USA aktarımları DPF + SCC kapsamında:
- Resend (transactional email)
- Anthropic (AI)
- PostHog parent company support transfers
- Stripe corporate transfers

EU-içi sağlayıcılar (gönüllü olarak listelendi):
- ALL-INKL.COM (DE), Stripe Payments Europe (IE), OpenAI Ireland, Google Ireland (Gemini + Calendar), Meta Ireland (WhatsApp)

## İletişim

Veri koruma soruları: info@mentorde.com
Genel/sözleşme soruları: support@mentorde.com
Telefon: +49 157 84218282

---

**Önemli:** Bu paket bir compliance snapshot'ıdır, üretken bir referans değil.
Yayın öncesi tüm metinler bir IT-/Datenschutzrecht uzmanı tarafından
onaylanmalı; özellikle USt-IdNr. ve aktif sosyal medya bilgileri
production'a almadan önce gerçek değerlerle değiştirilmelidir.
MD;
    }

    private function buildAvvCsv(int $companyId): string
    {
        $rows = DataProcessingAgreement::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('provider_name')
            ->get();

        $fp = fopen('php://temp', 'r+');
        // BOM (Excel UTF-8)
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [
            'Sağlayıcı Adı', 'Web URL', 'İletişim', 'Ülke', 'EU?', 'Status',
            'Amaç', 'İşlenen Kategoriler', 'İmza', 'Bitiş', 'Notlar', 'PDF',
        ]);
        foreach ($rows as $r) {
            fputcsv($fp, [
                $r->provider_name,
                $r->provider_url,
                $r->contact_email,
                $r->country,
                $r->eu_based ? 'Evet' : 'Hayır',
                DataProcessingAgreement::STATUS_LABELS[$r->status] ?? $r->status,
                $r->purpose_summary,
                implode(', ', (array) ($r->processed_categories ?? [])),
                $r->signed_date?->format('d.m.Y'),
                $r->expires_date?->format('d.m.Y'),
                $r->notes,
                $r->avv_pdf_path ? 'Yüklü' : 'Yok',
            ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }

    private function buildRopaCsv(int $companyId): string
    {
        $rows = ProcessingActivity::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [
            'Aktivite Adı', 'Sorumlu', 'Amaç', 'Veri Kategorileri', 'Kişi Kategorileri',
            'Alıcılar', 'Hukuki Dayanak', '3. Ülke Aktarımı', 'Saklama (gün)',
            'Güvenlik Önlemleri', 'Aktif', 'Notlar',
        ]);
        foreach ($rows as $r) {
            fputcsv($fp, [
                $r->name, $r->responsible, $r->purpose,
                implode(', ', (array) ($r->data_categories ?? [])),
                implode(', ', (array) ($r->subject_categories ?? [])),
                implode(', ', (array) ($r->recipients ?? [])),
                ProcessingActivity::LEGAL_BASIS[$r->legal_basis] ?? $r->legal_basis,
                $r->third_country_transfer ? ('Evet — ' . $r->third_country_country) : 'Hayır',
                $r->retention_days,
                $r->security_measures,
                $r->is_active ? 'Evet' : 'Hayır',
                $r->notes,
            ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }

    /**
     * Basit HTML → Markdown dönüşümü. Seeder'ın textToHtml'inin tersi.
     * <h2>...</h2> → ## ...
     * <h3>...</h3> → ### ...
     * <p>...</p>   → satır
     * <ul><li>...</li></ul> → - satırlar
     */
    private function htmlToMarkdown(string $html): string
    {
        $text = $html;

        // Heading'ler
        $text = preg_replace('/<h2>(.*?)<\/h2>/us', "## $1\n", $text);
        $text = preg_replace('/<h3>(.*?)<\/h3>/us', "### $1\n", $text);

        // Listeler
        $text = preg_replace('/<ul>(.*?)<\/ul>/us', "$1", $text);
        $text = preg_replace('/<li>(.*?)<\/li>/us', "- $1\n", $text);

        // Paragraflar
        $text = preg_replace('/<p>(.*?)<\/p>/us', "$1\n\n", $text);

        // Geri kalan tagları temizle
        $text = strip_tags($text);

        // HTML entity'leri çöz
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Çoklu boş satırları normalize et
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
