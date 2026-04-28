<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use App\Models\GuestRequiredDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerRequiredDocumentController extends Controller
{
    private const APPLICATION_TYPES = [
        'bachelor'   => 'Bachelor (Lisans)',
        'master'     => 'Master',
        'ausbildung' => 'Ausbildung (Mesleki Eğitim)',
        'dil_kursu'  => 'Dil Kursu',
    ];

    private const STAGES = [
        'guest'   => 'Aday Öğrenci (Guest)',
        'student' => 'Öğrenci (Student)',
    ];

    public function index(Request $request)
    {
        $appType = $request->string('application_type', 'bachelor')->toString();
        $stage   = $request->string('stage', 'student')->toString();

        $rows = GuestRequiredDocument::query()
            ->where('application_type', $appType)
            ->where('stage', $stage)
            ->where('is_active', true)
            ->orderBy('top_category_code')
            ->orderBy('sort_order')
            ->get();

        // category_code → top_category_codes (multi-tag aggregation)
        $byCode = $rows->groupBy('category_code');

        // Bir document_code'un kaç top kategoride aktif olduğu (multi-tag özet)
        $multiTagCount = $rows->groupBy('document_code')
            ->map(fn($g) => $g->pluck('top_category_code')->unique()->count());

        return view('manager.required-documents.index', [
            'rows'             => $rows,
            'byCode'           => $byCode,
            'multiTagCount'    => $multiTagCount,
            'topCategories'    => DocumentCategory::TOP_CATEGORIES,
            'applicationTypes' => self::APPLICATION_TYPES,
            'stages'           => self::STAGES,
            'currentAppType'   => $appType,
            'currentStage'     => $stage,
        ]);
    }

    public function create(Request $request)
    {
        return view('manager.required-documents.form', [
            'doc'              => null,
            'topCategories'    => DocumentCategory::TOP_CATEGORIES,
            'applicationTypes' => self::APPLICATION_TYPES,
            'stages'           => self::STAGES,
            'preselectedTags'  => [],
            'currentAppType'   => $request->string('application_type', 'bachelor')->toString(),
            'currentStage'     => $request->string('stage', 'student')->toString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRow($request);
        $tags = (array) $request->input('top_category_codes', []);
        $tags = array_values(array_intersect($tags, array_keys(DocumentCategory::TOP_CATEGORIES)));
        if (empty($tags)) {
            return back()->withInput()->withErrors(['top_category_codes' => 'En az bir kategori seçin.']);
        }

        // Aynı document_code varsa otomatik kategori_code = document_code (uniform)
        $categoryCode = $data['category_code'] ?: $data['document_code'];

        // document_categories master kaydı yoksa oluştur (tek isim/altyapı için)
        $this->ensureCategoryRow($data['document_code'], $data, $tags[0]);

        DB::transaction(function () use ($data, $tags, $categoryCode) {
            foreach ($tags as $i => $tag) {
                GuestRequiredDocument::create([
                    'company_id'          => 1,
                    'application_type'    => $data['application_type'],
                    'stage'               => $data['stage'],
                    'top_category_code'   => $tag,
                    'document_code'       => $data['document_code'],
                    'category_code'       => $categoryCode,
                    'name'                => $data['name'],
                    'name_de'             => $data['name_de'] ?? '',
                    'uni_assist_category' => $data['uni_assist_category'] ?? '',
                    'description'         => $data['description'] ?? '',
                    'is_required'         => $data['is_required'] ?? true,
                    'accepted'            => $data['accepted'] ?: 'pdf,jpg,png',
                    'max_mb'              => (int) ($data['max_mb'] ?? 10),
                    'sort_order'          => (int) ($data['sort_order'] ?? 100) + $i,
                    'is_active'           => true,
                ]);
            }
        });

        return redirect()
            ->route('manager.required-documents.index', [
                'application_type' => $data['application_type'],
                'stage'            => $data['stage'],
            ])
            ->with('flash_success', 'Belge eklendi: ' . $data['name'] . ' (' . count($tags) . ' kategori)');
    }

    public function edit(GuestRequiredDocument $document)
    {
        // Aynı document_code'a ait tüm satırları bul (multi-tag)
        $siblings = GuestRequiredDocument::query()
            ->where('application_type', $document->application_type)
            ->where('stage', $document->stage)
            ->where('document_code', $document->document_code)
            ->where('is_active', true)
            ->get();

        $tags = $siblings->pluck('top_category_code')->unique()->values()->all();

        return view('manager.required-documents.form', [
            'doc'              => $document,
            'siblings'         => $siblings,
            'preselectedTags'  => $tags,
            'topCategories'    => DocumentCategory::TOP_CATEGORIES,
            'applicationTypes' => self::APPLICATION_TYPES,
            'stages'           => self::STAGES,
            'currentAppType'   => $document->application_type,
            'currentStage'     => $document->stage,
        ]);
    }

    public function update(Request $request, GuestRequiredDocument $document)
    {
        $data = $this->validateRow($request);
        $tags = (array) $request->input('top_category_codes', []);
        $tags = array_values(array_intersect($tags, array_keys(DocumentCategory::TOP_CATEGORIES)));
        if (empty($tags)) {
            return back()->withInput()->withErrors(['top_category_codes' => 'En az bir kategori seçin.']);
        }

        $categoryCode = $data['category_code'] ?: $data['document_code'];

        $this->ensureCategoryRow($data['document_code'], $data, $tags[0]);

        DB::transaction(function () use ($document, $data, $tags, $categoryCode) {
            // Eski multi-tag satırları soft-delete (is_active=false)
            GuestRequiredDocument::query()
                ->where('application_type', $document->application_type)
                ->where('stage', $document->stage)
                ->where('document_code', $document->document_code)
                ->update(['is_active' => false, 'updated_at' => now()]);

            // Yeni tag'lerle yeniden insert et
            foreach ($tags as $i => $tag) {
                GuestRequiredDocument::create([
                    'company_id'          => 1,
                    'application_type'    => $data['application_type'],
                    'stage'               => $data['stage'],
                    'top_category_code'   => $tag,
                    'document_code'       => $data['document_code'],
                    'category_code'       => $categoryCode,
                    'name'                => $data['name'],
                    'name_de'             => $data['name_de'] ?? '',
                    'uni_assist_category' => $data['uni_assist_category'] ?? '',
                    'description'         => $data['description'] ?? '',
                    'is_required'         => $data['is_required'] ?? true,
                    'accepted'            => $data['accepted'] ?: 'pdf,jpg,png',
                    'max_mb'              => (int) ($data['max_mb'] ?? 10),
                    'sort_order'          => (int) ($data['sort_order'] ?? 100) + $i,
                    'is_active'           => true,
                ]);
            }
        });

        return redirect()
            ->route('manager.required-documents.index', [
                'application_type' => $data['application_type'],
                'stage'            => $data['stage'],
            ])
            ->with('flash_success', 'Belge güncellendi: ' . $data['name']);
    }

    public function destroy(GuestRequiredDocument $document)
    {
        $name = $document->name;
        $appType = $document->application_type;
        $stage = $document->stage;

        // Aynı document_code'lu tüm satırları soft-delete
        GuestRequiredDocument::query()
            ->where('application_type', $document->application_type)
            ->where('stage', $document->stage)
            ->where('document_code', $document->document_code)
            ->update(['is_active' => false, 'updated_at' => now()]);

        return redirect()
            ->route('manager.required-documents.index', ['application_type' => $appType, 'stage' => $stage])
            ->with('flash_success', 'Belge kaldırıldı: ' . $name);
    }

    /**
     * @return array<string,mixed>
     */
    private function validateRow(Request $request): array
    {
        return $request->validate([
            'application_type'     => 'required|string|in:' . implode(',', array_keys(self::APPLICATION_TYPES)),
            'stage'                => 'required|string|in:' . implode(',', array_keys(self::STAGES)),
            'document_code'        => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'category_code'        => 'nullable|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'name'                 => 'required|string|max:190',
            'name_de'              => 'nullable|string|max:190',
            'uni_assist_category'  => 'nullable|string|max:80',
            'description'          => 'nullable|string|max:500',
            'is_required'          => 'boolean',
            'accepted'             => 'nullable|string|max:120',
            'max_mb'               => 'nullable|integer|min:1|max:50',
            'sort_order'           => 'nullable|integer|min:0|max:9999',
        ], [
            'document_code.regex' => 'Belge kodu sadece harf, rakam, _ ve - içerebilir.',
            'category_code.regex' => 'Kategori kodu sadece harf, rakam, _ ve - içerebilir.',
        ]);
    }

    /**
     * Belge için document_categories master kaydı yoksa oluştur.
     */
    private function ensureCategoryRow(string $code, array $data, string $primaryTag): void
    {
        if (DocumentCategory::query()->where('code', $code)->exists()) {
            return;
        }
        DocumentCategory::create([
            'code'              => $code,
            'name_tr'           => $data['name'],
            'name_de'           => $data['name_de'] ?? $data['name'],
            'name_en'           => $data['name'],
            'top_category_code' => $primaryTag,
            'is_active'         => true,
            'sort_order'        => (int) ($data['sort_order'] ?? 100),
        ]);
    }
}
