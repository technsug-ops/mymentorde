<?php

namespace App\Services;

use App\Models\DealerType;
use App\Models\DocumentCategory;
use App\Models\FieldRule;
use App\Models\ProcessDefinition;
use App\Models\StudentType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EntityCatalogService
{
    public function snapshot(int $limit = 300): array
    {
        $cfg = config('entity_catalog', []);

        return [
            'version' => (string) ($cfg['version'] ?? 'v1'),
            'document_templates' => $this->documentTemplates(),
            'field_catalog' => $this->fieldCatalog($limit),
            'id_prefixes' => (array) ($cfg['id_prefix_by_entity'] ?? []),
            'student_types' => StudentType::query()
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->limit($limit)
                ->get(['code', 'id_prefix', 'name_tr']),
            'dealer_types' => DealerType::query()
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->limit($limit)
                ->get(['code', 'name_tr']),
            'document_categories' => DocumentCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->limit($limit)
                ->get(['code', 'name_tr']),
            'process_definitions' => ProcessDefinition::query()
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->limit($limit)
                ->get(['external_id', 'code', 'name_tr']),
        ];
    }

    public function suggest(string $kind, array $payload): array
    {
        return match (strtolower(trim($kind))) {
            'document' => $this->suggestDocument($payload),
            'field' => $this->suggestField($payload),
            'id', 'code' => $this->suggestCode($payload),
            default => [
                'kind' => $kind,
                'result' => null,
                'message' => 'Desteklenmeyen suggestion kind.',
            ],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function documentTemplates(): array
    {
        return collect((array) config('entity_catalog.document_templates', []))
            ->map(function ($row): array {
                return [
                    'document_code' => strtoupper(trim((string) Arr::get($row, 'document_code', ''))),
                    'category_code' => strtoupper(trim((string) Arr::get($row, 'category_code', ''))),
                    'name' => trim((string) Arr::get($row, 'name', '')),
                    'keywords' => collect((array) Arr::get($row, 'keywords', []))
                        ->map(fn ($v) => strtolower(trim((string) $v)))
                        ->filter()
                        ->values()
                        ->all(),
                    'default_required' => (bool) Arr::get($row, 'default_required', true),
                    'accepted' => trim((string) Arr::get($row, 'accepted', 'pdf,jpg,png')),
                    'max_mb' => (int) Arr::get($row, 'max_mb', 10),
                ];
            })
            ->filter(fn ($row) => $row['document_code'] !== '' && $row['category_code'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function fieldCatalog(int $limit = 300): array
    {
        $cfgFields = collect((array) config('entity_catalog.field_catalog', []))
            ->map(fn ($items) => collect((array) $items)->map(fn ($v) => trim((string) $v))->filter()->values()->all())
            ->all();

        $dbFields = FieldRule::query()
            ->whereNotNull('target_field')
            ->where('target_field', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('target_field')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existing = collect($cfgFields['student_registration'] ?? []);
        $cfgFields['student_registration'] = $existing->merge($dbFields)->unique()->values()->all();

        return $cfgFields;
    }

    private function suggestDocument(array $payload): array
    {
        $query = strtolower(trim((string) ($payload['query'] ?? '')));
        $queryTokens = $this->tokenize($query);

        $templates = collect($this->documentTemplates())
            ->map(function (array $row) use ($queryTokens): array {
                $keywords = collect((array) ($row['keywords'] ?? []))
                    ->map(fn ($v) => strtolower(trim((string) $v)))
                    ->filter()
                    ->values()
                    ->all();
                $score = 0;
                foreach ($keywords as $kw) {
                    if ($kw === '') {
                        continue;
                    }
                    if (str_contains($query, $kw)) {
                        $score += 3;
                    }
                    foreach ($queryTokens as $token) {
                        if ($token !== '' && str_contains($kw, $token)) {
                            $score += 1;
                        }
                    }
                }
                $row['score'] = $score;
                return $row;
            })
            ->sortByDesc('score')
            ->values();

        $best = $templates->first();
        if (!$best || (int) ($best['score'] ?? 0) <= 0) {
            $best = [
                'document_code' => $this->normalizeDocCode((string) ($payload['document_code'] ?? 'DOC-NEW')),
                'category_code' => $this->normalizeDocCode((string) ($payload['category_code'] ?? 'DOC-NEW')),
                'name' => trim((string) ($payload['query'] ?? 'Yeni Belge')),
                'default_required' => true,
                'accepted' => 'pdf,jpg,png',
                'max_mb' => 10,
                'score' => 0,
            ];
        }

        return [
            'kind' => 'document',
            'result' => [
                'document_code' => $best['document_code'],
                'category_code' => $best['category_code'],
                'name' => $best['name'],
                'is_required' => (bool) ($best['default_required'] ?? true),
                'accepted' => (string) ($best['accepted'] ?? 'pdf,jpg,png'),
                'max_mb' => (int) ($best['max_mb'] ?? 10),
                'confidence' => (int) ($best['score'] ?? 0),
            ],
            'alternatives' => $templates->take(5)->map(fn ($row) => [
                'document_code' => $row['document_code'],
                'category_code' => $row['category_code'],
                'name' => $row['name'],
                'score' => (int) ($row['score'] ?? 0),
            ])->values()->all(),
        ];
    }

    private function suggestField(array $payload): array
    {
        $form = trim((string) ($payload['form'] ?? 'student_registration'));
        $query = strtolower(trim((string) ($payload['query'] ?? '')));
        $tokens = $this->tokenize($query);
        $fieldCatalog = $this->fieldCatalog();
        $candidateFields = collect((array) ($fieldCatalog[$form] ?? $fieldCatalog['student_registration'] ?? []));

        $ranked = $candidateFields->map(function (string $field) use ($tokens, $query): array {
            $score = 0;
            $fieldLower = strtolower($field);
            foreach ($tokens as $t) {
                if ($t !== '' && str_contains($fieldLower, $t)) {
                    $score += 2;
                }
            }
            if ($query !== '' && str_contains($fieldLower, $query)) {
                $score += 3;
            }
            return ['field' => $field, 'score' => $score];
        })->sortByDesc('score')->values();

        return [
            'kind' => 'field',
            'result' => $ranked->first()['field'] ?? null,
            'alternatives' => $ranked->take(8)->values()->all(),
            'form' => $form,
        ];
    }

    private function suggestCode(array $payload): array
    {
        $entity = strtolower(trim((string) ($payload['entity'] ?? 'student')));
        $subType = strtolower(trim((string) ($payload['sub_type'] ?? '')));
        $year = (int) ($payload['year'] ?? now()->year);
        $month = (int) ($payload['month'] ?? now()->month);
        $sequence = max(1, (int) ($payload['sequence'] ?? 1));

        $prefix = $this->resolvePrefix($entity, $subType);
        $yy = str_pad((string) ($year % 100), 2, '0', STR_PAD_LEFT);
        $mm = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $seq = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        return [
            'kind' => 'code',
            'result' => [
                'entity' => $entity,
                'sub_type' => $subType !== '' ? $subType : null,
                'prefix' => $prefix,
                'suggested_code' => sprintf('%s%s%s%s', $prefix, $yy, $mm, $seq),
                'pattern' => 'PREFIX + YY + MM + SEQ4',
            ],
        ];
    }

    private function resolvePrefix(string $entity, string $subType = ''): string
    {
        $map = (array) config('entity_catalog.id_prefix_by_entity', []);
        $entityMap = (array) ($map[$entity] ?? []);
        if ($subType !== '' && isset($entityMap[$subType])) {
            return strtoupper((string) $entityMap[$subType]);
        }
        return strtoupper((string) ($entityMap['default'] ?? strtoupper(substr($entity, 0, 3))));
    }

    /**
     * @return array<int,string>
     */
    private function tokenize(string $value): array
    {
        return collect(preg_split('/[^a-z0-9_]+/i', strtolower($value)) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && strlen($v) >= 2)
            ->values()
            ->all();
    }

    private function normalizeDocCode(string $value): string
    {
        $clean = strtoupper(trim($value));
        $clean = preg_replace('/[^A-Z0-9_]/', '_', $clean) ?: 'DOC_NEW';
        if (!Str::startsWith($clean, 'DOC')) {
            $clean = 'DOC_' . $clean;
        }
        return substr($clean, 0, 16);
    }
}

