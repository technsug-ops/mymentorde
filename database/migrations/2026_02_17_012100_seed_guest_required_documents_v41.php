<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_required_documents')) {
            return;
        }

        $companyId = Schema::hasTable('companies')
            ? (int) DB::table('companies')->where('is_active', true)->orderBy('id')->value('id')
            : 0;
        $companyId = $companyId > 0 ? $companyId : null;
        $now = now();

        $rows = array_merge(
            $this->docsFor('bachelor', false),
            $this->docsFor('master', true),
            $this->languageCourseDocs()
        );

        foreach ($rows as $row) {
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
        // keep seed
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function docsFor(string $type, bool $master): array
    {
        return [
            ['application_type' => $type, 'document_code' => 'DOC-DIPL', 'category_code' => 'DOC-DIPL', 'name' => $master ? 'Universite Diplomasi + yeminli tercume' : 'Lise Diplomasi + Almanca yeminli tercume', 'description' => $type.' icin zorunlu', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 10, 'is_active' => 1],
            ['application_type' => $type, 'document_code' => 'DOC-TRNS', 'category_code' => 'DOC-TRNS', 'name' => $master ? 'Universite Transkript + yeminli tercume' : 'Transkript + Almanca yeminli tercume', 'description' => $type.' icin zorunlu', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 20, 'is_active' => 1],
            ['application_type' => $type, 'document_code' => 'DOC-UNWN', 'category_code' => 'DOC-UNWN', 'name' => $master ? 'Universite Kabul/Referans Belgesi' : 'Universite Kazandi Belgesi + tercume', 'description' => $type.' icin zorunlu', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 30, 'is_active' => 1],
            ['application_type' => $type, 'document_code' => 'DOC-YKSP', 'category_code' => 'DOC-YKSP', 'name' => $master ? 'Ek Akademik Yerlestirme Belgesi' : 'YKS Yerlestirme Belgesi', 'description' => $type.' icin zorunlu', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 40, 'is_active' => 1],
            ['application_type' => $type, 'document_code' => 'DOC-IDCR', 'category_code' => 'DOC-IDCR', 'name' => 'Kimlik On-Arka Fotografi', 'description' => 'JPG/PNG, max 5MB', 'is_required' => 1, 'accepted' => 'jpg,png', 'max_mb' => 5, 'sort_order' => 50, 'is_active' => 1],
            ['application_type' => $type, 'document_code' => 'DOC-PASS', 'category_code' => 'DOC-PASS', 'name' => 'Pasaport Ilk 2 Sayfa', 'description' => 'PDF/JPG, max 10MB', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 60, 'is_active' => 1],
            // DOC-CV__ ve DOC-MOTV guest asamasinda istenmez (student kabulu sonrasinda talep edilir)
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function languageCourseDocs(): array
    {
        return [
            ['application_type' => 'dil_kursu', 'document_code' => 'DOC-PASS', 'category_code' => 'DOC-PASS', 'name' => 'Pasaport Ilk 2 Sayfa', 'description' => 'Dil kursu icin zorunlu', 'is_required' => 1, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10, 'sort_order' => 10, 'is_active' => 1],
            ['application_type' => 'dil_kursu', 'document_code' => 'DOC-IDCR', 'category_code' => 'DOC-IDCR', 'name' => 'Kimlik On-Arka Fotografi', 'description' => 'JPG/PNG, max 5MB', 'is_required' => 1, 'accepted' => 'jpg,png', 'max_mb' => 5, 'sort_order' => 20, 'is_active' => 1],
            // DOC-CV__ ve DOC-MOTV guest asamasinda istenmez (student kabulu sonrasinda talep edilir)
        ];
    }
};

