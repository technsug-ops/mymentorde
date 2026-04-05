<?php

namespace Database\Seeders;

use App\Models\RevenueMilestone;
use Illuminate\Database\Seeder;

class RevenueMilestoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'external_id' => 'REV-001',
                'name_tr' => 'Kayit',
                'name_de' => 'Anmeldung',
                'name_en' => 'Registration',
                'description_tr' => 'Guest to Student donusumu + sozlesme.',
                'description_de' => 'Lead zu Student + Vertrag.',
                'description_en' => 'Guest to student conversion + contract.',
                'trigger_type' => 'manual',
                'trigger_condition' => [],
                'revenue_type' => 'fixed',
                'percentage' => null,
                'fixed_amount' => 500,
                'fixed_currency' => 'EUR',
                'sort_order' => 1,
                'is_active' => true,
                'is_required' => true,
                'created_by' => 'system-seeder',
            ],
            [
                'external_id' => 'REV-002',
                'name_tr' => 'Universite Kabul',
                'name_de' => 'Universitaetszulassung',
                'name_en' => 'University Acceptance',
                'description_tr' => 'Kabul belgesi onayi.',
                'description_de' => 'Freigabe des Zulassungsdokuments.',
                'description_en' => 'Acceptance document approval.',
                'trigger_type' => 'document_approved',
                'trigger_condition' => ['field' => 'documentCategory', 'value' => 'acceptance_letter'],
                'revenue_type' => 'percentage',
                'percentage' => 30,
                'fixed_amount' => null,
                'fixed_currency' => null,
                'sort_order' => 2,
                'is_active' => true,
                'is_required' => true,
                'created_by' => 'system-seeder',
            ],
            [
                'external_id' => 'REV-003',
                'name_tr' => 'Vize Onay',
                'name_de' => 'Visagenehmigung',
                'name_en' => 'Visa Approval',
                'description_tr' => 'Vize onay belgesi.',
                'description_de' => 'Visa-Freigabe Dokument.',
                'description_en' => 'Visa approval document.',
                'trigger_type' => 'document_approved',
                'trigger_condition' => ['field' => 'documentCategory', 'value' => 'visa_approval'],
                'revenue_type' => 'percentage',
                'percentage' => 25,
                'fixed_amount' => null,
                'fixed_currency' => null,
                'sort_order' => 3,
                'is_active' => true,
                'is_required' => true,
                'created_by' => 'system-seeder',
            ],
        ];

        foreach ($items as $item) {
            RevenueMilestone::updateOrCreate(
                ['external_id' => $item['external_id']],
                $item
            );
        }
    }
}
