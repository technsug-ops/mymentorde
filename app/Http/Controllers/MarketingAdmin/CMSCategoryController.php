<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\CmsCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CMSCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = CmsCategory::query()->orderBy('sort_order')->orderBy('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('name_tr', 'like', "%{$q}%");
            });
        }
        $rows = $query->paginate(20)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? CmsCategory::query()->find($editId) : null;

        return view('marketing-admin.content.categories', [
            'pageTitle' => 'CMS Kategorileri',
            'title' => 'Kategori Listesi',
            'rows' => $rows,
            'editing' => $editing,
            'q' => $q,
            'parentOptions' => CmsCategory::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'code', 'name_tr']),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/categories');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $row = CmsCategory::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name_tr' => $data['name_tr'],
            'name_de' => $data['name_de'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'description_tr' => $data['description_tr'] ?? null,
            'parent_category_id' => $data['parent_category_id'] ?? null,
            'icon_url' => $data['icon_url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Kategori eklendi.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/categories?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/categories?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = CmsCategory::query()->findOrFail($id);
        $data = $this->validatePayload($request, false, $row->id);

        $payload = array_filter([
            'code' => isset($data['code']) ? strtoupper((string) $data['code']) : null,
            'name_tr' => $data['name_tr'] ?? null,
            'name_de' => $data['name_de'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'description_tr' => $data['description_tr'] ?? null,
            'parent_category_id' => $data['parent_category_id'] ?? null,
            'icon_url' => $data['icon_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn ($v) => $v !== null);

        if ($request->has('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }
        if ($payload !== []) {
            $row->update($payload);
        }

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Kategori guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = CmsCategory::query()->findOrFail($id);
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Kategori silindi.');
    }

    private function validatePayload(Request $request, bool $isCreate, ?int $currentId = null): array
    {
        return $request->validate([
            'code' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:64',
                Rule::unique('cms_categories', 'code')->ignore($currentId),
            ],
            'name_tr' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:190'],
            'name_de' => ['nullable', 'string', 'max:190'],
            'name_en' => ['nullable', 'string', 'max:190'],
            'description_tr' => ['nullable', 'string'],
            'parent_category_id' => ['nullable', 'integer', 'exists:cms_categories,id'],
            'icon_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable'],
        ]);
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/categories')->with('status', $statusMessage);
    }
}
