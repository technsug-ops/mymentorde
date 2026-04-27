<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conditional belge kategorileri:
 * - tr_uni_student_cert: Türk üniversitesinde öğrenci olduğunu gösteren belge
 *   (higher_education_status='enrolled' iken talep edilir)
 * - dil_belgesi_de: Almanca dil sertifikası (german_certificate_held='yes' iken)
 * - dil_belgesi_en: İngilizce dil sertifikası (english_certificate_held='yes' iken)
 *
 * Bu kategoriler sadece guest'in Level 1 cevaplarına göre dinamik olarak
 * checklist'e eklenir; standart 6 belgeden ayrı.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('document_categories')) {
            return;
        }

        $rows = [
            [
                'code'              => 'tr_uni_student_cert',
                'name_tr'           => 'Türk Üniversitesi Öğrenci Belgesi',
                'name_de'           => 'Studienbescheinigung (türkische Universität)',
                'name_en'           => 'TR University Enrollment Certificate',
                'top_category_code' => 'egitim_belgeleri',
                'is_active'         => true,
                'sort_order'        => 130,
            ],
            [
                'code'              => 'dil_belgesi_de',
                'name_tr'           => 'Almanca Dil Sertifikası',
                'name_de'           => 'Deutsch-Sprachzertifikat',
                'name_en'           => 'German Language Certificate',
                'top_category_code' => 'dil_belgeleri',
                'is_active'         => true,
                'sort_order'        => 140,
            ],
            [
                'code'              => 'dil_belgesi_en',
                'name_tr'           => 'İngilizce Dil Sertifikası',
                'name_de'           => 'Englisch-Sprachzertifikat',
                'name_en'           => 'English Language Certificate',
                'top_category_code' => 'dil_belgeleri',
                'is_active'         => true,
                'sort_order'        => 150,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('document_categories')->where('code', $row['code'])->exists();
            if (!$exists) {
                $row['created_at'] = now();
                $row['updated_at'] = now();
                DB::table('document_categories')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_categories')) {
            return;
        }
        DB::table('document_categories')
            ->whereIn('code', ['tr_uni_student_cert', 'dil_belgesi_de', 'dil_belgesi_en'])
            ->delete();
    }
};
