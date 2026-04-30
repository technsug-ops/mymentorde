<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vize kategorisine 3 eksik belge ekler:
 *  - DOC-APS_ : APS Sertifikası (Türk lisans öğrencileri için zorunlu)
 *  - DOC-IDAT : iDATA başvuru dekontu / randevu kanıtı
 *  - DOC-FOTO : Biyometrik vesikalık fotoğraf (vize için ayrı kayıt — uni_kayit'ta var ama vize'de yoktu)
 *
 * Composite unique key: (company_id, application_type, stage, top_category_code, document_code)
 * O yüzden DOC-FOTO'yu top_category_code='vize' ile yeni kayıt olarak ekleyebiliriz.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1) document_categories tablosuna yeni 2 kod (FOTO zaten var)
        if (Schema::hasTable('document_categories')) {
            $hasTopCat = Schema::hasColumn('document_categories', 'top_category_code');

            $newCategories = [
                ['code' => 'DOC-APS_', 'name_tr' => 'APS Sertifikası', 'name_de' => 'APS-Zertifikat', 'name_en' => 'APS Certificate', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 360],
                ['code' => 'DOC-IDAT', 'name_tr' => 'iDATA Başvuru Dekontu / Randevu Kanıtı', 'name_de' => 'iDATA Antragsbeleg / Termin', 'name_en' => 'iDATA Application Receipt / Appointment', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 370],
            ];

            foreach ($newCategories as $row) {
                $payload = [
                    'name_tr'    => $row['name_tr'],
                    'name_de'    => $row['name_de'],
                    'name_en'    => $row['name_en'],
                    'is_active'  => 1,
                    'sort_order' => $row['sort_order'],
                    'updated_at' => $now,
                ];
                if ($hasTopCat) {
                    $payload['top_category_code'] = $row['top_category_code'];
                }
                DB::table('document_categories')->updateOrInsert(
                    ['code' => $row['code']],
                    $payload + ['created_at' => $now]
                );
            }
        }

        // 2) guest_required_documents — vize kategorisine 3 belge × her application_type
        if (! Schema::hasTable('guest_required_documents')) {
            return;
        }

        $companyId = Schema::hasTable('companies')
            ? (int) DB::table('companies')->where('is_active', true)->orderBy('id')->value('id')
            : 0;
        $companyId = $companyId > 0 ? $companyId : null;

        $hasStage  = Schema::hasColumn('guest_required_documents', 'stage');
        $hasTopCat = Schema::hasColumn('guest_required_documents', 'top_category_code');

        $newDocs = [
            ['code' => 'DOC-APS_', 'name' => 'APS Sertifikası',                              'desc' => 'Türk lisans öğrencileri için zorunlu akademik denklik belgesi (APS).', 'sort' => 360],
            ['code' => 'DOC-IDAT', 'name' => 'iDATA Başvuru Dekontu / Randevu Kanıtı',       'desc' => 'iDATA üzerinden alınan vize randevusu / dekont belgesi.',             'sort' => 370],
            ['code' => 'DOC-FOTO', 'name' => 'Biyometrik Vesikalık Fotoğraf (Vize için)',    'desc' => 'Schengen vize standartlarına uygun 35×45mm biyometrik fotoğraf.',     'sort' => 380],
        ];

        foreach (['bachelor', 'master', 'dil_kursu'] as $applicationType) {
            foreach ($newDocs as $doc) {
                $row = [
                    'application_type' => $applicationType,
                    'document_code'    => $doc['code'],
                    'category_code'    => $doc['code'],
                    'name'             => $doc['name'],
                    'description'      => $doc['desc'],
                    'is_required'      => 0, // opsiyonel — manager talep eder
                    'accepted'         => 'pdf,jpg,png',
                    'max_mb'           => 10,
                    'sort_order'       => $doc['sort'],
                    'is_active'        => 1,
                    'updated_at'       => $now,
                    'created_at'       => $now,
                ];
                if ($hasStage)  $row['stage'] = 'student';
                if ($hasTopCat) $row['top_category_code'] = 'vize';

                $key = [
                    'company_id'        => $companyId,
                    'application_type'  => $applicationType,
                    'document_code'     => $doc['code'],
                ];
                if ($hasStage)  $key['stage'] = 'student';
                if ($hasTopCat) $key['top_category_code'] = 'vize';

                DB::table('guest_required_documents')->updateOrInsert($key, $row);
            }
        }
    }

    public function down(): void
    {
        // Idempotent — geri almıyoruz
    }
};
