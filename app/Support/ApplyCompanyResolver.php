<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Başvurunun HANGİ FİRMAYA yazılacağını çözer.
 *
 * B2B modelinde her partner firma kendi başvuru linkini öğrencisine verir:
 *
 *     yourgermanuni.com/apply/abc-egitim   →  kayıt ABC Eğitim'e
 *     yourgermanuni.com/apply/xyz-akademi  →  kayıt XYZ Akademi'ye
 *     yourgermanuni.com/apply              →  kayıt varsayılan şirkete (B2C havuzu)
 *
 * Firma bilgisi GET landing'inde session'a yazılır, POST'ta oradan okunur. Form
 * ayrıca gizli alanla aynı bilgiyi taşır (session kaybolursa yedek). Kurcalamanın
 * riski düşük: kişi yalnızca KENDİ başvurusunu başka firmaya yazdırabilir, hiçbir
 * veri okuyamaz — okuma yetkisi asla buradan gelmez.
 */
final class ApplyCompanyResolver
{
    public const SESSION_KEY = 'apply.company_id';

    /** Formdaki gizli yedek alan. */
    public const FORM_FIELD = 'apply_company';

    /**
     * Lead'i devralabilecek personel rolleri.
     *
     * Personeli olmayan bir şirkete başvuru kabul etmek, kaydı kimsenin bakmadığı
     * bir kuyruğa atmak demektir — nötr portal şirketi (yourgermanuni) tam olarak
     * bu durumda: marka taşıyıcısı, tenant değil.
     */
    private const STAFF_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SENIOR,
        User::ROLE_MENTOR,
        User::ROLE_SALES_ADMIN,
        User::ROLE_SALES_STAFF,
        User::ROLE_MARKETING_ADMIN,
        User::ROLE_OPERATIONS_ADMIN,
    ];

    /** Slug (yoksa code) ile başvuru kabul eden aktif firma. */
    public static function bySlug(string $slug): ?Company
    {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return null;
        }

        $company = Cache::remember(
            "apply:company_by_slug:{$slug}",
            300,
            static fn (): ?Company => Company::query()
                ->where('is_active', true)
                ->where(function ($q) use ($slug): void {
                    $q->whereRaw('lower(slug) = ?', [$slug])
                        ->orWhereRaw('lower(code) = ?', [$slug]);
                })
                ->first()
        );

        return ($company && self::acceptsApplications($company)) ? $company : null;
    }

    /** Şirketin lead'e bakacak en az bir aktif personeli var mı? */
    public static function acceptsApplications(Company $company): bool
    {
        if (!$company->is_active) {
            return false;
        }

        return (bool) Cache::remember(
            "apply:company_has_staff:{$company->id}",
            300,
            static fn (): bool => User::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->whereIn('role', self::STAFF_ROLES)
                ->exists()
        );
    }

    /**
     * POST /apply için hedef firma.
     *
     * Önce session (GET landing'inde yazıldı), sonra formdaki gizli yedek.
     * Hiçbiri geçerli değilse null → çağıran taraf varsayılan davranışta kalır
     * (kayıt B2C havuzuna düşer).
     */
    public static function fromRequest(Request $request): ?Company
    {
        if ($request->hasSession()) {
            $sessionId = (int) $request->session()->get(self::SESSION_KEY, 0);

            if ($sessionId > 0) {
                $company = Company::query()->find($sessionId);

                if ($company && self::acceptsApplications($company)) {
                    return $company;
                }
            }
        }

        $fromForm = trim((string) $request->input(self::FORM_FIELD, ''));

        return $fromForm !== '' ? self::bySlug($fromForm) : null;
    }

    /** GET landing'inde çağrılır. */
    public static function remember(Request $request, Company $company): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, (int) $company->id);
        }
    }

    /** Firmanın paylaşacağı tam başvuru adresi. */
    public static function linkFor(Company $company): string
    {
        $slug = trim((string) ($company->slug ?: $company->code));

        return url('/apply/' . $slug);
    }

    public static function flushCache(Company $company): void
    {
        Cache::forget("apply:company_has_staff:{$company->id}");

        foreach (array_filter([$company->slug, $company->code]) as $key) {
            Cache::forget('apply:company_by_slug:' . strtolower((string) $key));
        }
    }
}
