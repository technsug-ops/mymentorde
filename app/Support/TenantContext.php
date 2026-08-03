<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * Şirket (tenant) bağlamı — TEK KAYNAK.
 *
 * İki ayrı kavramı bilinçli olarak ayırır:
 *
 *   • writeId()     → YAZARKEN kullanılacak TEK şirket. Belirsizlik olamaz:
 *                     yeni kayıt her zaman tek bir şirkete ait olur.
 *   • visibleIds()  → OKURKEN görünen şirket KÜMESİ. null = kısıt yok.
 *
 * Bu ayrım, "firmalar birbirini görmesin" ile "platform sahibi hepsini tek
 * yerden görsün" isteklerini aynı anda karşılar:
 *
 *   Firma A yöneticisi   visible=[A]           write=A
 *   MentorDE senior'ı    visible=[MDE,A,B,C]   write=seçili şirket
 *   Platform sahibi      visible=null (hepsi)  write=seçili şirket
 *
 * GERİYE UYUM: `current_company_id` container binding'i korunur — mevcut kod
 * (ModuleAccess, PublicTheme, GuestResolverService, ...) onu okumaya devam eder.
 *
 * BAĞLAM YOKSA: visibleIds() null döner, yani filtre uygulanmaz. Bu, migration
 * ve seeder gibi bağlam kurulmayan yerlerde eski davranışı korur. Web isteklerinde
 * bağlamı SetCompanyContext middleware'i kurar (web grubuna eklidir), kuyruk
 * işlerinde ise job payload'ından geri yüklenir (AppServiceProvider).
 */
final class TenantContext
{
    /** Yazma hedefi — mevcut kodun okuduğu anahtar, DEĞİŞTİRME. */
    public const WRITE_KEY = 'current_company_id';

    /** Görünür şirket kümesi (list<int>) veya null. */
    public const VISIBLE_KEY = 'tenant_visible_company_ids';

    /**
     * Bağlamı kur.
     *
     * @param  int|null              $writeId     yeni kayıtların yazılacağı şirket
     * @param  list<int>|null        $visibleIds  görünür küme; null = kısıtsız (platform sahibi)
     */
    public static function bind(?int $writeId, ?array $visibleIds = null): void
    {
        if ($writeId !== null && $writeId > 0) {
            App::instance(self::WRITE_KEY, $writeId);
        }

        // "Kısıt yok" durumu `false` ile saklanır, null ile DEĞİL:
        // Laravel container'da instance(key, null) bind edilse bile bound() false döner
        // (isset(null) === false). null saklasaydık "kısıtsız" ile "bağlam yok" birbirine
        // karışır, platform sahibi kendi şirketiyle filtrelenirdi.
        App::instance(self::VISIBLE_KEY, $visibleIds === null ? false : array_values(array_unique(
            array_filter(array_map('intval', $visibleIds), static fn (int $id): bool => $id > 0)
        )));
    }

    /** Yazma hedefi şirket. Bağlam yoksa null. */
    public static function writeId(): ?int
    {
        if (!App::bound(self::WRITE_KEY)) {
            return null;
        }

        $id = (int) App::make(self::WRITE_KEY);

        return $id > 0 ? $id : null;
    }

    /**
     * Okuma filtresi.
     *
     * @return list<int>|null  null = filtre uygulama (kısıtsız ya da bağlam yok)
     */
    public static function visibleIds(): ?array
    {
        if (App::bound(self::VISIBLE_KEY)) {
            /** @var list<int>|false $ids */
            $ids = App::make(self::VISIBLE_KEY);

            // false = bilinçli olarak kısıtsız (platform sahibi)
            return $ids === false ? null : $ids;
        }

        // Küme bind edilmemiş ama yazma hedefi varsa: tek şirketlik küme varsay.
        // (Eski çağrı yerleri yalnızca current_company_id bind ediyordu.)
        $write = self::writeId();

        return $write !== null ? [$write] : null;
    }

    /** Bağlam kuruldu mu? (migration/seeder gibi yerlerde false) */
    public static function isBound(): bool
    {
        return App::bound(self::WRITE_KEY) || App::bound(self::VISIBLE_KEY);
    }

    /**
     * Verilen şirketin bağlamında bir işi çalıştırır, sonra eski bağlamı geri koyar.
     * Kuyruk işleri ve konsol komutları için.
     *
     * @template TReturn
     * @param  \Closure():TReturn  $callback
     * @return TReturn
     */
    public static function runFor(int $companyId, \Closure $callback): mixed
    {
        $previous = self::snapshot();

        try {
            self::bind($companyId, [$companyId]);

            return $callback();
        } finally {
            self::restore($previous);
        }
    }

    /**
     * Şirket filtresi olmadan çalıştırır (platform paneli, cross-tenant raporlar).
     * Yazma hedefi korunur — okuma serbest, yazma yine tek şirkete gider.
     *
     * @template TReturn
     * @param  \Closure():TReturn  $callback
     * @return TReturn
     */
    public static function runUnscoped(\Closure $callback): mixed
    {
        $previous = self::snapshot();

        try {
            self::bind(self::writeId(), null);

            return $callback();
        } finally {
            self::restore($previous);
        }
    }

    /** @return array{write:int|null,visible:list<int>|null,bound:bool} */
    public static function snapshot(): array
    {
        return [
            'write' => self::writeId(),
            'visible' => self::visibleIds(),
            'bound' => self::isBound(),
        ];
    }

    /** @param array{write:int|null,visible:list<int>|null,bound:bool} $snapshot */
    public static function restore(array $snapshot): void
    {
        if (!$snapshot['bound']) {
            self::forget();

            return;
        }

        self::bind($snapshot['write'], $snapshot['visible']);
    }

    public static function forget(): void
    {
        App::forgetInstance(self::WRITE_KEY);
        App::forgetInstance(self::VISIBLE_KEY);
    }
}
