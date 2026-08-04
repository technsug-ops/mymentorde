<?php

namespace Tests\Unit\Services;

use App\Services\DocumentNamingService;
use PHPUnit\Framework\TestCase;

/**
 * DocumentNamingService — Belge adlandırma kuralları için saf unit testler.
 *
 * DB bağlantısı gerekmez. Sadece string işleme mantığı test edilir.
 *
 * NOT: Bu testler eskiden `$tags` dizisi alan bir imzayı test ediyordu; servis
 * o zamandan beri {KATEGORI}_{OGRENCI}_{TARIH}_{AdBaşHarfi}_{Soyad}.{ext}
 * formatına geçmiş ama testler güncellenmemişti (8 test TypeError veriyordu).
 */
class DocumentNamingServiceTest extends TestCase
{
    private DocumentNamingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentNamingService();
    }

    // ── buildStandardFileName ────────────────────────────────────────────────

    public function test_standard_filename_format_is_correct(): void
    {
        $name = $this->service->buildStandardFileName('GST00000051', 'DOC-PASS', 'Mehmet', 'Yilmaz', 'png');

        // {KATEGORI}_{OGRENCI}_{YYYYAAGG}_{AdBaşHarfi}_{Soyad}.{ext}
        $this->assertMatchesRegularExpression(
            '/^DOC-PASS_GST00000051_\d{8}_M_Yilmaz\.png$/',
            $name
        );
    }

    public function test_special_chars_stripped_from_student_id(): void
    {
        $name = $this->service->buildStandardFileName('std-001/abc', 'DOC', 'Ali', 'Veli', 'pdf');

        // Tire korunur, eğik çizgi düşer, büyük harfe çevrilir
        $this->assertStringContainsString('_STD-001ABC_', $name);
    }

    public function test_special_chars_stripped_from_category(): void
    {
        $name = $this->service->buildStandardFileName('STD001', 'doc-type/special', 'Ali', 'Veli', 'pdf');

        $this->assertStringStartsWith('DOC-TYPESPECIAL_', $name);
    }

    public function test_extension_is_lowercase(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', 'A', 'B', 'PDF');

        $this->assertStringEndsWith('.pdf', $name);
    }

    public function test_empty_extension_defaults_to_pdf(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', 'A', 'B', '');

        $this->assertStringEndsWith('.pdf', $name);
    }

    public function test_turkish_characters_in_surname_are_transliterated(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', 'Şeyma', 'Güngör', 'pdf');

        $this->assertStringContainsString('_Gungor.', $name);
        $this->assertStringContainsString('_S_', $name, 'Ad baş harfi büyük harfe çevrilmeli.');
    }

    public function test_missing_name_falls_back_to_placeholders(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', '', '', 'pdf');

        // Ad yoksa X, soyad yoksa "bilinmiyor"
        $this->assertStringContainsString('_X_bilinmiyor.pdf', $name);
    }

    public function test_surname_spaces_become_single_dash(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', 'Ali', 'Van  Der  Berg', 'pdf');

        $this->assertStringContainsString('_Van-Der-Berg.pdf', $name);
    }

    // ── buildDocumentId ──────────────────────────────────────────────────────

    public function test_document_id_format(): void
    {
        $id = $this->service->buildDocumentId(42);

        $this->assertMatchesRegularExpression('/^DOC-\d{4}-000042$/', $id);
    }

    public function test_document_id_zero_padded_to_6_digits(): void
    {
        $this->assertStringEndsWith('-000001', $this->service->buildDocumentId(1));
        $this->assertStringEndsWith('-001000', $this->service->buildDocumentId(1000));
        $this->assertStringEndsWith('-999999', $this->service->buildDocumentId(999999));
    }

    public function test_document_id_contains_current_year(): void
    {
        $this->assertStringContainsString((string) date('Y'), $this->service->buildDocumentId(7));
    }
}
