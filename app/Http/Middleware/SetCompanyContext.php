<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Support\Brand;
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

        $company = $this->findActiveCompany($companyId);

        if (!$company) {
            // Şirket pasife çekilmişse VARSAYILANA DÜŞME.
            //
            // Düşseydi askıya alınmış bir firmanın kullanıcısı platformun
            // şirketini yazma hedefi olarak alır ve ürettiği her kayıt
            // MentorDE kutusuna düşerdi. Okuma kümesi kendi (pasif) şirketi
            // olduğu için hiçbir şey göremez ama yazdığı yer yanlış olurdu.
            //
            // Kendi şirketinde kalsın: göremez, yazamaz, kimseye bulaşmaz.
            $ownCompanyId = (int) ($user->company_id ?? 0);

            $company = ($ownCompanyId > 0 && $ownCompanyId === $companyId)
                ? (Company::query()->find($companyId) ?? $default)
                : $default;
        }

        // ── Okuma kümesi ────────────────────────────────────────────────────
        // Platform sahibi: null (kısıtsız). Diğerleri: izinli küme.
        $visible = $allowed;

        TenantContext::bind((int) $company->id, $visible);

        // ── MARKA — veriden AYRI çözülür ────────────────────────────────────
        // Giriş yapmış kullanıcı kendi şirketinin markasını görür; anonim ziyaretçi
        // (login sayfası, /apply, partner mini-site) geldiği DOMAIN'in markasını.
        //
        // ⚠ Host YALNIZCA markayı belirler, veri erişimini ASLA. Aksi halde `Host:`
        // başlığı değiştirilerek başka tenant'ın verisi okunabilirdi.
        $brandCompany = $user !== null
            ? $company
            : ($this->resolveCompanyByHost($request->getHost()) ?? $company);

        Brand::apply($brandCompany);

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
     *   • Platform sahibi           → null (kısıtsız)
     *   • Çok-şirketli personel     → kendi şirketi + company_user pivotundakiler
     *   • Firma kullanıcısı         → yalnızca kendi şirketi (pivotta satırı yok)
     *   • Giriş yapmamış ziyaretçi  → varsayılan şirket
     *
     * @return list<int>|null  null = kısıtsız
     */
    private function allowedCompanyIds(?User $user, Company $default): ?array
    {
        if ($this->isPlatformOwner($user)) {
            return null;
        }

        if ($user !== null) {
            $ids = $user->visibleCompanyIds();

            if ($ids !== []) {
                return $ids;
            }
        }

        // Giriş yapmamış ziyaretçi (public sayfalar, /apply, /p/{slug}) → varsayılan şirket.
        return [(int) $default->id];
    }

    private function isPlatformOwner(?User $user): bool
    {
        return $user !== null && $user->role === User::ROLE_PLATFORM_OWNER;
    }

    /**
     * Host → şirket (YALNIZCA marka için).
     *
     * `primary_domain` eşleşmesi, sonra `domain_aliases` içinde arama.
     * Eşleşme yoksa null → çağıran taraf erişim şirketinin markasına düşer,
     * yani bugünkü davranış korunur (hiçbir şirkette domain tanımlı değilken).
     */
    private function resolveCompanyByHost(string $host): ?Company
    {
        $host = strtolower(trim($host));

        if ($host === '' || !Schema::hasColumn('companies', 'primary_domain')) {
            return null;
        }

        return Cache::remember("company:by_host:{$host}", 600, function () use ($host): ?Company {
            $direct = Company::query()
                ->where('is_active', true)
                ->whereRaw('lower(primary_domain) = ?', [$host])
                ->first();

            if ($direct) {
                return $direct;
            }

            // Alias listesi JSON — sürücüden bağımsız kalmak için PHP tarafında ara.
            // Domain tanımlı şirket sayısı çok az olacağı için maliyeti önemsiz.
            return Company::query()
                ->where('is_active', true)
                ->whereNotNull('domain_aliases')
                ->get()
                ->first(function (Company $company) use ($host): bool {
                    $aliases = $company->getAttribute('domain_aliases');
                    if (is_string($aliases)) {
                        $aliases = json_decode($aliases, true);
                    }

                    return is_array($aliases)
                        && in_array($host, array_map(
                            static fn ($a): string => strtolower(trim((string) $a)),
                            $aliases
                        ), true);
                });
        });
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
