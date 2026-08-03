<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Şirket (tenant) izolasyonu.
 *
 * OKUMA:  TenantContext::visibleIds() kümesiyle filtrelenir.
 *         null dönerse filtre uygulanmaz (bağlam yok ya da platform sahibi).
 * YAZMA:  company_id boşsa TenantContext::writeId() ile doldurulur — her kayıt
 *         TEK bir şirkete ait olur, kümeye değil.
 *
 * Kapsam dışı bırakmak için: withoutGlobalScope('company') veya
 * TenantContext::runUnscoped(). Bilinçli olarak paylaşımlı modeller için
 * App\Models\Concerns\SharedAcrossCompanies işaret trait'ini kullan.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            // Bağlam kurulmamışsa filtreleme (migration, seeder, bazı konsol komutları).
            // Web isteklerinde SetCompanyContext, kuyrukta AppServiceProvider bağlar.
            $ids = TenantContext::visibleIds();

            if ($ids === null || $ids === []) {
                return;
            }

            $column = $builder->getModel()->qualifyColumn('company_id');

            // Tek şirket → where (index kullanımı whereIn'den net)
            count($ids) === 1
                ? $builder->where($column, $ids[0])
                : $builder->whereIn($column, $ids);
        });

        static::creating(function ($model): void {
            if (!empty($model->company_id)) {
                return;
            }

            // Bağlam yoksa varsayılan şirkete yaz.
            //
            // Bu fallback ŞART: seeder, konsol komutu ve testler bağlam kurmadan kayıt
            // yaratıyor. Fallback olmadan company_id NULL kalır, sonraki HTTP isteğinde
            // filtre devreye girince kayıt "kaybolur". Faz 3'te bağlam her yerde garanti
            // altına alındığında bu fallback daraltılabilir.
            $companyId = TenantContext::writeId() ?? self::fallbackCompanyId();

            if ($companyId !== null && $companyId > 0) {
                $model->company_id = $companyId;
            }
        });
    }

    /** Belirli bir şirkete göre sorgula — global scope'u atlar. */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query
            ->withoutGlobalScope('company')
            ->where($query->getModel()->qualifyColumn('company_id'), $companyId);
    }

    /**
     * Bağlam kurulmamışken yazma hedefi: varsayılan (ilk aktif) şirket.
     * Yalnızca INSERT sırasında kullanılır — okuma filtresini etkilemez.
     */
    private static function fallbackCompanyId(): ?int
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            if (!Schema::hasTable('companies')) {
                return null;
            }

            $id = (int) DB::table('companies')
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');

            return $cached = ($id > 0 ? $id : null);
        } catch (\Throwable) {
            // Migration sırasında tablo henüz yok olabilir — sessizce geç.
            return null;
        }
    }
}
