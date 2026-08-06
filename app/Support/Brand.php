<?php

namespace App\Support;

use App\Models\Company;
use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Şirket başına marka çözümleme.
 *
 * ÜÇ KATMAN (sonraki öncekini ezer):
 *   1. config/brand.php      — .env varsayılanları (YALNIZCA ana şirket için)
 *   2. companies.brand_*     — şirketin marka paketi (platform sahibi tanımlar)
 *   3. marketing_admin_*     — şirketin panelden değiştirdiği değerler (mevcut sistem)
 *
 * Sonuç ÇALIŞMA ANINDA `config('brand')`'e geri yazılır. Bunun anlamı:
 * mevcut 313 `config('brand.*')` çağrısı, 720 blade dosyası, PortalTheme,
 * PublicTheme ve mail şablonları HİÇ DEĞİŞMEDEN doğru markayı verir.
 * Yeni helper öğrenmek ya da view composer eklemek gerekmez.
 *
 * ── WHITE-LABEL KURALI ──────────────────────────────────────────────────
 * `.env`'deki marka değerleri (BRAND_LOGO_URL, BRAND_NAME, banka bilgisi...)
 * PLATFORM SAHİBİNİN kimliğidir. Bir partner firma bu alanları kendi kaydında
 * doldurmadıysa alan BOŞ kalır — asla platformun logosuna/adına düşmez.
 * Aksi halde firma kendi adresinde MentorDE logosu görürdü; white-label sözü
 * sessizce bozulurdu. Bkz. `stripPlatformIdentity()`.
 *
 * `config:cache` ile uyumlu: runtime config() set'i yüklü repository'yi değiştirir,
 * önbellek dosyasına dokunmaz.
 */
final class Brand
{
    /** Firmaya özel taşıyıcının çalışma anında yazıldığı mailer adı. */
    private const TENANT_MAILER = 'tenant_runtime';

    /** Bu isteğin MARKASINI veren şirket (veri şirketinden farklı olabilir). */
    public const BRAND_COMPANY_KEY = 'brand_company_id';

    /**
     * Yalnızca ana şirketin miras alabileceği kimlik alanları.
     *
     * `mail_from_address` bilerek DIŞARIDA: SMTP gönderici adresi altyapıdır,
     * boşalırsa mail gönderimi tamamen kırılır.
     */
    private const PLATFORM_IDENTITY_KEYS = [
        'name', 'legal_name', 'short_name', 'tagline', 'accent',
        'logo_url', 'logo_path', 'favicon_url',
        'email', 'support_email', 'phone', 'address', 'website',
        'mail_from_name',
        'company_no', 'tax_id', 'kvkk_url', 'terms_url', 'privacy_url',
    ];

    /** İç içe kimlik blokları — anahtar yapısı korunur, değerler boşaltılır. */
    private const PLATFORM_IDENTITY_GROUPS = ['banking', 'social'];

    /** `banking.currency` gibi kimlik olmayan teknik alanlar boşaltılmaz. */
    private const KEEP_IN_GROUPS = ['currency'];

    /**
     * config/brand.php'nin BOZULMAMIŞ hali.
     *
     * `apply()` sonucu `config('brand')`'e yazdığı için, aynı istekte ikinci bir
     * `resolve()` çağrısı bir önceki şirketin değerlerini taban alırdı — yani
     * firmalar arası marka sızıntısı. AppServiceProvider açılışta burayı doldurur.
     *
     * @var array<string,mixed>|null
     */
    private static ?array $platformBase = null;

    /** Uygulama açılışında (herhangi bir apply()'dan ÖNCE) çağrılır. */
    public static function rememberPlatformBase(): void
    {
        self::$platformBase = (array) config('brand', []);
    }

    /** Bu istek için markayı şirkete göre yeniden bağla. */
    public static function apply(?Company $company): void
    {
        if (!$company) {
            return;
        }

        config(['brand' => self::resolve($company)]);
        app()->instance(self::BRAND_COMPANY_KEY, (int) $company->id);

        self::applyMailIdentity($company);
    }

    /**
     * Giden mailin GÖNDERİCİ kimliğini şirkete bağla.
     *
     * ── NEDEN GEREKLİYDİ ────────────────────────────────────────────────
     * Marka katmanı yalnızca `config('brand')`'i değiştiriyordu; Laravel'in
     * gönderici bilgisi (`config('mail.from')`) .env'den geliyordu. Sonuç:
     * sayfalar doğru markayı gösteriyor ama HER MAİL "MentorDE" adına
     * çıkıyordu. Partner firmanın kullanıcısı hesabını etkinleştirmek için
     * hiç duymadığı bir isimden mail alıyordu.
     *
     * ── GÖNDEREN ADI ────────────────────────────────────────────────────
     * Ortak portalın altındaki firmalar için iki bilgi de gerekli: mail
     * hangi platformdan geliyor (YourGermanUni) ve hangi firmanın hesabı
     * (Novavia). Tek başına firma adı "bu da nereden çıktı" hissi verir,
     * tek başına portal adı hangi hesap olduğunu söylemez.
     *
     *      YourGermanUni · Novavia Yurtdışı Danışmanlık
     *
     * ⚠ ADRES BİLEREK DEĞİŞTİRİLMİYOR. Gönderici alan adının mail
     * sağlayıcısında (Resend) DOĞRULANMIŞ olması gerekiyor; doğrulanmamış
     * bir adrese geçmek o firmanın TÜM mailini sessizce kırardı. Yalnızca
     * şirket için açıkça `brand_overrides.mail_from_address` tanımlanmışsa
     * kullanılır — yani alan adı doğrulandıktan sonra bilinçli bir adım.
     */
    private static function applyMailIdentity(Company $company): void
    {
        $portal = self::isPrimary($company) ? null : self::portalCompany($company);

        $senderName = self::mailSenderName($company, $portal);

        if ($senderName !== '') {
            config(['mail.from.name' => $senderName]);
        }

        // ⚠ ADRES ÜST FİRMADAN DEVRALINIR.
        //
        // İlk sürüm adresi yalnızca firmanın KENDİ ayarından okuyordu. Ortak
        // portalın altındaki firmalarda bu sessizce platformun adresine
        // düşüyordu: YourGermanUni'ye account@yourgermanuni.com yazılmış olsa
        // bile Novavia'nın maili noreply@mentorde.com'dan çıkıyordu.
        //
        // Doğru sıra: firmanın kendi adresi → bağlı olduğu portalın adresi →
        // platform varsayılanı. Böylece adres bir kez portala yazılıyor ve
        // altındaki her firma onu kullanıyor.
        $address = self::configuredMailAddress($company)
            ?: ($portal ? self::configuredMailAddress($portal) : null);

        if ($address !== null) {
            config(['mail.from.address' => $address]);
        }

        self::applyMailTransport($company, $portal);
    }

    /**
     * Firmanın KENDİ mail taşıyıcısı — varsa platformunkinin yerine geçer.
     *
     * ── NEDEN ────────────────────────────────────────────────────────────
     * White-label platformda gönderim kimliği firmaya ait olmalı. Başka bir
     * markanın maili platformun adresinden çıkarsa white-label sözü bozulur.
     * Ayrıca "kendi mail sunucumu kullanın" diyen firmaya verilecek cevap bu.
     *
     * Zincir adresle aynı: firma → bağlı olduğu portal → platform. Böylece
     * taşıyıcı bir kez portala tanımlanıp altındaki tüm firmalarca
     * kullanılabiliyor; isteyen firma kendi altyapısını getiriyor.
     *
     * ⚠ FİRMANIN ALTYAPISI SENİN KONTROLÜNDE DEĞİL. Şifresi değişirse,
     * kotası dolarsa, sunucusu düşerse o firmanın maili durur. Bu yüzden
     * yalnızca TEST EDİLİP aktifleştirilmiş kayıtlar kullanılıyor
     * (`is_active`), ve hata mesajı kayda geçiyor.
     *
     * ⚠ Yapılandırma hatası mail gönderimini tamamen kırmamalı: sorun
     * çıkarsa platformun taşıyıcısına düşülür, log'a yazılır.
     */
    private static function applyMailTransport(Company $company, ?Company $portal): void
    {
        try {
            $setting = \App\Models\CompanyMailSetting::activeFor((int) $company->id);

            if (!$setting && $portal) {
                $setting = \App\Models\CompanyMailSetting::activeFor((int) $portal->id);
            }

            if (!$setting || !$setting->isComplete()) {
                return;
            }

            config(['mail.mailers.' . self::TENANT_MAILER => $setting->mailerConfig()]);
            config(['mail.default' => self::TENANT_MAILER]);

            if ($setting->driver === \App\Models\CompanyMailSetting::DRIVER_RESEND) {
                self::useResendKey((string) $setting->api_key);
            }

            // Taşıyıcıya özel gönderen adresi markayı ezer: kimlik bilgisi
            // hangi alan adına aitse gönderim de ondan çıkmalı, yoksa
            // sağlayıcı reddeder.
            $from = trim((string) $setting->from_address);

            if ($from !== '') {
                config(['mail.from.address' => $from]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Company mail transport not applied', [
                'company' => (int) $company->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resend anahtarını çalışma anında değiştir.
     *
     * ── ÜÇ TUZAK BİRDEN ──────────────────────────────────────────────────
     * 1. Paket anahtarı ÖNCE `resend.api_key`'den okuyor, `services.resend.key`
     *    yalnızca o boşsa devreye giriyor. İlk sürüm sadece ikincisini
     *    yazıyordu; .env'den gelen `resend.api_key` dolu olduğu için firma
     *    anahtarı HİÇ kullanılmıyordu ve gönderim platformun hesabından
     *    deneniyordu — "domain is not verified" hatasının sebebi buydu.
     *
     * 2. Resend istemcisi SINGLETON. Bir kez çözüldükten sonra yapılandırmayı
     *    değiştirmek işe yaramaz; örneği unutturmak gerekiyor.
     *
     * 3. Laravel mailer'ları da adına göre önbelleğe alıyor. Aynı istekte
     *    iki farklı firmaya mail giderse ikincisi birincinin taşıyıcısını
     *    kullanırdı.
     */
    private static function useResendKey(string $key): void
    {
        if ($key === '') {
            return;
        }

        config([
            'resend.api_key'      => $key,
            'services.resend.key' => $key,
        ]);

        self::forgetResolvedMailers();
    }

    /** Çözülmüş Resend istemcisini ve mailer önbelleğini bırak. */
    private static function forgetResolvedMailers(): void
    {
        try {
            app()->forgetInstance(\Resend\Contracts\Client::class);
            app()->forgetInstance('resend');
        } catch (\Throwable $e) {
            // Paket yoksa (test ortamı) sorun değil.
        }

        try {
            \Illuminate\Support\Facades\Mail::forgetMailers();
        } catch (\Throwable $e) {
            // Mail yöneticisi henüz kurulmadıysa yapacak bir şey yok.
        }
    }

    /**
     * Şirket için AÇIKÇA tanımlanmış gönderen adresi — yoksa null.
     *
     * `brand_overrides` okunuyor, çözülmüş marka değil: çözülmüş pakette
     * platformun varsayılan adresi de bulunur ve "tanımlanmış mı" sorusunu
     * cevaplayamaz.
     */
    private static function configuredMailAddress(Company $company): ?string
    {
        $overrides = $company->getAttribute('brand_overrides');

        if (is_string($overrides)) {
            $overrides = json_decode($overrides, true);
        }

        if (!is_array($overrides)) {
            return null;
        }

        $address = trim((string) ($overrides['mail_from_address'] ?? ''));

        return $address !== '' ? $address : null;
    }

    /**
     * "Portal · Firma" biçiminde gönderen adı.
     *
     * Platformun kendi şirketinde sade marka adı kullanılır; alt firmalarda
     * ortak portalın adı öne alınır.
     */
    private static function mailSenderName(Company $company, ?Company $portal): string
    {
        $own = trim((string) (config('brand.mail_from_name') ?: config('brand.name') ?: ''));

        if (self::isPrimary($company)) {
            return $own;
        }

        if (!$portal || (int) $portal->id === (int) $company->id) {
            return $own;
        }

        $portalName = trim((string) ($portal->brand_name ?: $portal->name ?: ''));

        if ($portalName === '' || $own === '' || $portalName === $own) {
            return $own !== '' ? $own : $portalName;
        }

        return $portalName . ' · ' . $own;
    }

    /**
     * Şirketin bağlı olduğu ortak portal (is_public_portal) — yoksa null.
     *
     * Kendisi portal olabilir; değilse en yakın üst firmaya bakılır.
     */
    private static function portalCompany(Company $company): ?Company
    {
        try {
            $candidates = array_merge([(int) $company->id], Company::ancestorIds((int) $company->id));

            foreach ($candidates as $id) {
                $candidate = Company::query()->withoutGlobalScope('company')->find($id);

                if ($candidate && (bool) $candidate->is_public_portal) {
                    return $candidate;
                }
            }
        } catch (\Throwable $e) {
            // Marka çözümü mail kimliği yüzünden patlamamalı.
            \Illuminate\Support\Facades\Log::warning('Brand portal lookup failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Şirketin çözülmüş marka paketi.
     *
     * @return array<string,mixed>
     */
    public static function resolve(Company $company): array
    {
        $base = self::$platformBase ?? (array) config('brand', []);

        if (!self::isPrimary($company)) {
            $base = self::stripPlatformIdentity($base);
        }

        $resolved = Cache::remember(
            self::cacheKey((int) $company->id),
            600,
            static fn (): array => array_replace_recursive(
                self::fromCompany($company),
                self::fromSettings((int) $company->id),
            )
        );

        return array_replace_recursive($base, $resolved);
    }

    /** Şirket, platformun kendi şirketi mi (config('app.primary_company_code'))? */
    public static function isPrimary(Company $company): bool
    {
        $primaryCode = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));
        $code = strtolower(trim((string) $company->getAttribute('code')));

        return $code !== '' && $code === $primaryCode;
    }

    /**
     * Platformun kimlik alanlarını taban paketten çıkar.
     *
     * Anahtarlar SİLİNMEZ, boşaltılır: blade'lerdeki `config('brand.x')` çağrıları
     * eksik anahtarda null döner ve ikinci argümandaki "MentorDE" varsayılanına
     * düşerdi — sızıntı yeniden başlardı. Boş string bunu keser.
     *
     * @param  array<string,mixed>  $base
     * @return array<string,mixed>
     */
    private static function stripPlatformIdentity(array $base): array
    {
        foreach (self::PLATFORM_IDENTITY_KEYS as $key) {
            if (array_key_exists($key, $base)) {
                $base[$key] = '';
            }
        }

        foreach (self::PLATFORM_IDENTITY_GROUPS as $group) {
            if (!is_array($base[$group] ?? null)) {
                continue;
            }

            foreach (array_keys($base[$group]) as $key) {
                if (!in_array($key, self::KEEP_IN_GROUPS, true)) {
                    $base[$group][$key] = '';
                }
            }
        }

        return $base;
    }

    /**
     * Şirket kaydındaki marka alanları → config/brand.php şekline dönüştür.
     *
     * @return array<string,mixed>
     */
    private static function fromCompany(Company $company): array
    {
        $out = [];
        $isPrimary = self::isPrimary($company);

        // brand_overrides config/brand.php'nin tam şeklini taklit eder (banka, hukuki,
        // sosyal, mail kimliği, ödeme günleri...) — önce o, sonra düz kolonlar biner.
        $overrides = $company->getAttribute('brand_overrides');
        if (is_string($overrides)) {
            $overrides = json_decode($overrides, true);
        }
        if (is_array($overrides)) {
            $out = $overrides;
        }

        $name = trim((string) ($company->getAttribute('brand_name') ?? ''));

        // Partner firma marka adını doldurmadıysa KENDİ adını kullanır.
        // Platformun adına düşmek white-label sözünü bozardı.
        if ($name === '' && !$isPrimary) {
            $name = trim((string) ($company->getAttribute('name') ?? ''));
        }

        if ($name !== '') {
            $out['name'] = $name;
            // legal_name/short_name açıkça verilmediyse marka adına düşsün
            $out['legal_name'] ??= $name;
            $out['short_name'] ??= $name;
            $out['mail_from_name'] ??= $name;
        }

        $logo = trim((string) ($company->getAttribute('brand_logo_url') ?? ''));
        if ($logo !== '') {
            $out['logo_url'] = $logo;
        }

        $color = trim((string) ($company->getAttribute('brand_primary_color') ?? ''));
        if ($color !== '') {
            $out['theme']['primary'] = $color;
            // Şirket rengi AÇIKÇA seçildi mi? config/brand.php'nin env varsayılanı da
            // bir renk döndürdüğü için "tanımlı mı" sorusu değerden anlaşılamıyor.
            // Favicon/theme-color gibi yerler platform tonunu koruyabilsin diye ayrı bayrak.
            $out['theme']['primary_source'] = 'company';
        }

        $domain = trim((string) ($company->getAttribute('primary_domain') ?? ''));
        if ($domain !== '') {
            $out['website'] = 'https://' . $domain;
        }

        // İletişim adresi verilmediyse faturalama adresine düş — boş bırakmak
        // sözleşme/mail şablonlarında görünür boşluk üretir.
        if (!$isPrimary) {
            $billing = trim((string) ($company->getAttribute('billing_email') ?? ''));
            if ($billing !== '') {
                $out['email'] ??= $billing;
                $out['support_email'] ??= $billing;
            }
        }

        // Public sayfalarda (login, /apply) B2C kazanım içeriği gösterilsin mi?
        // Kolon henüz yoksa (migration öncesi) varsayılan: göster.
        $marketing = $company->getAttribute('public_marketing');
        $out['public_marketing'] = $marketing === null ? true : (bool) $marketing;

        return $out;
    }

    /**
     * marketing_admin_settings'teki marka anahtarları (manager panelden düzenlenir).
     * Mevcut sistem — AppServiceProvider'daki view composer da bunu okuyordu.
     *
     * @return array<string,mixed>
     */
    private static function fromSettings(int $companyId): array
    {
        try {
            $rows = MarketingAdminSetting::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('setting_key', ['brand_name', 'brand_logo_url', 'brand_logo_height', 'ai_labs_brand_name'])
                ->pluck('setting_value', 'setting_key');
        } catch (\Throwable) {
            // Tablo henüz yok (migration) ya da DB erişilemez — sessizce geç.
            return [];
        }

        $out = [];

        $name = trim((string) self::settingValue($rows['brand_name'] ?? null));
        if ($name !== '') {
            $out['name'] = $name;
            $out['legal_name'] ??= $name;
            $out['short_name'] ??= $name;
            $out['mail_from_name'] ??= $name;
        }

        $logo = trim((string) self::settingValue($rows['brand_logo_url'] ?? null));
        if ($logo !== '') {
            $out['logo_url'] = $logo;
        }

        $height = trim((string) self::settingValue($rows['brand_logo_height'] ?? null));
        if ($height !== '') {
            $out['logo_height'] = (int) $height;
        }

        $aiLabs = trim((string) self::settingValue($rows['ai_labs_brand_name'] ?? null));
        if ($aiLabs !== '') {
            $out['ai_labs_name'] = $aiLabs;
        }

        return $out;
    }

    /**
     * setting_value JSON kolonu `{"value": "..."}` şeklinde saklanır
     * (bkz. MarketingAdminSetting::setValue). Düz string de tolere edilir.
     */
    private static function settingValue(mixed $raw): mixed
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        if (is_array($raw)) {
            return $raw['value'] ?? '';
        }

        return $raw ?? '';
    }

    /**
     * Çözülmüş markanın anlık görüntüsü.
     *
     * Kuyruk işleri web isteğinin İÇİNDE çalışıyor (KAS'ta cron yok). İş, ait
     * olduğu şirketin markasını uygulayıp bitince isteğin markasını iade etmeli.
     *
     * @return array{brand:array<string,mixed>,company_id:int|null}
     */
    public static function snapshot(): array
    {
        return [
            'brand' => (array) config('brand', []),
            // Gönderici kimliği de taşınmalı: iş, isteğin markasını iade
            // ettiğinde mail "from" bilgisi geride kalırsa sonraki mail
            // yanlış firma adına çıkardı.
            'mail_from' => (array) config('mail.from', []),
            // ⚠ TAŞIYICI DA TAŞINMALI. Aksi halde bir firmanın kendi mail
            // sunucusu, aynı istekte işlenen SONRAKİ firmanın mailini de
            // gönderirdi — başka markanın kimliğiyle çıkan mail demek.
            'mail_default'  => config('mail.default'),
            'tenant_mailer' => config('mail.mailers.' . self::TENANT_MAILER),
            // Paket anahtarı ÖNCE resend.api_key'den okuyor; ikisi de taşınmalı.
            'resend_key'    => config('services.resend.key'),
            'resend_pkg_key'=> config('resend.api_key'),
            'company_id' => app()->bound(self::BRAND_COMPANY_KEY)
                ? (int) app(self::BRAND_COMPANY_KEY)
                : null,
        ];
    }

    /** @param array{brand:array<string,mixed>,mail_from?:array<string,mixed>,company_id:int|null} $snapshot */
    public static function restore(array $snapshot): void
    {
        config(['brand' => $snapshot['brand'] ?? []]);

        if (isset($snapshot['mail_from'])) {
            config(['mail.from' => $snapshot['mail_from']]);
        }

        if (array_key_exists('mail_default', $snapshot)) {
            config(['mail.default' => $snapshot['mail_default']]);
            config(['mail.mailers.' . self::TENANT_MAILER => $snapshot['tenant_mailer'] ?? null]);
            config(['services.resend.key' => $snapshot['resend_key'] ?? null]);
            config(['resend.api_key' => $snapshot['resend_pkg_key'] ?? null]);

            // Anahtar geri alındı ama çözülmüş istemci hâlâ eskisini tutuyor
            // olabilir — bırakılmazsa iade sözde kalır.
            self::forgetResolvedMailers();
        }

        $companyId = $snapshot['company_id'] ?? null;

        if ($companyId !== null) {
            app()->instance(self::BRAND_COMPANY_KEY, (int) $companyId);
        } else {
            app()->forgetInstance(self::BRAND_COMPANY_KEY);
        }
    }

    public static function cacheKey(int $companyId): string
    {
        return "brand:resolved:{$companyId}";
    }

    /** Şirketin markası değiştiğinde çağır (Company observer / ThemeController). */
    public static function flushCache(int $companyId): void
    {
        Cache::forget(self::cacheKey($companyId));
    }
}
