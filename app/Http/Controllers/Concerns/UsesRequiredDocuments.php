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

        return $rows->map(function (GuestRequiredDocument $row) use ($uploadedCategoryCodes, $topByCategory): array {
            $categoryCode = (string) ($row->category_code ?? '');
            return [
                'document_code'     => (string) ($row->document_code ?? ''),
                'category_code'     => $categoryCode,
                'top_category_code' => (string) ($topByCategory[$categoryCode] ?? DocumentCategory::defaultTopCategoryCode()),
                'name'              => (string) ($row->name ?? ''),
                'description'       => (string) ($row->description ?? ''),
                'is_required'       => (bool) $row->is_required,
                'accepted'          => (string) ($row->accepted ?? 'pdf,jpg,png'),
                'max_mb'            => (int) ($row->max_mb ?? 10),
                'uploaded'          => in_array($categoryCode, $uploadedCategoryCodes, true),
            ];
        })->values()->all();
    }
}
