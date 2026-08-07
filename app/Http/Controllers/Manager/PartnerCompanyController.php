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
            // Bu partnerin adaylarına atanabilecek danışmanlar — operasyonu
            // yürüten şirketten gelir, partnerin kendisinden değil.
            'advisors'  => $this->advisorOptions((int) $target->id),
        ]);
    }

    /**
     * Operasyon şirketinin danışmanları: e-posta => görünen ad.
     *
     * ⚠ Kapsamsız: danışman üst firmanın elemanı; firma kapsamlı sorgu onu
     * bulamaz — bu projede tekrar eden hata sınıfı.
     *
     * @return \Illuminate\Support\Collection<string,string>
     */
    private function advisorOptions(int $companyId)
    {
        $operatingId = Company::operatingCompanyId($companyId);

        if ($operatingId === null) {
            return collect();
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $operatingId)
            ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'email', 'role'])
            ->mapWithKeys(fn ($u) => [
                (string) $u->email => trim(($u->name ?: $u->email)) . ' (' . $u->role . ')',
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

        // Varsayılan danışman — yalnızca operasyon şirketinin danışmanları
        // arasından. Doğrulamadan kabul edilirse başka firmanın personeli ya
        // da silinmiş bir hesap atanabilirdi.
        if ($request->has('default_advisor_email')) {
            $email  = strtolower(trim((string) $request->input('default_advisor_email', '')));
            $allowed = $this->advisorOptions((int) $target->id)
                ->keys()
                ->map(fn ($e) => strtolower((string) $e));

            $target->default_advisor_email = $email !== '' && $allowed->contains($email)
                ? $email
                : null;
        }

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
