<?php

namespace Tests\Unit\Services;

use App\Services\DocumentNamingService;
use PHPUnit\Framework\TestCase;

/**
 * DocumentNamingService — Belge adlandırma kuralları için saf unit testler.
 *
 * DB bağlantısı gerekmez. Sadece string işleme mantığı test edilir.
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
        $name = $this->service->buildStandardFileName('STD-001', 'PASAPORT', ['visa', 'apply'], 'pdf');

        // STUDENT_CATEGORY_tags_timestamp.ext formatında
        $this->assertMatchesRegularExpression(
            '/^STD001_PASAPORT_visa-apply_\d{8}_\d{6}\.pdf$/',
            $name
        );
    }

    public function test_special_chars_stripped_from_student_id(): void
    {
        $name = $this->service->buildStandardFileName('std-001/abc', 'DOC', [], 'pdf');

        $this->assertStringStartsWith('STD001ABC_', $name);
    }

    public function test_special_chars_stripped_from_category(): void
    {
        $name = $this->service->buildStandardFileName('STD001', 'doc-type/special', [], 'pdf');

        $this->assertStringContainsString('_DOCTYPESPECIAL_', $name);
    }

    public function test_empty_tags_uses_general(): void
    {
        $name = $this->service->buildStandardFileName('STD001', 'PASAPORT', [], 'pdf');

        $this->assertStringContainsString('_general_', $name);
    }

    public function test_max_4_tags_used(): void
    {
        $name = $this->service->buildStandardFileName('S', 'C', ['a', 'b', 'c', 'd', 'e'], 'pdf');

        // 5 tag var ama sadece 4'ü alınmalı
        $this->assertStringContainsString('_a-b-c-d_', $name);
        $this->assertStringNotContainsString('-e_', $name);
    }

    public function test_extension_is_lowercase(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', [], 'PDF');

        $this->assertStringEndsWith('.pdf', $name);
    }

    public function test_empty_extension_defaults_to_pdf(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', [], '');

        $this->assertStringEndsWith('.pdf', $name);
    }

    public function test_tag_special_chars_stripped(): void
    {
        $name = $this->service->buildStandardFileName('STD', 'CAT', ['tag/one', 'tag two!'], 'pdf');

        $this->assertStringContainsString('_tagone-tagtwo_', $name);
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
        $id = $this->service->buildDocumentId(1);

        $this->assertStringContainsString('DOC-' . date('Y') . '-', $id);
    }
}
