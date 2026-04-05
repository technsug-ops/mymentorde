<?php

namespace Database\Seeders;

use App\Models\DealerType;
use Illuminate\Database\Seeder;

class DealerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name_tr' => 'Yonlendirici Bayi',
                'name_de' => 'Vermittler',
                'name_en' => 'Referrer Dealer',
                'code' => 'referrer',
                'description_tr' => 'Sadece yonlendirme odakli bayi tipi.',
                'description_de' => 'Haendler mit Fokus auf Empfehlungen.',
                'description_en' => 'Dealer type focused on referrals only.',
                'permissions' => [
                    'canViewStudentDetails' => false,
                    'canViewDocuments' => false,
                    'canUploadDocuments' => false,
                    'canMessageStudent' => false,
                    'canViewProcessDetails' => false,
                    'canViewFinancials' => false,
                    'customPermissions' => [],
                ],
                'default_commission_config' => [
                    'type' => 'percentage',
                    'percentage' => 10,
                    'fixedAmount' => null,
                    'currency' => null,
                ],
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => 'system-seeder',
            ],
            [
                'name_tr' => 'Operasyonel Bayi',
                'name_de' => 'Operativer Partner',
                'name_en' => 'Operational Dealer',
                'code' => 'operational',
                'description_tr' => 'Surec ve belge takibine destek veren bayi tipi.',
                'description_de' => 'Partner fuer Prozess- und Dokumentenarbeit.',
                'description_en' => 'Dealer type supporting process and documents.',
                'permissions' => [
                    'canViewStudentDetails' => true,
                    'canViewDocuments' => true,
                    'canUploadDocuments' => true,
                    'canMessageStudent' => true,
                    'canViewProcessDetails' => true,
                    'canViewFinancials' => false,
                    'customPermissions' => [],
                ],
                'default_commission_config' => [
                    'type' => 'percentage',
                    'percentage' => 20,
                    'fixedAmount' => null,
                    'currency' => null,
                ],
                'is_active' => true,
                'sort_order' => 2,
                'created_by' => 'system-seeder',
            ],
        ];

        foreach ($items as $item) {
            DealerType::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
