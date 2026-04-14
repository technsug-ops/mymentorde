<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset;
use App\Models\DigitalAssetFolder;
use App\Models\User;
use App\Rules\ValidFileMagicBytes;
use App\Services\DigitalAsset\DigitalAssetFolderService;
use App\Services\DigitalAsset\DigitalAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DigitalAssetController extends Controller
{
    public function __construct(
        private readonly DigitalAssetService $assets,
        private readonly DigitalAssetFolderService $folders,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->renderFolder(null, $request);
    }

    public function folderShow(Request $request, int $folder): View
    {
        return $this->renderFolder($folder, $request);
    }

    /**
     * Yıldızlı varlıklar — kullanıcının kendi listesi.
     */
    public function favorites(Request $request): View
    {
        return $this->renderFolder(null, $request, onlyFavorites: true);
    }

    /**
     * Yıldız toggle — AJAX POST. JSON döner.
     */
    public function toggleFavorite(int $asset): \Illuminate\Http\JsonResponse
    {
        $user  = $this->user();
        $model = DigitalAsset::query()->findOrFail($asset);

        // Rol bazlı klasör kısıtı — kilidi olan klasörün dosyasını yıldızlayamasın
        if ($model->folder_id) {
            $folder = DigitalAssetFolder::query()->find($model->folder_id);
            if ($folder && !$folder->isAccessibleByRole((string) $user->role)) {
                abort(403, 'Bu varlığa erişiminiz yok.');
            }
        }

        $existing = $user->favoriteAssets()->where('asset_id', $model->id)->exists();
        if ($existing) {
            $user->favoriteAssets()->detach($model->id);
            $favorited = false;
        } else {
            $user->favoriteAssets()->attach($model->id);
            $favorited = true;
        }

        return response()->json([
            'favorited' => $favorited,
            'count'     => $user->favoriteAssets()->count(),
        ]);
    }

    private function renderFolder(?int $folderId, Request $request, bool $onlyFavorites = false): View
    {
        $user        = $this->user();
        $portal      = $this->portalKey($user);
        $layout      = $this->layoutFor($portal);
        $readOnly    = in_array($portal, ['dealer'], true);
        $userRole    = (string) $user->role;

        $tree        = $this->folders->treeForRole($userRole);
        $current     = $folderId ? DigitalAssetFolder::query()->findOrFail($folderId) : null;
        if ($current && !$current->isAccessibleByRole($userRole)) {
            abort(403, 'Bu klasöre erişiminiz yok.');
        }
        $breadcrumb  = $current ? $current->breadcrumb() : [];

        // Görünüm modu: ?view=grid|list — varsayılan grid
        $viewMode = in_array($request->query('view'), ['grid', 'list'], true)
            ? $request->query('view')
            : 'grid';

        // Filtreler (tag, search q, category, date range, uploader, size range) — E6 gelişmiş arama
        // size_min_mb/size_max_mb UI'dan MB olarak gelir, bytes'a çevrilir
        $sizeMinMb = (float) $request->query('size_min_mb', 0);
        $sizeMaxMb = (float) $request->query('size_max_mb', 0);
        $sizeMinBytes = $sizeMinMb > 0 ? (int) ($sizeMinMb * 1024 * 1024) : 0;
        $sizeMaxBytes = $sizeMaxMb > 0 ? (int) ($sizeMaxMb * 1024 * 1024) : 0;

        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'tag'      => trim((string) $request->query('tag', '')),
            'category' => in_array($request->query('category'), ['image','video','audio','document','archive','other'], true)
                            ? $request->query('category') : '',
            'from'     => $request->query('from', ''),
            'to'       => $request->query('to', ''),
            'uploader' => (int) $request->query('uploader', 0) ?: '',
            'size_min' => $sizeMinBytes ?: '',
            'size_max' => $sizeMaxBytes ?: '',
        ];
        $hasFilters = array_filter($filters) !== [];

        // Kullanıcının erişebileceği klasör ID'leri (role filtresi)
        $accessibleFolderIds = DigitalAssetFolder::query()
            ->get()
            ->filter(fn ($f) => $f->isAccessibleByRole($userRole))
            ->pluck('id')
            ->all();

        $query = DigitalAsset::query()
            ->with('creator:id,name,email');

        if ($onlyFavorites) {
            // Sadece bu kullanıcının yıldızladığı varlıklar — klasör filtresi uygulanmaz
            $query->whereIn('id', $user->favoriteAssets()->pluck('digital_assets.id'));
        } elseif (!$hasFilters) {
            // Filtre aktif değilse sadece mevcut klasörü göster; aktifse tüm varlıklarda ara
            $query->inFolder($folderId);
        }

        // Her durumda: kullanıcının görmeye yetkili olmadığı klasörlerdeki dosyalar gizlenir.
        // Kök klasördeki (folder_id NULL) dosyalar her zaman görünür.
        $query->where(function ($w) use ($accessibleFolderIds) {
            $w->whereNull('folder_id')->orWhereIn('folder_id', $accessibleFolderIds);
        });

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('original_filename', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhere('doc_code', 'like', "%{$q}%");
            });
        }

        if ($filters['tag'] !== '') {
            // JSON tags kolonunda arama (SQLite + MySQL uyumlu basit like)
            $query->where('tags', 'like', '%"' . $filters['tag'] . '"%');
        }

        if ($filters['category'] !== '') {
            $query->where('category', $filters['category']);
        }

        if ($filters['from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // E6 — Uploader filtresi (yükleyen user id)
        if (!empty($filters['uploader'])) {
            $query->where('created_by', (int) $filters['uploader']);
        }

        // E6 — Boyut filtresi (bytes)
        if (!empty($filters['size_min'])) {
            $query->where('size_bytes', '>=', (int) $filters['size_min']);
        }
        if (!empty($filters['size_max'])) {
            $query->where('size_bytes', '<=', (int) $filters['size_max']);
        }

        // ── Sıralama ───────────────────────────────────────────────────────
        // Desteklenen kolonlar; bilinmeyen değer gelirse created_at'a düşer.
        $allowedSorts = [
            'name'        => 'name',
            'category'    => 'category',
            'doc_code'    => 'doc_code',
            'size_bytes'  => 'size_bytes',
            'created_at'  => 'created_at',
            'creator'     => 'created_by',
        ];
        $sortKey = (string) $request->query('sort', 'created_at');
        $sortCol = $allowedSorts[$sortKey] ?? 'created_at';
        $sortDir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $assets = $query
            ->orderByDesc('is_pinned')
            ->orderBy($sortCol, $sortDir)
            ->paginate($viewMode === 'list' ? 100 : 48)
            ->withQueryString();

        // ── Popüler etiketler (üst 15, company-scoped, 30 dk cache) ─────────
        // Cache invalidation store/update/delete sonunda forgetPopularTagsCache
        // ile manuel tetikleniyor; bu yüzden uzun TTL güvenli.
        $companyId = (int) ($user->company_id ?? 0);
        $popularTags = Cache::remember(
            "dam_popular_tags_c{$companyId}",
            now()->addMinutes(30),
            fn () => $this->computePopularTags()
        );

        // ── Kullanıcının yıldızlı varlıkları + sayı ────────────────────────
        $favoriteIds         = $user->favoriteAssets()->pluck('digital_assets.id')->all();
        $favoriteCount       = count($favoriteIds);

        // E4 — Yıldızlı klasör ID'leri
        $favoriteFolderIds   = $user->favoriteFolders()->pluck('digital_asset_folders.id')->all();

        // E6 — Advanced search için uploader listesi (aynı company'deki dosya yükleyenler)
        $uploaderList = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('id', DigitalAsset::query()->distinct()->pluck('created_by')->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('shared.digital-assets.index', [
            'layout'            => $layout,
            'portal'            => $portal,
            'readOnly'          => $readOnly,
            'tree'              => $tree,
            'currentFolder'     => $current,
            'breadcrumb'        => $breadcrumb,
            'assets'            => $assets,
            'viewMode'          => $viewMode,
            'filters'           => $filters,
            'hasFilters'        => $hasFilters,
            'popularTags'       => $popularTags,
            'favoriteIds'       => $favoriteIds,
            'favoriteCount'     => $favoriteCount,
            'favoriteFolderIds' => $favoriteFolderIds,
            'uploaderList'      => $uploaderList,
            'onlyFavorites'     => $onlyFavorites,
            'sortKey'           => $sortKey,
            'sortDir'           => $sortDir,
            'routePrefix'       => $this->routePrefix($portal),
        ]);
    }

    /**
     * En çok kullanılan 15 tag'i hesapla (şirket bazlı).
     * Tags JSON array olarak saklanıyor — PHP tarafında flatten+count.
     */
    private function computePopularTags(): array
    {
        $rawArrays = DigitalAsset::query()
            ->whereNotNull('tags')
            ->pluck('tags');

        $counts = [];
        foreach ($rawArrays as $tags) {
            if (!is_array($tags)) {
                continue;
            }
            foreach ($tags as $t) {
                $t = trim((string) $t);
                if ($t === '') {
                    continue;
                }
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }
        arsort($counts);
        return array_slice($counts, 0, 15, true);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb     = (int) (config('dam.max_size_bytes', 50 * 1024 * 1024) / 1024);
        $maxFiles  = (int) config('dam.bulk_upload_max_files', 20);
        $allowedMimes = implode(',', (array) config('dam.allowed_mimes', []));

        // Hem tek dosya (`file`) hem çoklu (`files[]`) destekle.
        // Güvenlik: Laravel `mimetypes` kuralı + custom `ValidFileMagicBytes`
        // ikilisi — birincisi browser-reported MIME'ı config whitelist'ine karşı
        // kontrol eder, ikincisi dosyanın gerçek magic byte'larını okuyup
        // tehlikeli içerikleri (php/html/svg/exe) reddeder.
        $fileRules = ['file', "max:{$maxKb}", new ValidFileMagicBytes()];
        if ($allowedMimes !== '') {
            $fileRules[] = "mimetypes:{$allowedMimes}";
        }

        $request->validate([
            'file'        => array_merge(['nullable'], $fileRules),
            'files'       => ['nullable', 'array', "max:{$maxFiles}"],
            'files.*'     => $fileRules,
            'folder_id'   => ['nullable', 'integer', 'exists:digital_asset_folders,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:60'],
        ]);

        $folderId = $request->integer('folder_id') ?: null;

        $meta = [
            'description' => $request->input('description'),
            'tags'        => $request->input('tags'),
        ];

        // Yüklenecek dosya listesi — hem `file` hem `files[]` tek akışta
        $incoming = [];
        if ($request->hasFile('files')) {
            $incoming = array_filter((array) $request->file('files'));
        } elseif ($request->hasFile('file')) {
            $incoming = [$request->file('file')];
        }

        if (empty($incoming)) {
            return back()->withErrors(['file' => 'Yüklenecek dosya seçilmedi.']);
        }

        $success = 0;
        $failed  = [];
        foreach ($incoming as $file) {
            try {
                $this->assets->store($file, $folderId, $this->user(), $meta);
                $success++;
            } catch (\Throwable $e) {
                $failed[] = $file->getClientOriginalName() . ' (' . $e->getMessage() . ')';
            }
        }

        $msg = "{$success} dosya yüklendi.";
        if (!empty($failed)) {
            $msg .= ' ' . count($failed) . ' başarısız: ' . implode('; ', $failed);
        }
        return back()->with('status', $msg);
    }

    public function update(Request $request, int $asset): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:60'],
            'is_pinned'   => ['nullable', 'boolean'],
        ]);

        $model = DigitalAsset::query()->findOrFail($asset);
        $this->assets->update($model, $request->only(['name', 'description', 'tags', 'is_pinned']));

        return back()->with('status', 'Varlık güncellendi.');
    }

    public function destroy(int $asset): RedirectResponse
    {
        $model = DigitalAsset::query()->findOrFail($asset);
        $this->assets->delete($model);
        return back()->with('status', 'Varlık silindi.');
    }

    public function download(int $asset)
    {
        $model = DigitalAsset::query()->findOrFail($asset);
        return $this->assets->download($model, $this->user());
    }

    /**
     * Inline preview — sayaç artırmaz, browser'da göstermek için.
     */
    public function preview(int $asset): \Symfony\Component\HttpFoundation\Response
    {
        $model = DigitalAsset::query()->findOrFail($asset);

        // Erişim kontrolü
        if ($model->folder_id) {
            $folder = DigitalAssetFolder::query()->find($model->folder_id);
            if ($folder && !$folder->isAccessibleByRole((string) $this->user()->role)) {
                abort(403);
            }
        }

        return $this->assets->preview($model);
    }

    /**
     * Harici link asset oluştur (Google Drive, YouTube, Dropbox, vb.)
     */
    public function storeLink(Request $request): RedirectResponse
    {
        $request->validate([
            'external_url' => ['required', 'string', 'url', 'max:1000'],
            'folder_id'    => ['nullable', 'integer', 'exists:digital_asset_folders,id'],
            'name'         => ['nullable', 'string', 'max:200'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'category'     => ['nullable', 'string', 'in:image,video,audio,document,archive,other'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:60'],
        ]);

        $this->assets->storeLink(
            (string) $request->input('external_url'),
            $request->integer('folder_id') ?: null,
            $this->user(),
            [
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'category'    => $request->input('category'),
                'tags'        => $request->input('tags'),
            ]
        );

        return back()->with('status', 'Link eklendi.');
    }

    public function folderStore(Request $request): RedirectResponse
    {
        $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'parent_id'       => ['nullable', 'integer', 'exists:digital_asset_folders,id'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'color'           => ['nullable', 'string', 'max:7'],
            'icon'            => ['nullable', 'string', 'max:50'],
            'allowed_roles'   => ['nullable', 'array'],
            'allowed_roles.*' => ['string', 'max:40'],
        ]);

        $this->folders->createFolder(
            $request->string('name'),
            $request->integer('parent_id') ?: null,
            $this->user(),
            $request->only(['description', 'color', 'icon', 'allowed_roles'])
        );

        return back()->with('status', 'Klasör oluşturuldu.');
    }

    public function folderUpdate(Request $request, int $folder): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:150']]);
        $model = DigitalAssetFolder::query()->findOrFail($folder);
        $this->folders->rename($model, (string) $request->input('name'));
        return back()->with('status', 'Klasör güncellendi.');
    }

    public function folderDestroy(int $folder): RedirectResponse
    {
        $model = DigitalAssetFolder::query()->findOrFail($folder);
        $this->folders->delete($model);
        return back()->with('status', 'Klasör silindi.');
    }

    /**
     * E2 — Klasörü başka parent'a taşı. null = root'a.
     */
    public function folderMove(Request $request, int $folder): RedirectResponse
    {
        $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:digital_asset_folders,id'],
        ]);

        $model = DigitalAssetFolder::query()->findOrFail($folder);

        try {
            $this->folders->move($model, $request->integer('parent_id') ?: null);
            return back()->with('status', 'Klasör taşındı.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['folder' => $e->getMessage()]);
        }
    }

    /**
     * E3 — Dosyayı başka klasöre taşı. null folder_id = root.
     */
    public function assetMove(Request $request, int $asset): RedirectResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:digital_asset_folders,id'],
        ]);

        $model  = DigitalAsset::query()->findOrFail($asset);
        $target = $request->integer('folder_id') ?: null;

        // Hedef klasör rol erişim kontrolü
        if ($target) {
            $targetFolder = DigitalAssetFolder::query()->findOrFail($target);
            if (!$targetFolder->isAccessibleByRole((string) $this->user()->role)) {
                abort(403, 'Hedef klasöre erişiminiz yok.');
            }
        }

        $this->assets->move($model, $target);
        return back()->with('status', 'Dosya taşındı.');
    }

    /**
     * E4 — Klasör yıldızla / yıldızı kaldır (toggle).
     */
    public function folderToggleFavorite(int $folder): \Illuminate\Http\JsonResponse
    {
        $user  = $this->user();
        $model = DigitalAssetFolder::query()->findOrFail($folder);

        if (!$model->isAccessibleByRole((string) $user->role)) {
            abort(403, 'Bu klasöre erişiminiz yok.');
        }

        $exists = $user->favoriteFolders()->where('folder_id', $model->id)->exists();
        if ($exists) {
            $user->favoriteFolders()->detach($model->id);
            $favorited = false;
        } else {
            $user->favoriteFolders()->attach($model->id);
            $favorited = true;
        }

        return response()->json([
            'favorited' => $favorited,
            'count'     => $user->favoriteFolders()->count(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function user(): User
    {
        $u = Auth::user();
        abort_unless($u, 401);
        /** @var User $u */
        return $u;
    }

    private function portalKey(User $user): string
    {
        return match ((string) $user->role) {
            'manager'                                            => 'manager',
            'senior', 'mentor'                                   => 'senior',
            'marketing_admin', 'marketing_staff',
            'sales_admin', 'sales_staff'                         => 'marketing-admin',
            'dealer'                                             => 'dealer',
            default                                              => abort(403, 'DAM erişiminiz yok.'),
        };
    }

    private function layoutFor(string $portal): string
    {
        return match ($portal) {
            'manager'         => 'manager.layouts.app',
            'senior'          => 'senior.layouts.app',
            'marketing-admin' => 'marketing-admin.layouts.app',
            'dealer'          => 'dealer.layouts.app',
            default           => abort(403),
        };
    }

    private function routePrefix(string $portal): string
    {
        return match ($portal) {
            'manager'         => 'manager.dam',
            'senior'          => 'senior.dam',
            'marketing-admin' => 'marketing-admin.dam',
            'dealer'          => 'dealer.dam',
            default           => 'manager.dam',
        };
    }
}
