<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner modundaki firmalar için panel yüzeyini daraltır.
 *
 * NEDEN: partner firmalar `manager` rolü aldığı için MentorDE'nin tam
 * panelini görüyordu — İnsan Kaynakları, Finans, VIP Oversight, Sistem
 * Yönetimi, AI Labs, UniMatch katalog yönetimi, bayi ağı... 60'tan fazla
 * sayfa. Ama partnerlere yazılım satmıyoruz; onlar öğrencilerini devredip
 * süreci izliyorlar.
 *
 * ⚠ YALNIZCA MENÜYÜ GİZLEMEK YETMEZ. Adresi bilen yine girerdi. Bu yüzden
 * kısıt burada, istek seviyesinde uygulanıyor.
 *
 * Kapsam: yalnızca PERSONEL panelleri. Öğrenci/aday portalları, public
 * sayfalar ve API bu middleware'in dışında — onların kendi kapıları var.
 */
class RestrictPartnerPanel
{
    /**
     * Partner modunda AÇIK kalan yollar.
     *
     * Bayi portalının şekli örnek alındı: aday takibi, öğrenci takibi,
     * belge, destek, hesap. Yönetim ve analiz alanları yok.
     *
     * @var list<string>
     */
    private const ALLOWED = [
        'manager/dashboard',
        'manager/guests',            // aday öğrenciler
        'manager/students',          // öğrenciler
        'manager/process-info',      // süreç bilgisi (salt okunur)
        'manager/leads',             // aday ekleme
        'manager/account',           // hesabım
        'manager/requests',          // destek talepleri
        'manager/bulletins',         // duyurular
        'manager/document-requests', // belge talepleri
        'manager/required-documents',// belge listesi
        'im',                        // öğrenci ve atanan danışmanla yazışma

        // Partner kendi adayının şifresini sıfırlayabilmeli — hesabı o açtı.
        // ⚠ quick-admin'in TAMAMI değil yalnızca bu uç: aynı grupta bayi,
        // senior ve öğrenci SİLME ile rol atama var.
        'manager/quick-admin/reset-password',
    ];

    /**
     * ALLOWED bir ön ek altında kalsa bile KAPALI olan yollar.
     *
     * `manager/guests` açık olunca `manager/guests/*` de açılıyor — rehberler
     * ve danışman ataması oraya dahil. Oysa bunlar operasyonun araçları:
     * Uni-Assist/vize rehberi işi üst firma yürütür, danışmanı da o atar.
     * Partner firma MentorDE'nin danışmanına dışarıdan görev veremez.
     *
     * DENIED, ALLOWED'dan ÖNCE bakılır.
     *
     * @var list<string>
     */
    private const DENIED = [
        'manager/guests/*/uni-assist-rehber',
        'manager/guests/*/uni-assist-rehber/*',
        'manager/guests/*/vize-rehber',
        'manager/guests/*/vize-rehber/*',
        'manager/guests/*/rehber/*',
        'manager/guests/*/assign',              // danışman ataması
        'manager/students/*/uni-assist-rehber',
        'manager/students/*/uni-assist-rehber/*',
        'manager/students/*/vize-rehber',
        'manager/students/*/vize-rehber/*',
        'manager/students/*/rehber/*',
        'manager/students/*/update',            // danışman/risk/ödeme — operasyonun değerlendirmesi
    ];

    /**
     * Partner modunda KAPATILAN alanlar.
     *
     * Personel paneline ait olup yukarıdaki listede bulunmayan her yol.
     * Açık liste yerine bu ön ekleri saymak, ileride /manager altına yeni
     * bir sayfa eklendiğinde onun da otomatik kapalı gelmesini sağlar.
     *
     * @var list<string>
     */
    private const RESTRICTED_AREAS = [
        'manager',
        'tasks',
        'im',
        'config',
        'availability',
        'mktg-admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$this->isRestrictedArea($request)) {
            return $next($request);
        }

        if (!$this->inPartnerMode($user)) {
            return $next($request);
        }

        // Manager dashboard'u MentorDE'nin yönetim ekranı: finans, İK, analiz
        // kartlarına bağlantı veriyor. Partnere kapalı alanların linklerini
        // göstermek anlamsız — doğrudan kendi öğrenci listesine götür.
        if ($request->is('manager/dashboard')) {
            return redirect('/manager/guests');
        }

        // DENIED önce: açık bir ön ekin altında kalan operasyon araçları.
        if ($this->matches($request, self::DENIED)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($this->matches($request, self::ALLOWED)) {
            return $next($request);
        }

        abort(Response::HTTP_NOT_FOUND);
    }

    private function isRestrictedArea(Request $request): bool
    {
        foreach (self::RESTRICTED_AREAS as $area) {
            if ($request->is($area) || $request->is($area . '/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * İstek verilen desenlerden birine uyuyor mu?
     *
     * Desenin kendisi ve alt yolları eşleşir: 'manager/guests' hem listeyi
     * hem detay sayfalarını kapsar.
     *
     * @param list<string> $patterns
     */
    private function matches(Request $request, array $patterns): bool
    {
        foreach ($patterns as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kullanıcının ÇALIŞTIĞI şirket partner modunda mı?
     *
     * Bağlam esas alınır, kullanıcının kendi şirketi değil: MentorDE
     * personeli şirket değiştiriciyle partnere geçtiğinde de o firmanın
     * yüzeyini görmeli — aksi halde partnerin göremediği bir ekranda onun
     * adına işlem yapardı.
     *
     * Platform sahibi muaf: impersonate ile girdiğinde tam panele erişir.
     */
    private function inPartnerMode(User $user): bool
    {
        if ((string) $user->role === User::ROLE_PLATFORM_OWNER) {
            return false;
        }

        $companyId = (int) (TenantContext::writeId() ?? $user->company_id ?? 0);

        if ($companyId <= 0) {
            return false;
        }

        return Company::isPartnerPanel($companyId);
    }
}
