<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DocumentCategory;
use App\Models\GuestRequiredDocument;
use App\Support\SchemaCache;

trait UsesRequiredDocuments
{
    /**
     * DB-first, config fallback.
     * $stage: 'guest' | 'student'
     *
     * @param  array<int,string>  $uploadedCategoryCodes
     * @return array<int,array<string,mixed>>
     */
    private function requiredDocumentsByApplicationType(
        string $applicationType,
        array  $uploadedCategoryCodes,
        string $stage = 'guest'
    ): array {
        $type = strtolower(trim($applicationType));
        if ($type === '') {
            $type = 'bachelor';
        }

        $rows = GuestRequiredDocument::query()
            ->where('is_active', true)
            ->where('application_type', $type)
            ->where('stage', $stage)
            ->orderBy('sort_order')
            ->orderBy('document_code')
            ->get();

        // Config fallback when DB has no rows
        if ($rows->isEmpty()) {
            $fallback = config("required_documents.{$stage}.{$type}", []);
            if (!is_array($fallback) || empty($fallback)) {
                $fallback = config("required_documents.{$stage}._default", []);
            }
            return collect($fallback)
                ->map(function (array $item) use ($uploadedCategoryCodes): array {
                    $item['uploaded'] = in_array((string) ($item['category_code'] ?? ''), $uploadedCategoryCodes, true);
                    return $item;
                })
                ->values()
                ->all();
        }

        $topByCategory = collect();
        if (SchemaCache::hasColumn('document_categories', 'top_category_code')) {
            $topByCategory = DocumentCategory::query()
                ->whereIn('code', $rows->pluck('category_code')->filter()->unique()->values())
                ->get(['code', 'top_category_code'])
                ->pluck('top_category_code', 'code');
        }

        $hasNameDe = SchemaCache::hasColumn('guest_required_documents', 'name_de');
        $hasUniCat = SchemaCache::hasColumn('guest_required_documents', 'uni_assist_category');
        $hasTopCat = SchemaCache::hasColumn('guest_required_documents', 'top_category_code');

        return $rows->map(function (GuestRequiredDocument $row) use ($uploadedCategoryCodes, $topByCategory, $hasNameDe, $hasUniCat, $hasTopCat): array {
            $categoryCode = (string) ($row->category_code ?? '');
            // Row'un kendi top_category_code override'i öncelikli — aynı belge farklı
            // sekmelerde farklı sıralarda görünebilsin (Pasaport: uni_assist+vize+yurt).
            // Yoksa kategori lookup'tan al, son çare default.
            $rowTop = $hasTopCat ? (string) ($row->top_category_code ?? '') : '';
            $catTop = (string) ($topByCategory[$categoryCode] ?? '');
            $topCategory = DocumentCategory::normalizeTopCategoryCode(
                $rowTop !== '' ? $rowTop : $catTop
            );
            return [
                'document_code'       => (string) ($row->document_code ?? ''),
                'category_code'       => $categoryCode,
                'top_category_code'   => $topCategory,
                'name'                => (string) ($row->name ?? ''),
                'name_de'             => $hasNameDe ? (string) ($row->name_de ?? '') : '',
                'uni_assist_category' => $hasUniCat ? (string) ($row->uni_assist_category ?? '') : '',
                'description'         => (string) ($row->description ?? ''),
                'is_required'         => (bool) $row->is_required,
                'accepted'            => (string) ($row->accepted ?? 'pdf,jpg,png'),
                'max_mb'              => (int) ($row->max_mb ?? 10),
                'uploaded'            => in_array($categoryCode, $uploadedCategoryCodes, true),
            ];
        })->values()->all();
    }
}
