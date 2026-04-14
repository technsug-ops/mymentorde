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
            // DAM7 — MySQL'de FULLTEXT MATCH AGAINST kullan (10-100x hızlı), diğerlerinde LIKE
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql' && mb_strlen($q) >= 3) {
                // Boolean mode — her kelime prefix match ile (+term*)
                $booleanQ = '+' . implode('* +', preg_split('/\s+/', $q)) . '*';
                $query->whereRaw(
                    'MATCH(name, original_filename, description, doc_code) AGAINST(? IN BOOLEAN MODE)',
                    [$booleanQ]
                );
            } else {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('original_filename', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%")
                      ->orWhere('doc_code', 'like', "%{$q}%");
                });
            }
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

        // DAM5 — Kullanıcının kayıtlı aramaları
        $savedSearches = \App\Models\DigitalAssetSavedSearch::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'query_params']);

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
            'savedSearches'     => $savedSearches,
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
                $created = $this->assets->store($file, $folderId, $this->user(), $meta);
                \App\Models\DigitalAssetActivityLog::record('upload', 'asset', $created->id, $created->name, $this->user(), ['size' => $created->size_bytes, 'folder_id' => $folderId], $request->ip());
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

        $model   = DigitalAsset::query()->findOrFail($asset);
        $oldName = $model->name;
        $this->assets->update($model, $request->only(['name', 'description', 'tags', 'is_pinned']));

        \App\Models\DigitalAssetActivityLog::record('update', 'asset', $model->id, $model->name, $this->user(), ['old_name' => $oldName], $request->ip());

        return back()->with('status', 'Varlık güncellendi.');
    }

    public function destroy(int $asset): RedirectResponse
    {
        $model = DigitalAsset::query()->findOrFail($asset);
        $name  = $model->name;
        $this->assets->delete($model);
        \App\Models\DigitalAssetActivityLog::record('delete', 'asset', $asset, $name, $this->user(), [], request()->ip());
        return back()->with('status', 'Varlık silindi.');
    }

    public function download(int $asset)
    {
        $model = DigitalAsset::query()->findOrFail($asset);
        \App\Models\DigitalAssetActivityLog::record('download', 'asset', $model->id, $model->name, $this->user(), ['size' => $model->size_bytes], request()->ip());
        return $this->assets->download($model, $this->user());
    }

    /**
     * DAM3 — Bulk ZIP download: birden fazla asset'i tek ZIP olarak indir.
     * POST ile asset_ids[] gönderilir. Permission: dam.download.
     */
    public function bulkDownload(Request $request)
    {
        $data = $request->validate([
            'asset_ids'   => ['required', 'array', 'min:1', 'max:200'],
            'asset_ids.*' => ['integer', 'exists:digital_assets,id'],
        ]);

        $user  = $this->user();
        $assets = DigitalAsset::query()->whereIn('id', $data['asset_ids'])->get();

        if ($assets->isEmpty()) {
            return back()->withErrors(['bulk' => 'Hiç varlık bulunamadı.']);
        }

        // Rol filtresi — erişemeyeceği klasörlerdekileri at
        $userRole = (string) $user->role;
        $accessibleFolderIds = DigitalAssetFolder::query()
            ->get()
            ->filter(fn ($f) => $f->isAccessibleByRole($userRole))
            ->pluck('id')
            ->all();

        $assets = $assets->filter(function (DigitalAsset $a) use ($accessibleFolderIds) {
            if ($a->folder_id === null) return true; // root her zaman erişilebilir
            return in_array($a->folder_id, $accessibleFolderIds, true);
        });

        if ($assets->isEmpty()) {
            return back()->withErrors(['bulk' => 'Yetkili olduğunuz varlık yok.']);
        }

        $tempDir  = sys_get_temp_dir();
        $zipName  = 'mentorde-assets-' . now()->format('YmdHis') . '.zip';
        $zipPath  = $tempDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'ZIP dosyası oluşturulamadı.');
        }

        $skipped = 0;
        foreach ($assets as $asset) {
            if ($asset->source_type === 'link') {
                $skipped++;
                continue; // harici link'ler indirilemez
            }
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk($asset->disk ?: 'local');
                if (!$disk->exists($asset->path)) {
                    $skipped++;
                    continue;
                }
                $fileContent = $disk->get($asset->path);
                $entryName   = $this->sanitizeZipName($asset->original_filename ?: $asset->name . '.' . ($asset->extension ?: 'bin'));
                // Çakışma önleme
                $base = $entryName;
                $i = 1;
                while ($zip->locateName($entryName) !== false) {
                    $pathInfo = pathinfo($base);
                    $entryName = ($pathInfo['filename'] ?? 'file') . '-' . $i . (isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '');
                    $i++;
                }
                $zip->addFromString($entryName, $fileContent);

                \App\Models\DigitalAssetActivityLog::record('download', 'asset', $asset->id, $asset->name, $user, ['bulk' => true], $request->ip());
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        $zip->close();

        if (filesize($zipPath) <= 22) { // boş zip ~22 bytes
            @unlink($zipPath);
            return back()->withErrors(['bulk' => 'ZIP oluşturulamadı (tüm dosyalar hatalı).']);
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    private function sanitizeZipName(string $name): string
    {
        // Güvenli dosya adı: path traversal önle, ASCII-safe
        $name = str_replace(['..', '/', '\\', "\0"], '_', $name);
        return substr(trim($name), 0, 240) ?: 'file';
    }

    /**
     * DAM4 — Share link oluştur. Manager + asset editor kullanır.
     */
    public function shareLinkCreate(Request $request, int $asset): RedirectResponse
    {
        $data = $request->validate([
            'expires_hours'  => ['nullable', 'integer', 'min:1', 'max:8760'], // 1 saat - 1 yıl
            'password'       => ['nullable', 'string', 'min:4', 'max:100'],
            'max_downloads'  => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $model = DigitalAsset::query()->findOrFail($asset);

        $link = \App\Models\DigitalAssetShareLink::create([
            'asset_id'           => $model->id,
            'token'              => \App\Models\DigitalAssetShareLink::generateToken(),
            'password_hash'      => !empty($data['password']) ? \Illuminate\Support\Facades\Hash::make($data['password']) : null,
            'created_by_user_id' => $this->user()->id,
            'expires_at'         => !empty($data['expires_hours']) ? now()->addHours((int) $data['expires_hours']) : null,
            'max_downloads'      => $data['max_downloads'] ?? null,
        ]);

        \App\Models\DigitalAssetActivityLog::record('share', 'asset', $model->id, $model->name, $this->user(), ['link_id' => $link->id, 'expires_at' => (string) $link->expires_at], $request->ip());

        $shareUrl = url('/share/' . $link->token);
        return back()->with('status', 'Paylaşım linki oluşturuldu: ' . $shareUrl)->with('share_url', $shareUrl);
    }

    /**
     * DAM4 — Share link iptal et.
     */
    public function shareLinkRevoke(int $linkId): RedirectResponse
    {
        $link = \App\Models\DigitalAssetShareLink::findOrFail($linkId);
        $link->update(['is_revoked' => true]);
        return back()->with('status', 'Paylaşım linki iptal edildi.');
    }

    /**
     * DAM8 — Raporlar sayfası: en çok indirilen, storage kullanımı, aktivite özeti.
     */
    public function reports(): View
    {
        $user      = $this->user();
        $portal    = $this->portalKey($user);
        $layout    = $this->layoutFor($portal);
        $companyId = (int) ($user->company_id ?? 0);

        // En çok indirilen 10 asset
        $topDownloaded = DigitalAsset::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('download_count')
            ->limit(10)
            ->get(['id', 'name', 'download_count', 'category', 'size_bytes', 'folder_id']);

        // Kategori bazlı dağılım
        $byCategory = DigitalAsset::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('category, COUNT(*) as count, SUM(size_bytes) as total_size')
            ->groupBy('category')
            ->get();

        // Toplam storage
        $totalSize = DigitalAsset::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->sum('size_bytes');

        $totalCount = DigitalAsset::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->count();

        // Son 30 gün aktivite özeti
        $activitySummary = \App\Models\DigitalAssetActivityLog::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        // En aktif 10 kullanıcı (son 30 gün)
        $topUsers = \App\Models\DigitalAssetActivityLog::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, user_name, COUNT(*) as count')
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Son 20 aktivite
        $recentActivity = \App\Models\DigitalAssetActivityLog::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('shared.digital-assets.reports', [
            'layout'          => $layout,
            'portal'          => $portal,
            'routePrefix'     => $this->routePrefix($portal),
            'topDownloaded'   => $topDownloaded,
            'byCategory'      => $byCategory,
            'totalSize'       => (int) $totalSize,
            'totalCount'      => $totalCount,
            'activitySummary' => $activitySummary,
            'topUsers'        => $topUsers,
            'recentActivity'  => $recentActivity,
        ]);
    }

    /**
     * DAM5 — Saved search kaydet.
     */
    public function savedSearchStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $queryParams = $request->only(['q', 'tag', 'category', 'uploader', 'size_min_mb', 'size_max_mb', 'from', 'to']);
        $queryParams = array_filter($queryParams, fn ($v) => $v !== null && $v !== '' && $v !== '0');

        \App\Models\DigitalAssetSavedSearch::create([
            'user_id'      => $this->user()->id,
            'name'         => $data['name'],
            'query_params' => $queryParams,
        ]);

        return back()->with('status', 'Arama kaydedildi: ' . $data['name']);
    }

    /**
     * DAM5 — Saved search sil.
     */
    public function savedSearchDestroy(int $searchId): RedirectResponse
    {
        $search = \App\Models\DigitalAssetSavedSearch::where('user_id', $this->user()->id)->findOrFail($searchId);
        $search->delete();
        return back()->with('status', 'Kayıtlı arama silindi.');
    }

    /**
     * DAM4 — PUBLIC: share link ile asset erişimi (auth gerekmez).
     * GET /share/{token}
     */
    public function sharePublic(Request $request, string $token)
    {
        $link = \App\Models\DigitalAssetShareLink::where('token', $token)->first();
        if (!$link) {
            abort(404, 'Paylaşım linki bulunamadı.');
        }

        if ($link->isExpired()) {
            abort(410, 'Paylaşım linkinin süresi dolmuş veya iptal edilmiş.');
        }

        // Password varsa ve henüz girilmemişse formu göster
        if ($link->requiresPassword()) {
            $providedPassword = (string) $request->query('pw', '');
            if ($providedPassword === '' || !$link->checkPassword($providedPassword)) {
                return response()->view('shared.digital-assets.share-password', [
                    'token' => $token,
                    'error' => $providedPassword !== '' ? 'Şifre yanlış.' : null,
                ]);
            }
        }

        $asset = $link->asset;
        if (!$asset) {
            abort(404, 'Varlık bulunamadı.');
        }

        // İndirme sayacı
        $link->increment('download_count');
        $link->forceFill(['last_accessed_at' => now(), 'last_accessed_ip' => $request->ip()])->save();

        // Direkt dosya response (auth gerekmez)
        if ($asset->source_type === 'link' && $asset->external_url) {
            return redirect()->away($asset->external_url);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk($asset->disk ?: 'local');
        if (!$disk->exists($asset->path)) {
            abort(404, 'Dosya bulunamadı.');
        }

        return $disk->download(
            $asset->path,
            $asset->original_filename ?: $asset->name,
            ['Content-Type' => $asset->mime_type ?: 'application/octet-stream']
        );
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

        $created = $this->folders->createFolder(
            $request->string('name'),
            $request->integer('parent_id') ?: null,
            $this->user(),
            $request->only(['description', 'color', 'icon', 'allowed_roles'])
        );

        \App\Models\DigitalAssetActivityLog::record('folder_create', 'folder', $created->id, $created->name, $this->user(), ['parent_id' => $request->integer('parent_id') ?: null], $request->ip());

        return back()->with('status', 'Klasör oluşturuldu.');
    }

    public function folderUpdate(Request $request, int $folder): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:150']]);
        $model   = DigitalAssetFolder::query()->findOrFail($folder);
        $oldName = $model->name;
        $this->folders->rename($model, (string) $request->input('name'));
        \App\Models\DigitalAssetActivityLog::record('folder_rename', 'folder', $model->id, $model->name, $this->user(), ['old_name' => $oldName], $request->ip());
        return back()->with('status', 'Klasör güncellendi.');
    }

    public function folderDestroy(int $folder): RedirectResponse
    {
        $model = DigitalAssetFolder::query()->findOrFail($folder);
        $name  = $model->name;
        $this->folders->delete($model);
        \App\Models\DigitalAssetActivityLog::record('folder_delete', 'folder', $folder, $name, $this->user(), [], request()->ip());
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

        $model  = DigitalAssetFolder::query()->findOrFail($folder);
        $oldParent = $model->parent_id;
        $newParent = $request->integer('parent_id') ?: null;

        try {
            $this->folders->move($model, $newParent);
            \App\Models\DigitalAssetActivityLog::record('folder_move', 'folder', $model->id, $model->name, $this->user(), ['old_parent_id' => $oldParent, 'new_parent_id' => $newParent], $request->ip());
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

        $model     = DigitalAsset::query()->findOrFail($asset);
        $target    = $request->integer('folder_id') ?: null;
        $oldFolder = $model->folder_id;

        // Hedef klasör rol erişim kontrolü
        if ($target) {
            $targetFolder = DigitalAssetFolder::query()->findOrFail($target);
            if (!$targetFolder->isAccessibleByRole((string) $this->user()->role)) {
                abort(403, 'Hedef klasöre erişiminiz yok.');
            }
        }

        $this->assets->move($model, $target);
        \App\Models\DigitalAssetActivityLog::record('move', 'asset', $model->id, $model->name, $this->user(), ['old_folder_id' => $oldFolder, 'new_folder_id' => $target], $request->ip());
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
