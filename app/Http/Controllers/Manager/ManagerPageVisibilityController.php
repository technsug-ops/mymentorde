<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Support\ModuleAccess;
use App\Support\PageAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sayfa Görünürlüğü Kontrol Paneli — Premium 'page_visibility' modülü.
 *
 * Manager portaldan rol-bazlı sayfa on/off matrix'i. Sadece manager rolü
 * erişebilir; modül kapalı şirkette 404.
 */
class ManagerPageVisibilityController extends Controller
{
    public function index(Request $request)
    {
        ModuleAccess::assertEnabled('page_visibility');

        $companyId = $this->companyId($request);

        // Mevcut visibility map'i + audit (kim/ne zaman) cek (eksik kombinasyonlar default TRUE)
        $rows = DB::table('role_page_visibility as rpv')
            ->leftJoin('users as u', 'u.id', '=', 'rpv.updated_by_user_id')
            ->where('rpv.company_id', $companyId)
            ->get(['rpv.role', 'rpv.page_key', 'rpv.is_visible', 'rpv.updated_at', 'u.name as updated_by_name']);

        $map = [];
        $audit = [];
        foreach ($rows as $r) {
            $map[$r->role][$r->page_key] = (bool) $r->is_visible;
            $audit[$r->role][$r->page_key] = [
                'by' => (string) ($r->updated_by_name ?? '—'),
                'at' => (string) ($r->updated_at ?? ''),
            ];
        }

        return view('manager.page-visibility.index', [
            'pages'       => PageAccess::PAGES,
            'coreRoles'   => PageAccess::coreRoles(),
            'staffRoles'  => PageAccess::staffRoles(),
            'visibility'  => $map,
            'audit'       => $audit,
        ]);
    }

    /**
     * AJAX: tek bir role+page toggle — anlik kayit, JSON response.
     */
    public function toggle(Request $request)
    {
        ModuleAccess::assertEnabled('page_visibility');

        $data = $request->validate([
            'role'       => ['required', 'string', 'max:32'],
            'page_key'   => ['required', 'string', 'max:64'],
            'is_visible' => ['required', 'boolean'],
        ]);

        // Page key + role kataloga uygun mu (URL manipulasyonuna karsi)
        if (!array_key_exists($data['page_key'], PageAccess::PAGES)) {
            \Illuminate\Support\Facades\Log::warning('PV toggle unknown page_key', [
                'received' => $data['page_key'],
                'role'     => $data['role'],
                'catalog'  => array_keys(PageAccess::PAGES),
            ]);
            return response()->json([
                'ok'    => false,
                'error' => 'Bilinmeyen page_key: "' . $data['page_key'] . '". Tarayıcı cache temizleyip yenileyin.',
            ], 422);
        }
        $allRoles = array_merge(array_keys(PageAccess::coreRoles()), array_keys(PageAccess::staffRoles()));
        if (!in_array($data['role'], $allRoles, true)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Bilinmeyen rol: "' . $data['role'] . '"',
            ], 422);
        }

        $companyId = $this->companyId($request);
        $userId    = (int) ($request->user()?->id ?? 0);

        PageAccess::setVisibility(
            $companyId,
            (string) $data['role'],
            (string) $data['page_key'],
            (bool) $data['is_visible'],
            $userId ?: null
        );

        return response()->json([
            'ok' => true,
            'audit' => [
                'by' => (string) ($request->user()?->name ?? '—'),
                'at' => now()->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Bulk preset: bir rol icin tum sayfalari ac/kapat veya tum matrix'i reset et.
     * Action degerleri:
     *   - 'role-all-on' / 'role-all-off' (rol parametresi gerekli)
     *   - 'reset-all' (tum row sil → default-true)
     */
    public function bulkSet(Request $request)
    {
        ModuleAccess::assertEnabled('page_visibility');

        $data = $request->validate([
            'action' => ['required', 'string', 'in:role-all-on,role-all-off,reset-all'],
            'role'   => ['nullable', 'string', 'max:32'],
        ]);

        $companyId = $this->companyId($request);
        $userId    = (int) ($request->user()?->id ?? 0);

        if ($data['action'] === 'reset-all') {
            DB::table('role_page_visibility')->where('company_id', $companyId)->delete();
            PageAccess::flushCache($companyId);
            return response()->json([
                'ok'      => true,
                'message' => 'Tüm matrix sıfırlandı (default-true).',
                'reload'  => true,
            ]);
        }

        $allRoles = array_merge(array_keys(PageAccess::coreRoles()), array_keys(PageAccess::staffRoles()));
        if (empty($data['role']) || !in_array($data['role'], $allRoles, true)) {
            return response()->json(['ok' => false, 'error' => 'Geçerli rol gönderin.'], 422);
        }

        $isVisible = ($data['action'] === 'role-all-on');
        $rows = [];
        foreach (PageAccess::PAGES as $pageKey => $meta) {
            $applicable = in_array($data['role'], (array) ($meta['roles'] ?? []), true)
                || array_key_exists($data['role'], PageAccess::staffRoles());
            if (!$applicable) continue;
            $rows[] = ['role' => $data['role'], 'page_key' => $pageKey, 'is_visible' => $isVisible];
        }
        PageAccess::setBulk($companyId, $rows, $userId ?: null);

        return response()->json([
            'ok'      => true,
            'message' => $isVisible ? 'Tümü açıldı.' : 'Tümü kapatıldı.',
            'reload'  => true,
        ]);
    }

    public function update(Request $request)
    {
        ModuleAccess::assertEnabled('page_visibility');

        $companyId = $this->companyId($request);
        $userId    = (int) ($request->user()?->id ?? 0);

        // Form: visible[role][page_key] = '1' (checkbox işaretliyse)
        $submitted = $request->input('visible', []);
        $rows = [];

        foreach (PageAccess::PAGES as $pageKey => $meta) {
            $allowedRoles = (array) ($meta['roles'] ?? []);
            // Admin altı staff roller bu listede yoksa default 'core' rollere uygulanır
            $rolesToCheck = array_unique(array_merge(
                $allowedRoles,
                array_keys(PageAccess::staffRoles())
            ));

            foreach ($rolesToCheck as $role) {
                $isVisible = !empty($submitted[$role][$pageKey]);
                $rows[] = [
                    'role'       => $role,
                    'page_key'   => $pageKey,
                    'is_visible' => $isVisible,
                ];
            }
        }

        PageAccess::setBulk($companyId, $rows, $userId);

        return back()->with('flash_success', '✅ Sayfa görünürlük ayarları kaydedildi.');
    }

    private function companyId(Request $request): int
    {
        $cid = (int) ($request->user()?->company_id ?? 0);
        if ($cid <= 0 && app()->bound('current_company_id')) {
            $cid = (int) app('current_company_id');
        }
        return max(1, $cid);
    }
}
