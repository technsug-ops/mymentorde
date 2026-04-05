<?php

namespace Database\Seeders;

use App\Models\StudentType;
use Illuminate\Database\Seeder;

class StudentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name_tr' => 'Bachelor (Lisans)',
                'name_de' => 'Bachelor',
                'name_en' => 'Bachelor',
                'code' => 'bachelor',
                'id_prefix' => 'BCS',
                'description_tr' => 'Lisans öğrencileri için varsayılan akış.',
                'description_de' => 'Standardablauf fuer Bachelor-Studierende.',
                'description_en' => 'Default flow for bachelor students.',
                'applicable_processes' => ['PROC-001', 'PROC-002', 'PROC-003', 'PROC-004', 'PROC-005', 'PROC-006'],
                'required_document_categories' => ['passport', 'transcript', 'diploma'],
                'default_checklist_template_id' => null,
                'field_rules' => [],
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => 'system-seeder',
            ],
            [
                'name_tr' => 'Master (Yuksek Lisans)',
                'name_de' => 'Master',
                'name_en' => 'Master',
                'code' => 'master',
                'id_prefix' => 'MST',
                'description_tr' => 'Yuksek lisans ogrencileri icin akis.',
                'description_de' => 'Ablauf fuer Master-Studierende.',
                'description_en' => 'Flow for master students.',
                'applicable_processes' => ['PROC-001', 'PROC-002', 'PROC-003', 'PROC-005', 'PROC-006'],
                'required_document_categories' => ['passport', 'transcript', 'diploma'],
                'default_checklist_template_id' => null,
                'field_rules' => [],
                'is_active' => true,
                'sort_order' => 2,
                'created_by' => 'system-seeder',
            ],
            [
                'name_tr' => 'Ausbildung',
                'name_de' => 'Ausbildung',
                'name_en' => 'Ausbildung',
                'code' => 'ausbildung',
                'id_prefix' => 'AUS',
                'description_tr' => 'Mesleki egitim odakli ogrenci tipi.',
                'description_de' => 'Studierendentyp fuer Berufsausbildung.',
                'description_en' => 'Student type focused on vocational training.',
                'applicable_processes' => ['PROC-001', 'PROC-003', 'PROC-005', 'PROC-006'],
                'required_document_categories' => ['passport', 'language_certificate'],
                'default_checklist_template_id' => null,
                'field_rules' => [],
                'is_active' => true,
                'sort_order' => 3,
                'created_by' => 'system-seeder',
            ],
        ];

        foreach ($items as $item) {
            StudentType::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
