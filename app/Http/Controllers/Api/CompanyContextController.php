<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Şirket bağlamı (company context) API'si.
 *
 * ⚠ GÜVENLİK: Bu controller session'daki `current_company_id`'yi değiştirir,
 * yani kullanıcının HANGİ ŞİRKETİN VERİSİNİ gördüğünü belirler. Her uç nokta
 * kullanıcının o şirkete erişim hakkı olduğunu doğrulamak ZORUNDA — rol/permission
 * kontrolü tek başına yetmez, çünkü "config.manage" yetkisi her şirkette bulunur.
 *
 * Erişim kuralı (Faz 0): Platform Owner → tüm şirketler, diğer herkes → yalnızca
 * kendi `users.company_id`'si. Çok-şirketli personel (senior'ın birden fazla
 * partner firmaya hizmet vermesi) `company_user` pivotuyla Faz 3'te eklenecek;
 * o zaman genişletilmesi gereken tek yer allowedCompanyIds().
 */
class CompanyContextController extends Controller
{
    public function index(Request $request)
    {
        $allowed = $this->allowedCompanyIds($request->user());

        $query = Company::query()->orderBy('name');
        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return response()->json([
            'current_company_id' => (int) $request->session()->get('current_company_id', 0),
            'companies' => $query->get(['id', 'name', 'code', 'is_active']),
        ]);
    }

    public function switch(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $targetId = (int) $data['company_id'];
        $this->assertCanAccess($request->user(), $targetId);

        $company = Company::query()
            ->where('id', $targetId)
            ->where('is_active', true)
            ->firstOrFail();

        $request->session()->put('current_company_id', (int) $company->id);

        return response()->json([
            'ok' => true,
            'current_company_id' => (int) $company->id,
            'company' => $company,
        ]);
    }

    /** Yeni şirket açmak platform sahibinin işidir — müşteri manager'ı tenant yaratamaz. */
    public function store(Request $request)
    {
        $this->assertPlatformOwner($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('companies', 'code')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $row = Company::query()->create([
            'name' => trim((string) $data['name']),
            'code' => strtolower(trim((string) $data['code'])),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'ok' => true,
            'company' => $row,
        ], 201);
    }

    public function update(Request $request, Company $company)
    {
        $this->assertCanAccess($request->user(), (int) $company->id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:190'],
            'code' => ['sometimes', 'required', 'string', 'max:40', 'alpha_dash', Rule::unique('companies', 'code')->ignore($company->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('code', $data)) {
            $payload['code'] = strtolower(trim((string) $data['code']));
        }
        // Kendi şirketini pasife çekip kendini kilitlemesin — bu platform sahibinin kararı.
        if (array_key_exists('is_active', $data) && $this->isPlatformOwner($request->user())) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if (!empty($payload)) {
            $company->update($payload);
        }

        return response()->json([
            'ok' => true,
            'company' => $company->fresh(),
        ]);
    }

    /**
     * Kullanıcının erişebildiği şirket id'leri.
     *
     * @return list<int>|null  null = kısıt yok (platform sahibi), [] = hiçbiri
     */
    private function allowedCompanyIds(?User $user): ?array
    {
        if (!$user) {
            return [];
        }

        if ($this->isPlatformOwner($user)) {
            return null;
        }

        $own = (int) ($user->company_id ?? 0);

        return $own > 0 ? [$own] : [];
    }

    private function assertCanAccess(?User $user, int $companyId): void
    {
        $allowed = $this->allowedCompanyIds($user);

        if ($allowed === null) {
            return;
        }

        if (!in_array($companyId, $allowed, true)) {
            // 403: kaynağın varlığını sızdırmadan reddet.
            throw new AccessDeniedHttpException('Bu şirkete erişim yetkiniz yok.');
        }
    }

    private function assertPlatformOwner(?User $user): void
    {
        if (!$this->isPlatformOwner($user)) {
            throw new AccessDeniedHttpException('Bu işlem yalnızca platform sahibine açıktır.');
        }
    }

    private function isPlatformOwner(?User $user): bool
    {
        return $user !== null && $user->role === User::ROLE_PLATFORM_OWNER;
    }
}
