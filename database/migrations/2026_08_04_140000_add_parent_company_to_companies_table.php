<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Şirket hiyerarşisi — "üst firma".
 *
 * İş modeli:
 *
 *     DGmarkt            SaaS saglayici (platform sahibi)
 *       └── MentorDE     operasyonu yuruten firma
 *             ├── B2C ogrenciler + kisisel partnerler (bayi)
 *             └── B2B kurumsal partnerler (Aythink, ...)
 *
 * MentorDE partner firmalarin ogrencilerinin SURECINE hakim olmali: lead'leri
 * gormeli, takip etmeli, isi yurutmeli. Izolasyon YATAY'dir — firma firmayi,
 * bayi bayiyi, ogrenci ogrenciyi gormez. Yukari dogru kapalilik yoktur.
 *
 * Tenant cekirdegi bunu zaten kaldiriyor: TenantContext::visibleIds() tek id
 * degil bir KUME. Eksik olan tek sey, kumeye alt firmalarin eklenmesiydi.
 *
 * BILEREK BOS BIRAKILIYOR: mevcut sirketlere otomatik ust firma ATANMAZ.
 * Hangi sirketin MentorDE'nin altinda oldugu is karari; yanlis tahmin bir
 * firmanin verisini baskasina acardi. Platform panelinden secilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'parent_company_id')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_company_id')->nullable()->after('code');
            $table->index('parent_company_id', 'companies_parent_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'parent_company_id')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_parent_idx');
            $table->dropColumn('parent_company_id');
        });
    }
};
