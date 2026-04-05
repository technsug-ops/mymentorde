<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\SchemaCache;

class DocumentCategoryController extends Controller
{
    public function index()
    {
        $labels = DocumentCategory::topCategoryOptions();
        $hasTopCategory = SchemaCache::hasColumn('document_categories', 'top_category_code');

        $query = DocumentCategory::query();
        if ($hasTopCategory) {
            $query->orderBy('top_category_code');
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(function (DocumentCategory $row) use ($labels, $hasTopCategory): DocumentCategory {
                $topCode = $hasTopCategory
                    ? (string) ($row->top_category_code ?: DocumentCategory::defaultTopCategoryCode())
                    : DocumentCategory::defaultTopCategoryCode();
                $row->setAttribute(
                    'top_category_label',
                    (string) ($labels[$topCode] ?? $labels[DocumentCategory::defaultTopCategoryCode()])
                );
                $row->setAttribute('top_category_code', $topCode);
                return $row;
            })
            ->values();
    }

    public function store(Request $request)
    {
        $rules = [
            'code' => ['required', 'string', 'max:64', 'unique:document_categories,code'],
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
        if (SchemaCache::hasColumn('document_categories', 'top_category_code')) {
            $rules['top_category_code'] = ['nullable', 'string', 'in:'.implode(',', array_keys(DocumentCategory::topCategoryOptions()))];
        }

        $data = $request->validate($rules);

        $data['code'] = SystematicInput::codeUpper((string) $data['code'], 'code');
        if (SchemaCache::hasColumn('document_categories', 'top_category_code')) {
            $data['top_category_code'] = (string) ($data['top_category_code'] ?? DocumentCategory::defaultTopCategoryCode());
        } else {
            unset($data['top_category_code']);
        }

        $row = DocumentCategory::create($data);
        return response()->json($row, Response::HTTP_CREATED);
    }
}
