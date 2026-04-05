<?php

namespace Database\Seeders;

use App\Models\DealerRevenueMilestone;
use Illuminate\Database\Seeder;

class DealerRevenueMilestoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'external_id' => 'DLR-REV-001',
                'name_tr' => 'Kayit Yonu',
                'name_de' => 'Registrierung',
                'name_en' => 'Registration',
                'trigger_type' => 'manual',
                'trigger_condition' => [],
                'revenue_type' => 'percentage',
                'percentage' => 10,
                'fixed_amount' => null,
                'fixed_currency' => null,
                'applicable_dealer_types' => ['referrer', 'operational'],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'external_id' => 'DLR-REV-002',
                'name_tr' => 'Vize Sonrasi',
                'name_de' => 'Nach Visum',
                'name_en' => 'After Visa',
                'trigger_type' => 'document_approved',
                'trigger_condition' => ['field' => 'documentCategory', 'value' => 'visa_approval'],
                'revenue_type' => 'fixed',
                'percentage' => null,
                'fixed_amount' => 200,
                'fixed_currency' => 'EUR',
                'applicable_dealer_types' => ['operational'],
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            DealerRevenueMilestone::updateOrCreate(
                ['external_id' => $item['external_id']],
                $item
            );
        }
    }
}
