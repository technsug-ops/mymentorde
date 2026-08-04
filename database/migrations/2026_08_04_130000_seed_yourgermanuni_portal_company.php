<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * yourgermanuni.com için NÖTR PORTAL şirketi.
 *
 * Neden gerekli: marka `Host:` başlığından çözülür (SetCompanyContext). Bu domaini
 * hiçbir şirket sahiplenmediği sürece sistem varsayılan şirkete düşer ve partner
 * firmaların giriş sayfasında MentorDE adı/logosu görünür — canlıda böyleydi.
 *
 * Bu kayıt bir TENANT DEĞİL, yalnızca marka taşıyıcısıdır:
 *   • Kullanıcısı yok, verisi yok.
 *   • Anonim ziyaretçinin VERİ bağlamı yine varsayılan şirkettir; host yalnızca
 *     markayı belirler (bkz. SetCompanyContext::resolveCompanyByHost).
 *   • public_marketing = false → B2C kazanım içeriği (özellik listesi, "Ücretsiz
 *     Başvuru" CTA'ları) bu adreste gösterilmez.
 *   • Logo bilinçli olarak BOŞ — portal nötr çalışacak.
 *
 * Şirket oluştuktan sonra adı/rengi Platform → Şirketler ekranından düzenlenebilir.
 * Domaini zaten sahiplenen bir şirket varsa bu migration hiçbir şey yapmaz.
 */
return new class extends Migration
{
    private const HOST = 'yourgermanuni.com';
    private const CODE = 'yourgermanuni';

    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'primary_domain')) {
            return;
        }

        $claimed = DB::table('companies')
            ->whereRaw('lower(primary_domain) = ?', [self::HOST])
            ->exists();

        if ($claimed || DB::table('companies')->where('code', self::CODE)->exists()) {
            return;
        }

        $row = [
            'name'           => 'YourGermanUni',
            'code'           => self::CODE,
            'is_active'      => true,
            'primary_domain' => self::HOST,
            'brand_name'     => 'YourGermanUni',
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        if (Schema::hasColumn('companies', 'domain_aliases')) {
            $row['domain_aliases'] = json_encode(['www.' . self::HOST]);
        }

        if (Schema::hasColumn('companies', 'public_marketing')) {
            $row['public_marketing'] = false;
        }

        if (Schema::hasColumn('companies', 'slug')) {
            $row['slug'] = self::CODE;
        }

        // Modül dağıtımı yok: portal şirketinin kullanıcısı olmayacağı için
        // enabled_modules boş kalır (varsayılan davranış).
        DB::table('companies')->insert($row);
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        // Yalnızca bu migration'ın oluşturduğu, kullanıcısı olmayan kaydı sil.
        $company = DB::table('companies')->where('code', self::CODE)->first();

        if (!$company) {
            return;
        }

        if (Schema::hasTable('users') && DB::table('users')->where('company_id', $company->id)->exists()) {
            return;
        }

        DB::table('companies')->where('id', $company->id)->delete();
    }
};
