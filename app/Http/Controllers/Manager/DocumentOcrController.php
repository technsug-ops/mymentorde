<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractDocumentDataJob;
use App\Models\Document;
use App\Services\DocumentOcrSchemas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager belge OCR endpoint'leri.
 *
 * - POST /manager/documents/{id}/re-extract  → job kuyruğa at (yeniden çıkar)
 * - GET  /manager/documents/{id}/extraction  → mevcut extracted_data JSON döner
 *
 * Manager UI inline panel'i bu endpoint'leri çağırır:
 *   1. Sayfa açılışında GET ile mevcut data ve confidence çekilir
 *   2. "Belgeden Tekrar Çıkar" butonunda POST → job kuyruğa, polling devam eder
 */
class DocumentOcrController extends Controller
{
    public function __construct(private readonly DocumentOcrSchemas $schemas)
    {
    }

    /**
     * Yeniden çıkar — job kuyruğa atılır, sync değil.
     */
    public function reExtract(Request $request, int $id): JsonResponse
    {
        $document = Document::query()->with('category')->find($id);
        if (!$document) {
            return response()->json(['ok' => false, 'error' => 'document_not_found'], 404);
        }

        $categoryCode = (string) ($document->category->code ?? '');
        if (!$this->schemas->isSupported($categoryCode)) {
            return response()->json([
                'ok' => false,
                'error' => 'category_not_supported',
                'category_code' => $categoryCode,
            ], 422);
        }

        // Pending'e çek — UI loader gösterebilir
        $document->forceFill([
            'extraction_status' => 'pending',
            'extraction_error'  => null,
        ])->save();

        ExtractDocumentDataJob::dispatch($document->id);

        return response()->json([
            'ok'                => true,
            'document_id'       => $document->id,
            'extraction_status' => 'pending',
            'message'           => 'OCR yeniden başlatıldı, sonuç birkaç saniye içinde hazır olacak.',
        ]);
    }

    /**
     * Mevcut extraction durumu + data JSON.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $document = Document::query()->with('category')->find($id);
        if (!$document) {
            return response()->json(['ok' => false, 'error' => 'document_not_found'], 404);
        }

        $categoryCode = (string) ($document->category->code ?? '');
        $schema = $this->schemas->getSchemaForCategory($categoryCode);

        return response()->json([
            'ok'                    => true,
            'document_id'           => $document->id,
            'category_code'         => $categoryCode,
            'category_label'        => $schema['category_label'] ?? null,
            'schema_supported'      => $schema !== null,
            'schema_fields'         => $schema['fields'] ?? [],
            'extraction_status'     => $document->extraction_status,
            'extraction_confidence' => $document->extraction_confidence,
            'extraction_error'      => $document->extraction_error,
            'extracted_at'          => $document->extracted_at?->toIso8601String(),
            'extracted_data'        => $document->extracted_data ?? null,
        ]);
    }
}
