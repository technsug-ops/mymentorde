<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('document_categories')) {
            $hasTopCategory = Schema::hasColumn('document_categories', 'top_category_code');

            foreach ($this->documentCategories() as $row) {
                $payload = [
                    'name_tr' => $row['name_tr'],
                    'name_de' => $row['name_de'],
                    'name_en' => $row['name_en'],
                    'is_active' => 1,
                    'sort_order' => $row['sort_order'],
                    'updated_at' => $now,
                ];

                if ($hasTopCategory) {
                    $payload['top_category_code'] = $row['top_category_code'];
                }

                DB::table('document_categories')->updateOrInsert(
                    ['code' => $row['code']],
                    $payload + ['created_at' => $now]
                );
            }
        }

        if (! Schema::hasTable('guest_required_documents')) {
            return;
        }

        $companyId = Schema::hasTable('companies')
            ? (int) DB::table('companies')->where('is_active', true)->orderBy('id')->value('id')
            : 0;
        $companyId = $companyId > 0 ? $companyId : null;

        foreach ($this->requiredDocsRows() as $row) {
            DB::table('guest_required_documents')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'application_type' => $row['application_type'],
                    'document_code' => $row['document_code'],
                ],
                $row + ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Seed verilerini geri almiyoruz (idempotent migration)
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function documentCategories(): array
    {
        return [
            // Basvuru Hazirlik / Uni-Assist
            ['code' => 'DOC-APOS', 'name_tr' => 'Apostilli Belge (Apostille)', 'name_de' => 'Apostille', 'name_en' => 'Apostille Document', 'top_category_code' => 'uni_assist_dokumanlari', 'sort_order' => 190],
            ['code' => 'DOC-BGLT', 'name_tr' => 'Yeminli Tercume', 'name_de' => 'Beglaubigte Ubersetzung', 'name_en' => 'Certified Translation', 'top_category_code' => 'uni_assist_dokumanlari', 'sort_order' => 200],
            ['code' => 'DOC-VPRF', 'name_tr' => 'On Inceleme Belgesi (VPD)', 'name_de' => 'Vorprufungsdokument (VPD)', 'name_en' => 'VPD Document', 'top_category_code' => 'uni_assist_dokumanlari', 'sort_order' => 210],

            // Vize
            ['code' => 'DOC-NFUS', 'name_tr' => 'Tam Tekmil Nufus Kayit Ornegi', 'name_de' => 'Auszug aus dem Personenstandsregister', 'name_en' => 'Population Registry Extract', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 220],
            ['code' => 'DOC-VERP', 'name_tr' => 'Garantor Belgesi', 'name_de' => 'Verpflichtungserklarung', 'name_en' => 'Sponsorship/Guarantee Letter', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 230],
            ['code' => 'DOC-RKRV', 'name_tr' => 'Seyahat Saglik Sigortasi', 'name_de' => 'Reisekrankenversicherung', 'name_en' => 'Travel Health Insurance', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 240],
            ['code' => 'DOC-FLUG', 'name_tr' => 'Ucus Rezervasyonu', 'name_de' => 'Flugreservierung', 'name_en' => 'Flight Reservation', 'top_category_code' => 'vize_dokumanlari', 'sort_order' => 250],

            // Dil Kursu
            ['code' => 'DOC-SPRK', 'name_tr' => 'Dil Kursu Kayit Belgesi', 'name_de' => 'Sprachkursbescheinigung', 'name_en' => 'Language Course Registration', 'top_category_code' => 'dil_okulu_dokumanlari', 'sort_order' => 260],

            // Ikamet
            ['code' => 'DOC-WGBE', 'name_tr' => 'Ev Sahibi Onay Belgesi', 'name_de' => 'Wohnungsgeberbestatigung', 'name_en' => 'Landlord Confirmation', 'top_category_code' => 'ikamet_kaydi_dokumanlari', 'sort_order' => 270],

            // Almanya Burokrasi
            ['code' => 'DOC-AT11', 'name_tr' => 'AT/11 Saglik Sigortasi Belgesi', 'name_de' => 'Anspruchsnachweis AT/11', 'name_en' => 'AT/11 Entitlement Certificate', 'top_category_code' => 'almanya_burokrasi_dokumanlari', 'sort_order' => 280],
            ['code' => 'DOC-SEMB', 'name_tr' => 'Donem Harci Dekontu', 'name_de' => 'Zahlungsbeleg fur Semesterbeitrag', 'name_en' => 'Semester Fee Payment Receipt', 'top_category_code' => 'almanya_burokrasi_dokumanlari', 'sort_order' => 290],
            ['code' => 'DOC-IMMA', 'name_tr' => 'Ogrenci Belgesi (Immatrikulation)', 'name_de' => 'Immatrikulationsbescheinigung', 'name_en' => 'Certificate of Enrollment', 'top_category_code' => 'almanya_burokrasi_dokumanlari', 'sort_order' => 300],

            // Ozel durum / istisnai
            ['code' => 'DOC-MAPP', 'name_tr' => 'Portfolyo (Mappe)', 'name_de' => 'Mappe / Portfolio', 'name_en' => 'Portfolio', 'top_category_code' => 'diger_dokumanlar', 'sort_order' => 310],
            ['code' => 'DOC-TSAS', 'name_tr' => 'TestAS / GMAT / GRE Sonuclari', 'name_de' => 'TestAS / GMAT / GRE Ergebnisse', 'name_en' => 'TestAS / GMAT / GRE Results', 'top_category_code' => 'diger_dokumanlar', 'sort_order' => 320],
            ['code' => 'DOC-ARBZ', 'name_tr' => 'Is Deneyimi Belgesi', 'name_de' => 'Arbeitszeugnis', 'name_en' => 'Work Experience Certificate', 'top_category_code' => 'diger_dokumanlar', 'sort_order' => 330],
            ['code' => 'DOC-HEIR', 'name_tr' => 'Evlenme Cuzdani', 'name_de' => 'Heiratsurkunde', 'name_en' => 'Marriage Certificate', 'top_category_code' => 'diger_dokumanlar', 'sort_order' => 340],
            ['code' => 'DOC-EXMA', 'name_tr' => 'Universite Kayit Silme Belgesi', 'name_de' => 'Exmatrikulationsbescheinigung', 'name_en' => 'Exmatriculation Certificate', 'top_category_code' => 'diger_dokumanlar', 'sort_order' => 350],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function requiredDocsRows(): array
    {
        $rows = [];

        foreach (['bachelor', 'master', 'dil_kursu'] as $applicationType) {
            $rows = array_merge($rows, $this->docsForApplicationType($applicationType));
        }

        return $rows;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function docsForApplicationType(string $applicationType): array
    {
        $base = [
            ['code' => 'DOC-APOS', 'name' => 'Apostilli Belgeler (Apostille)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 190],
            ['code' => 'DOC-BGLT', 'name' => 'Yeminli Tercume (Beglaubigte Ubersetzung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 15, 'sort' => 200],
            ['code' => 'DOC-VPRF', 'name' => 'On Inceleme Belgesi (VPD)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 210],
            ['code' => 'DOC-NFUS', 'name' => 'Tam Tekmil Nufus Kayit Ornegi', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 220],
            ['code' => 'DOC-VERP', 'name' => 'Garantor Belgesi (Verpflichtungserklarung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 230],
            ['code' => 'DOC-RKRV', 'name' => 'Seyahat Saglik Sigortasi', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 240],
            ['code' => 'DOC-FLUG', 'name' => 'Ucus Rezervasyonu (Flugreservierung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 250],
            ['code' => 'DOC-SPRK', 'name' => 'Dil Kursu Kayit Belgesi (Sprachkursbescheinigung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 260],
            ['code' => 'DOC-WGBE', 'name' => 'Ev Sahibi Onay Belgesi (Wohnungsgeberbestatigung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 270],
            ['code' => 'DOC-AT11', 'name' => 'AT/11 Saglik Sigortasi Belgesi', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 280],
            ['code' => 'DOC-SEMB', 'name' => 'Donem Harci Dekontu', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 290],
            ['code' => 'DOC-IMMA', 'name' => 'Ogrenci Belgesi (Immatrikulationsbescheinigung)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 300],
            ['code' => 'DOC-MAPP', 'name' => 'Portfolyo (Mappe)', 'accepted' => 'pdf,jpg,png,zip', 'max_mb' => 25, 'sort' => 310],
            ['code' => 'DOC-TSAS', 'name' => 'TestAS / GMAT / GRE Sonuclari', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 320],
            ['code' => 'DOC-ARBZ', 'name' => 'Is Deneyimi Belgesi (Arbeitszeugnis)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 330],
            ['code' => 'DOC-HEIR', 'name' => 'Evlenme Cuzdani (Heiratsurkunde)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 340],
            ['code' => 'DOC-EXMA', 'name' => 'Universite Kayit Silme Belgesi (Exmatrikulation)', 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort' => 350],
        ];

        $hasStageColumn = Schema::hasColumn('guest_required_documents', 'stage');

        return array_map(function (array $doc) use ($applicationType, $hasStageColumn): array {
            $row = [
                'application_type' => $applicationType,
                'document_code' => $doc['code'],
                'category_code' => $doc['code'],
                'name' => $doc['name'],
                'description' => 'Ogrenci surecinde yuklenebilir (varsayilan: opsiyonel).',
                'is_required' => 0,
                'accepted' => $doc['accepted'],
                'max_mb' => $doc['max_mb'],
                'sort_order' => $doc['sort'],
                'is_active' => 1,
            ];
            if ($hasStageColumn) {
                $row['stage'] = 'student';
            }
            return $row;
        }, $base);
    }
};
