<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\AiLabs\DocumentExtractor;
use App\Services\Analytics\AnalyticsService;
use App\Services\DocumentOcrSchemas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Yüklenen belgeden Gemini Vision ile structured data çıkarımı.
 *
 * Tetiklenme:
 *   - Public link ile yükleme (PublicDocumentUploadController::store)
 *   - Manager "Belgeden Tekrar Çıkar" butonu (re-extract endpoint)
 *
 * Akış:
 *   1. Document'i yükle, kategorisinin schema'sını al
 *   2. extraction_status = processing → konkürren tetiklemelerde çakışmasın
 *   3. DocumentExtractor::extractWithVision çağır
 *   4. Sonuç completed | failed olarak kaydet, PostHog'a event gönder
 */
class ExtractDocumentDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(
        DocumentExtractor $extractor,
        DocumentOcrSchemas $schemas,
        AnalyticsService $analytics,
    ): void {
        $document = Document::query()->with('category')->find($this->documentId);

        if (!$document) {
            Log::info('ExtractDocumentDataJob: document not found, skipping', [
                'document_id' => $this->documentId,
            ]);
            return;
        }

        $categoryCode = (string) ($document->category->code ?? '');
        $schema = $schemas->getSchemaForCategory($categoryCode);

        if ($schema === null) {
            Log::info('ExtractDocumentDataJob: category not supported, skipping', [
                'document_id'    => $document->id,
                'category_code'  => $categoryCode,
            ]);
            $document->forceFill([
                'extraction_status' => null, // skip (not pending, not failed)
            ])->save();
            return;
        }

        // İlerleme durumunu işaretle — kullanıcı UI'da loader görsün
        $document->forceFill([
            'extraction_status' => 'processing',
            'extraction_error'  => null,
        ])->save();

        $companyId = $document->company_id ?? null;

        try {
            $result = $extractor->extractWithVision(
                storagePath: (string) $document->storage_path,
                schema: $schema,
                companyId: $companyId ? (int) $companyId : null,
            );

            if (!($result['ok'] ?? false)) {
                $err = (string) ($result['error'] ?? 'unknown_error');
                $document->forceFill([
                    'extraction_status' => 'failed',
                    'extraction_error'  => mb_substr($err, 0, 1000),
                    'extracted_at'      => now(),
                ])->save();

                $analytics->capture('document_ocr_failed', [
                    'document_id'   => $document->id,
                    'category_code' => $categoryCode,
                    'error'         => mb_substr($err, 0, 200),
                ]);

                Log::warning('ExtractDocumentDataJob: extraction failed', [
                    'document_id'   => $document->id,
                    'category_code' => $categoryCode,
                    'error'         => $err,
                ]);
                return;
            }

            $document->forceFill([
                'extracted_data'        => (array) ($result['data'] ?? []),
                'extraction_confidence' => (float) ($result['confidence'] ?? 0),
                'extracted_at'          => now(),
                'extraction_status'     => 'completed',
                'extraction_error'      => null,
            ])->save();

            $analytics->capture('document_ocr_extracted', [
                'document_id'   => $document->id,
                'category_code' => $categoryCode,
                'confidence'    => (float) ($result['confidence'] ?? 0),
                'fields_count'  => count((array) ($result['data'] ?? [])),
                'model'         => (string) ($result['model'] ?? ''),
                'tokens_input'  => (int) ($result['tokens_input']  ?? 0),
                'tokens_output' => (int) ($result['tokens_output'] ?? 0),
            ]);

            Log::info('ExtractDocumentDataJob: extracted', [
                'document_id'   => $document->id,
                'category_code' => $categoryCode,
                'confidence'    => $result['confidence'] ?? null,
            ]);
        } catch (Throwable $e) {
            $document->forceFill([
                'extraction_status' => 'failed',
                'extraction_error'  => mb_substr('exception: ' . $e->getMessage(), 0, 1000),
                'extracted_at'      => now(),
            ])->save();

            $analytics->capture('document_ocr_failed', [
                'document_id'   => $document->id,
                'category_code' => $categoryCode,
                'error'         => mb_substr($e->getMessage(), 0, 200),
            ]);

            Log::error('ExtractDocumentDataJob: unhandled exception', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e; // retry için
        }
    }

    /**
     * Tüm tries başarısız oldu — status'u kalıcı failed yap.
     */
    public function failed(Throwable $exception): void
    {
        $document = Document::query()->find($this->documentId);
        if (!$document) return;

        $document->forceFill([
            'extraction_status' => 'failed',
            'extraction_error'  => mb_substr('job_failed: ' . $exception->getMessage(), 0, 1000),
            'extracted_at'      => now(),
        ])->save();
    }
}
