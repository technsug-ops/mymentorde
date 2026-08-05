<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\PermissionCeiling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Üst firmanın kendi ALT firmalarını yönetmesi.
 *
 * Yetki tavanını şimdiye kadar yalnızca platform sahibi (DGmarkt konsolu)
 * ayarlayabiliyordu. Oysa kısıtı koyması gereken taraf ağacın üstündeki
 * FİRMA — partnerle anlaşmayı o yapıyor, sınırı o biliyor.
 *
 * ── SINIRLAR ─────────────────────────────────────────────────────────────
 *   • Yalnızca KENDİ alt firmaları (Company::descendantIds)
 *   • Kendi şirketine kısıt koyamaz — kendini kilitlemesin
 *   • Üstten MİRAS gelen kısıtı kaldıramaz (platform sahibinin koyduğu kalır)
 *   • Yalnızca `role.template.manage` yetkisi olan personel
 *
 * Kişisel veri göstermez: bu ekran firma ve yetki hakkındadır.
 */
class PartnerCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $children = $this->manageableCompanies($request->user());

        return view('manager.partners.index', [
            'children' => $children,
        ]);
    }

    public function edit(Request $request, int $company): View
    {
        $target = $this->assertManageable($request->user(), $company);

        $own = collect($target->denied_permission_codes ?? []);
        $effective = collect(Company::effectiveDeniedPermissions((int) $target->id));

        return view('manager.partners.edit', [
            'company'   => $target,
            'own'       => $own,
            'inherited' => $effective->diff($own),
            'groups'    => PermissionCeiling::grouped(),
        ]);
    }

    public function update(Request $request, int $company): RedirectResponse
    {
        $target = $this->assertManageable($request->user(), $company);

        $requested = PermissionCeiling::sanitize($request->input('denied_permission_codes', []));

        // Üstten miras gelen kısıtlar bu ekrandan KALDIRILAMAZ; formda devre
        // dışı geldikleri için POST'a da girmezler. Yine de yazarken kendi
        // listemizden düşürüyoruz — miras zaten hesaplamada ekleniyor.
        $inherited = collect(Company::effectiveDeniedPermissions((int) $target->id))
            ->diff(collect($target->denied_permission_codes ?? []))
            ->all();

        $own = array_values(array_diff($requested, $inherited));

        $target->denied_permission_codes = $own !== [] ? $own : null;
        $target->save();

        Company::flushHierarchyCache();

        return back()->with('status', $own === []
            ? ($target->brand_name ?: $target->name) . ' için kısıt kaldırıldı.'
            : ($target->brand_name ?: $target->name) . ' için ' . count($own) . ' yetki kısıtlandı.');
    }

    /**
     * Kullanıcının yönetebileceği alt firmalar.
     *
     * @return \Illuminate\Support\Collection<int,Company>
     */
    private function manageableCompanies(?User $user)
    {
        if (!$user || !$user->hasPermissionCode('role.template.manage')) {
            return collect();
        }

        $ids = Company::descendantIds((int) ($user->company_id ?? 0));

        if ($ids === []) {
            return collect();
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_name', 'code', 'is_active', 'parent_company_id', 'denied_permission_codes']);
    }

    private function assertManageable(?User $user, int $companyId): Company
    {
        if (!$user || !$user->hasPermissionCode('role.template.manage')) {
            throw new AccessDeniedHttpException('Bu işlem için yetkiniz yok.');
        }

        // Kendi şirketi HARİÇ — kendini kilitlemesin.
        if ((int) $user->company_id === $companyId) {
            throw new AccessDeniedHttpException('Kendi şirketinize kısıt koyamazsınız.');
        }

        if (!in_array($companyId, Company::descendantIds((int) $user->company_id), true)) {
            throw new AccessDeniedHttpException('Bu firma sizin altınızda değil.');
        }

        return Company::query()->where('id', $companyId)->firstOrFail();
    }
}
