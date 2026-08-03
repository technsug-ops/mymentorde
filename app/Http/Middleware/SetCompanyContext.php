<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Şirket (tenant) bağlamını her istek için çözer.
 *
 * ÖNCELİK SIRASI — veri erişimi ASLA host başlığından gelmez:
 *   1. impersonating_company_id  (yalnızca platform sahibi)
 *   2. session current_company_id (yalnızca kullanıcının izinli kümesindeyse)
 *   3. users.company_id
 *   4. varsayılan şirket (PRIMARY_COMPANY_CODE, yoksa ilk aktif)
 *
 * Host'a göre MARKA seçimi Faz 2'de eklenecek ve bilinçli olarak bundan AYRI
 * tutulacak: `Host:` başlığı değiştirilerek tenant atlanamasın diye.
 *
 * Geçmiş: Bu middleware daha önce marketing alanı dışındaki tüm isteklerde erken
 * çıkıp bağlamı zorla varsayılan şirkete sabitliyordu ("ERP/CRM alanlarında firma
 * context her zaman varsayılan firmadır"). Multi-tenant sprint'i iptal edildiğinde
 * alınmış bilinçli bir sadeleştirmeydi; ikinci şirket eklenince veri karışmasına
 * yol açacağı için kaldırıldı.
 */
class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Schema::hasTable('companies')) {
            return $next($request);
        }

        $default = $this->resolveDefaultCompany();
        if (!$default) {
            return $next($request);
        }

        $user = $request->user();
        $hasSession = $request->hasSession();

        $allowed = $this->allowedCompanyIds($user, $default);

        // ── Yazma hedefi: TEK şirket ────────────────────────────────────────
        $companyId = 0;

        // 1) Platform sahibi impersonate ediyorsa onun seçtiği şirket
        if ($hasSession && $this->isPlatformOwner($user)) {
            $companyId = (int) $request->session()->get('impersonating_company_id', 0);
        }

        // 2) Session'daki seçim — SADECE izinli kümedeyse (yetkisiz atlamayı engeller)
        if ($companyId <= 0 && $hasSession) {
            $sessionId = (int) $request->session()->get('current_company_id', 0);
            if ($sessionId > 0 && ($allowed === null || in_array($sessionId, $allowed, true))) {
                $companyId = $sessionId;
            }
        }

        // 3) Kullanıcının kendi şirketi
        if ($companyId <= 0) {
            $companyId = (int) ($user->company_id ?? 0);
        }

        // 4) Varsayılan
        if ($companyId <= 0) {
            $companyId = (int) $default->id;
        }

        $company = $this->findActiveCompany($companyId) ?? $default;

        // ── Okuma kümesi ────────────────────────────────────────────────────
        // Platform sahibi: null (kısıtsız). Diğerleri: izinli küme.
        $visible = $allowed;

        TenantContext::bind((int) $company->id, $visible);

        // Geriye uyum: currentCompany view'larda kullanılıyor.
        app()->instance('current_company', $company);
        View::share('currentCompany', $company);

        if ($hasSession) {
            $request->session()->put('current_company_id', (int) $company->id);
        }

        return $next($request);
    }

    /**
     * Kullanıcının OKUYABİLECEĞİ şirketler.
     *
     * Faz 1: platform sahibi → null (hepsi), diğer herkes → kendi şirketi.
     * Faz 3'te `company_user` pivotu eklenince çok-şirketli personel (bir senior'ın
     * birden fazla partner firmaya hizmet vermesi) BURADA genişletilecek.
     *
     * @return list<int>|null  null = kısıtsız
     */
    private function allowedCompanyIds(?User $user, Company $default): ?array
    {
        if ($this->isPlatformOwner($user)) {
            return null;
        }

        $own = (int) ($user->company_id ?? 0);

        if ($own > 0) {
            return [$own];
        }

        // Giriş yapmamış ziyaretçi (public sayfalar, /apply, /p/{slug}) → varsayılan şirket.
        return [(int) $default->id];
    }

    private function isPlatformOwner(?User $user): bool
    {
        return $user !== null && $user->role === User::ROLE_PLATFORM_OWNER;
    }

    private function findActiveCompany(int $companyId): ?Company
    {
        if ($companyId <= 0) {
            return null;
        }

        return Cache::remember("company:{$companyId}:active", 600, fn () => Company::query()
            ->where('id', $companyId)
            ->where('is_active', true)
            ->first());
    }

    private function resolveDefaultCompany(): ?Company
    {
        $code = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));

        return Cache::remember("default_company:{$code}", 3600, function () use ($code): ?Company {
            $byCode = Company::query()
                ->where('is_active', true)
                ->whereRaw('lower(code) = ?', [$code])
                ->first();

            if ($byCode) {
                return $byCode;
            }

            return Company::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        });
    }
}
