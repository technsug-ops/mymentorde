<?php

namespace Database\Seeders;

use App\Models\ProcessDefinition;
use Illuminate\Database\Seeder;

class ProcessDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'external_id' => 'PROC-001',
                'code' => 'application_prep',
                'name_tr' => 'Basvuru Hazirlik',
                'name_de' => 'Bewerbungsvorbereitung',
                'name_en' => 'Application Preparation',
                'description_tr' => 'Belge toplama ve basvuru on hazirligi.',
                'description_de' => 'Sammlung der Unterlagen und Vorbereitung.',
                'description_en' => 'Document collection and pre-application prep.',
                'sort_order' => 1,
                'is_active' => true,
                'is_mandatory' => true,
                'applicable_student_types' => ['bachelor', 'master', 'ausbildung'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#1D4ED8',
                'icon' => 'clipboard-list',
                'updated_by' => 'system-seeder',
            ],
            [
                'external_id' => 'PROC-002',
                'code' => 'uni_assist',
                'name_tr' => 'Uni-Assist Sureci',
                'name_de' => 'Uni-Assist Prozess',
                'name_en' => 'Uni-Assist Process',
                'description_tr' => 'Uni-assist ve dogrudan universite basvurulari.',
                'description_de' => 'Uni-Assist und direkte Hochschulbewerbungen.',
                'description_en' => 'Uni-assist and direct university applications.',
                'sort_order' => 2,
                'is_active' => true,
                'is_mandatory' => true,
                'applicable_student_types' => ['bachelor', 'master'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#2563EB',
                'icon' => 'building-library',
                'updated_by' => 'system-seeder',
            ],
            [
                'external_id' => 'PROC-003',
                'code' => 'visa_application',
                'name_tr' => 'Vize Basvuru Sureci',
                'name_de' => 'Visumantrag',
                'name_en' => 'Visa Application Process',
                'description_tr' => 'Vize randevusu ve basvuru adimlari.',
                'description_de' => 'Termin und Schritte fuer den Visumantrag.',
                'description_en' => 'Visa appointment and application steps.',
                'sort_order' => 3,
                'is_active' => true,
                'is_mandatory' => true,
                'applicable_student_types' => ['bachelor', 'master', 'ausbildung'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#0EA5E9',
                'icon' => 'passport',
                'updated_by' => 'system-seeder',
            ],
            [
                'external_id' => 'PROC-004',
                'code' => 'language_course',
                'name_tr' => 'Dil Kursu',
                'name_de' => 'Sprachkurs',
                'name_en' => 'Language Course',
                'description_tr' => 'Sartli kabul durumlarinda dil kursu takibi.',
                'description_de' => 'Sprachkurs bei bedingter Zulassung.',
                'description_en' => 'Language course tracking for conditional offers.',
                'sort_order' => 4,
                'is_active' => true,
                'is_mandatory' => false,
                'applicable_student_types' => ['bachelor', 'ausbildung'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#14B8A6',
                'icon' => 'language',
                'updated_by' => 'system-seeder',
            ],
            [
                'external_id' => 'PROC-005',
                'code' => 'residence',
                'name_tr' => 'Ikamet Yeri',
                'name_de' => 'Unterkunft',
                'name_en' => 'Residence',
                'description_tr' => 'Konaklama ve yerlesim sureci.',
                'description_de' => 'Unterkunfts- und Wohnungsprozess.',
                'description_en' => 'Accommodation and settlement process.',
                'sort_order' => 5,
                'is_active' => true,
                'is_mandatory' => true,
                'applicable_student_types' => ['bachelor', 'master', 'ausbildung'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#22C55E',
                'icon' => 'home',
                'updated_by' => 'system-seeder',
            ],
            [
                'external_id' => 'PROC-006',
                'code' => 'official_services',
                'name_tr' => 'Almanya Resmi Hizmetler',
                'name_de' => 'Amtliche Dienste in Deutschland',
                'name_en' => 'Official Services in Germany',
                'description_tr' => 'Banka, anmeldung ve resmi kurum islemleri.',
                'description_de' => 'Bank, Anmeldung und Behoerdenvorgaenge.',
                'description_en' => 'Bank, registration, and official procedures.',
                'sort_order' => 6,
                'is_active' => true,
                'is_mandatory' => true,
                'applicable_student_types' => ['bachelor', 'master', 'ausbildung'],
                'default_checklist' => [],
                'revenue_milestone_id' => null,
                'color' => '#84CC16',
                'icon' => 'building-office-2',
                'updated_by' => 'system-seeder',
            ],
        ];

        foreach ($items as $item) {
            ProcessDefinition::updateOrCreate(
                ['external_id' => $item['external_id']],
                $item
            );
        }
    }
}
