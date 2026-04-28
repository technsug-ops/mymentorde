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

        // Mevcut visibility map'i çek (eksik kombinasyonlar default TRUE)
        $rows = DB::table('role_page_visibility')
            ->where('company_id', $companyId)
            ->get(['role', 'page_key', 'is_visible']);

        $map = [];
        foreach ($rows as $r) {
            $map[$r->role][$r->page_key] = (bool) $r->is_visible;
        }

        return view('manager.page-visibility.index', [
            'pages'       => PageAccess::PAGES,
            'coreRoles'   => PageAccess::coreRoles(),
            'staffRoles'  => PageAccess::staffRoles(),
            'visibility'  => $map,
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
