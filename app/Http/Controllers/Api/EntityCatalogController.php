<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EntityCatalogService;
use Illuminate\Http\Request;

class EntityCatalogController extends Controller
{
    public function __construct(private readonly EntityCatalogService $catalog)
    {
    }

    public function index(Request $request)
    {
        $limit = max(50, min(1000, (int) $request->query('limit', 300)));
        return response()->json($this->catalog->snapshot($limit));
    }

    public function suggest(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:document,field,id,code'],
            'query' => ['nullable', 'string', 'max:190'],
            'form' => ['nullable', 'string', 'max:120'],
            'entity' => ['nullable', 'string', 'max:64'],
            'sub_type' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'document_code' => ['nullable', 'string', 'max:64'],
            'category_code' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json($this->catalog->suggest((string) $data['kind'], $data));
    }
}

