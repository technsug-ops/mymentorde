<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeSource;
use App\Services\AiLabs\DocumentExtractor;
use App\Services\AiLabs\KnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * AI Labs bilgi havuzu — kaynak CRUD (PDF / URL / metin).
 *
 * Route: /manager/ai-labs/sources (manager.role + module:ai_labs)
 */
class ManagerAiLabsSourcesController extends Controller
{
    public function index(Request $request): View
    {
        $cid = $this->companyId();

        $sources = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();

        return view('ai-labs.manager.sources.index', [
            'sources' => $sources,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cid = $this->companyId();

        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'type'               => 'required|in:file,url,text',
            'category'           => 'nullable|string|max:80',
            'visible_to_roles'   => 'required|array|min:1',
            'visible_to_roles.*' => 'in:guest,student,senior,manager,admin_staff',
            'url'                => 'nullable|url|max:500|required_if:type,url',
            'content_text'       => 'nullable|string|max:60000|required_if:type,text',
            // Kabul edilen dosyalar: PDF, Word, Excel, Metin
            'doc_file'           => 'nullable|file|mimes:pdf,docx,xlsx,xls,txt,md|max:15360|required_if:type,file',
        ]);

        // Geriye uyumluluk için target_audience değerini roles'tan türet
        $roles = array_values($data['visible_to_roles']);
        $hasGuest = in_array('guest', $roles, true);
        $hasStudent = in_array('student', $roles, true);
        $targetAudience = match (true) {
            $hasGuest && $hasStudent => 'both',
            $hasGuest                => 'guest',
            $hasStudent              => 'student',
            default                  => 'both', // internal-only kaynak için default 'both'
        };

        $filePath = null;
        $content  = null;
        $hash     = null;
        $storedType = $data['type']; // 'file' geldiyse altta pdf veya document'e dönüşür

        if ($data['type'] === 'file') {
            $upload = $request->file('doc_file');
            $ext = strtolower($upload->getClientOriginalExtension());
            $folder = "ai-labs/{$cid}";
            $originalName = $upload->getClientOriginalName();
            $storedName = time() . '_' . Str::random(8) . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME), '-') . '.' . $ext;
            $filePath = $upload->storeAs($folder, $storedName, 'local');

            if ($ext === 'pdf') {
                // PDF → Gemini File API'ye yüklenir (hash dosyadan)
                $storedType = 'pdf';
                $hash = hash_file('sha256', Storage::disk('local')->path($filePath));
            } else {
                // DOCX / XLSX / XLS / TXT / MD → text extract, inline context
                $storedType = 'document';
                $extractor = app(DocumentExtractor::class);
                $absolute = Storage::disk('local')->path($filePath);
                $result = $extractor->extract($absolute, $ext);
                if ($result['ok'] ?? false) {
                    $content = (string) $result['content'];
                    $hash = hash('sha256', $content);
                } else {
                    // Extraction başarısız — dosyayı sil, hata dön
                    Storage::disk('local')->delete($filePath);
                    return back()->withInput()->with('status', '⚠️ Dosya içeriği çıkartılamadı: ' . ($result['error'] ?? 'unknown'));
                }
            }
        } elseif ($data['type'] === 'text') {
            $content = (string) $data['content_text'];
            $hash = hash('sha256', $content);
        }
        // type=url: content_markdown boş kalır — fetchUrlSource() sonra doldurur

        $source = KnowledgeSource::create([
            'company_id'         => $cid,
            'title'              => $data['title'],
            'type'               => $storedType,
            'category'           => $data['category'] ?: null,
            'target_audience'    => $targetAudience,
            'visible_to_roles'   => $roles,
            'url'                => $data['type'] === 'url' ? $data['url'] : null,
            'file_path'          => $filePath,
            'content_markdown'   => $content,
            'content_hash'       => $hash,
            'is_active'          => true,
            'created_by_user_id' => auth()->id(),
        ]);

        // URL kaynağı ise içeriği hemen fetch et — AI kullanabilsin
        $statusMsg = 'Kaynak eklendi.';
        if ($data['type'] === 'url') {
            $fetchResult = app(\App\Services\AiLabs\KnowledgeBaseService::class)
                ->fetchUrlSource($source, force: true);
            $statusMsg .= ($fetchResult['ok'] ?? false)
                ? ' URL içeriği çekildi (' . number_format(($fetchResult['bytes'] ?? 0) / 1024, 1) . ' KB).'
                : ' ⚠️ URL içeriği çekilemedi: ' . ($fetchResult['error'] ?? 'unknown');
        }

        return back()->with('status', $statusMsg);
    }

    public function update(Request $request, int $source): RedirectResponse
    {
        $cid = $this->companyId();
        $row = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('id', $source)
            ->where('company_id', $cid)
            ->firstOrFail();

        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'category'           => 'nullable|string|max:80',
            'visible_to_roles'   => 'required|array|min:1',
            'visible_to_roles.*' => 'in:guest,student,senior,manager,admin_staff',
        ]);

        $roles = array_values($data['visible_to_roles']);
        $hasGuest = in_array('guest', $roles, true);
        $hasStudent = in_array('student', $roles, true);
        $targetAudience = match (true) {
            $hasGuest && $hasStudent => 'both',
            $hasGuest                => 'guest',
            $hasStudent              => 'student',
            default                  => 'both',
        };

        $row->update([
            'title'            => $data['title'],
            'category'         => $data['category'] ?: null,
            'target_audience'  => $targetAudience,
            'visible_to_roles' => $roles,
        ]);

        return back()->with('status', 'Kaynak güncellendi.');
    }

    /**
     * Toplu işlem endpoint'i: seçili kaynaklara rol ekle/çıkar, aktif/pasif yap, sil.
     */
    public function bulkUpdate(Request $request, KnowledgeBaseService $kb): RedirectResponse
    {
        $cid = $this->companyId();
        $data = $request->validate([
            'action'        => 'required|in:add_role,remove_role,replace_roles,activate,deactivate,delete',
            'ids'           => 'required|array|min:1',
            'ids.*'         => 'integer',
            'roles'         => 'nullable|array',
            'roles.*'       => 'in:guest,student,senior,manager,admin_staff',
        ]);

        $query = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereIn('id', $data['ids']);

        $action = $data['action'];
        $affected = 0;

        if ($action === 'delete') {
            // Toplu silme sadece yöneticilere
            $this->assertCanDelete($request);
            $rows = $query->get();
            foreach ($rows as $row) {
                if ($row->gemini_file_id) {
                    $kb->desyncSource($row);
                }
                if ($row->file_path && Storage::disk('local')->exists($row->file_path)) {
                    Storage::disk('local')->delete($row->file_path);
                }
                $row->delete();
                $affected++;
            }
            return back()->with('status', "🗑 {$affected} kaynak silindi.");
        }

        if ($action === 'activate' || $action === 'deactivate') {
            $newState = $action === 'activate';
            $rows = $query->get();
            foreach ($rows as $row) {
                $row->update(['is_active' => $newState]);
                if (!$newState && $row->gemini_file_id) {
                    $kb->desyncSource($row);
                }
                $affected++;
            }
            return back()->with('status', $newState ? "✅ {$affected} kaynak aktifleştirildi." : "⏸ {$affected} kaynak pasifleştirildi.");
        }

        // Rol işlemleri
        $roles = array_values($data['roles'] ?? []);
        if (empty($roles) && $action !== 'replace_roles') {
            return back()->with('status', '⚠️ Rol seçmedin.');
        }

        $rows = $query->get();
        foreach ($rows as $row) {
            $current = array_values($row->visible_to_roles ?: []);
            if ($action === 'add_role') {
                $new = array_values(array_unique(array_merge($current, $roles)));
            } elseif ($action === 'remove_role') {
                $new = array_values(array_diff($current, $roles));
            } else { // replace_roles
                $new = $roles;
            }

            // target_audience'ı tutarlı güncelle
            $hasG = in_array('guest', $new, true);
            $hasS = in_array('student', $new, true);
            $ta = match (true) {
                $hasG && $hasS => 'both',
                $hasG          => 'guest',
                $hasS          => 'student',
                default        => 'both',
            };

            $row->update(['visible_to_roles' => $new, 'target_audience' => $ta]);
            $affected++;
        }

        return back()->with('status', "✅ {$affected} kaynak güncellendi.");
    }

    public function refetch(Request $request, int $source, KnowledgeBaseService $kb): RedirectResponse
    {
        $cid = $this->companyId();
        $row = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('id', $source)
            ->where('company_id', $cid)
            ->firstOrFail();

        if ($row->type !== 'url') {
            return back()->with('status', 'Bu kaynak URL tipi değil.');
        }

        $result = $kb->fetchUrlSource($row, force: true);
        if (!($result['ok'] ?? false)) {
            return back()->with('status', '⚠️ İçerik çekilemedi: ' . ($result['error'] ?? 'unknown'));
        }
        return back()->with('status', "URL içeriği yenilendi (" . number_format(($result['bytes'] ?? 0) / 1024, 1) . " KB).");
    }

    public function toggle(Request $request, int $source, KnowledgeBaseService $kb): RedirectResponse
    {
        $cid = $this->companyId();
        $row = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('id', $source)
            ->where('company_id', $cid)
            ->firstOrFail();

        $newState = !$row->is_active;
        $row->update(['is_active' => $newState]);

        // Pasifleştiğinde Gemini'den sil — yeniden aktif olunca sync command ile yüklenir
        if (!$newState && $row->gemini_file_id) {
            $kb->desyncSource($row);
        }

        return back()->with('status', $newState ? 'Kaynak aktifleştirildi.' : 'Kaynak pasifleştirildi.');
    }

    public function destroy(Request $request, int $source, KnowledgeBaseService $kb): RedirectResponse
    {
        // Silme sadece yöneticilere — senior engellenir
        $this->assertCanDelete($request);

        $cid = $this->companyId();
        $row = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('id', $source)
            ->where('company_id', $cid)
            ->firstOrFail();

        // Gemini'den sil — fail olursa devam et (orphan kalabilir ama DB temiz olsun)
        if ($row->gemini_file_id) {
            $kb->desyncSource($row);
        }

        if ($row->file_path && Storage::disk('local')->exists($row->file_path)) {
            Storage::disk('local')->delete($row->file_path);
        }

        $row->delete();

        return back()->with('status', 'Kaynak silindi.');
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    /**
     * Kaynak silme + toplu silme sadece yöneticilere (manager/admin grupları).
     * Senior danışman ekleyebilir ama silemez.
     */
    private function assertCanDelete(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403, 'Kaynak silme yetkisi sadece yöneticilere aittir.');
        }
    }
}
