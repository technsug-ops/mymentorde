<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

/**
 * Kimlik doğrulama şirket filtresinden MUAF tutan user provider.
 *
 * NEDEN GEREKLİ:
 * `User` modeline şirket scope'u eklendiğinde kimlik doğrulama kırılır — çünkü
 * tavuk-yumurta problemi vardır:
 *
 *   1. Kullanıcı /login'e gelir; henüz kim olduğu bilinmez.
 *   2. Şirket bağlamı kullanıcıdan türetilir → bağlam varsayılan şirkettir.
 *   3. Provider `where email = ...` yapar; scope `AND company_id = <varsayılan>`
 *      ekler → BAŞKA şirketteki kullanıcı BULUNAMAZ → "kimlik bilgileri hatalı".
 *
 * Aynı sorun şifre sıfırlama, "beni hatırla" çerezi ve oturum yenilemede de çıkar.
 *
 * ÇÖZÜM: Kimlik GLOBAL, yetki TENANT'LI.
 * Bu provider yalnızca "bu e-posta/şifre kime ait?" sorusunu cevaplar. Kullanıcı
 * doğrulandıktan sonra SetCompanyContext onun `company_id`'sine göre bağlamı kurar
 * ve o andan itibaren TÜM veri sorguları şirketle sınırlanır.
 *
 * Yani burada scope'u kaldırmak bir güvenlik boşluğu DEĞİL: kullanıcı yalnızca
 * kendi şifresiyle giriş yapabilir ve giriş sonrası yalnızca kendi şirketinin
 * verisini görür. Bkz. tests/Feature/Tenancy/TenantIsolationTest.
 */
class TenantAwareUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null): Builder
    {
        return parent::newModelQuery($model)->withoutGlobalScope('company');
    }
}
