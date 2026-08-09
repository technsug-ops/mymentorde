<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alan bazında başvuru türü etiketi.
 *
 * ── NEDEN AYRI FORM DEĞİL ───────────────────────────────────────────────
 * Master ve Ausbildung için ayrı form açmak ilk bakışta basit görünüyor ama
 * kopyalama sorununu ÜÇE katlıyor: merkezde bir alan değişince üç ayrı
 * tanımın da güncellenmesi gerekir, biri unutulduğunda fark edilmez.
 * Bu projede aynı hata form şablonunda zaten yaşandı — firma kendi
 * satırlarını edinince merkezden koptu ve kimse görmedi.
 *
 * Bunun yerine TEK merkezî tanım kalıyor; her alan hangi başvuru
 * türlerinde görüneceğini kendisi söylüyor.
 *
 * ── BOŞ = HEPSİ ─────────────────────────────────────────────────────────
 * `null` ya da boş dizi "her türde görünür" demek. Bu, sistemde zaten
 * yerleşik olan kalıp (bkz. FieldRule.applicable_student_types ve
 * FieldRuleEngine). Varsayılanın "hepsi" olması, kolon eklendiği anda
 * hiçbir alanın kaybolmamasını da garanti ediyor: etiketlemek bilinçli
 * bir eylem, yan etki değil.
 *
 * Değerler: bachelor | master | ausbildung
 * (bkz. StudentBridgeService::mapApplicationTypeToStudentTypeCode)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_registration_fields')) {
            return;
        }

        Schema::table('guest_registration_fields', function (Blueprint $table): void {
            if (! Schema::hasColumn('guest_registration_fields', 'applicable_types')) {
                $table->json('applicable_types')->nullable()->after('is_required');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('guest_registration_fields')) {
            return;
        }

        Schema::table('guest_registration_fields', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_registration_fields', 'applicable_types')) {
                $table->dropColumn('applicable_types');
            }
        });
    }
};
