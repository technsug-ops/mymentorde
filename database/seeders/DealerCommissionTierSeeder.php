<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DealerCommissionTier;
use Illuminate\Database\Seeder;

/**
 * Pazarlama (/satis-ortagi) sayfasındaki tier matrisi ile birebir:
 *  - 5 Lead Generation tier (Bronz €200 → Elmas €400)
 *  - 3 Freelance Danışman tier (Aktif €500 → Elit €750)
 *
 * Her company için seedlenir. Mevcut kayıtlar etkilenmez (firstOrCreate).
 */
class DealerCommissionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            // ── Lead Generation (5 kademe) ─────────────────────────────────
            ['tier_code' => 'lg_bronz',  'tier_name' => 'Bronz',  'tier_emoji' => '🥉', 'dealer_type_code' => 'lead_generation',  'min_count' => 1,   'max_count' => 10,  'commission_rate_eur' => 200, 'advantages_text' => 'Standart komisyon + Dealer Paneli erişimi',         'display_order' => 1],
            ['tier_code' => 'lg_gumus',  'tier_name' => 'Gümüş',  'tier_emoji' => '🥈', 'dealer_type_code' => 'lead_generation',  'min_count' => 11,  'max_count' => 25,  'commission_rate_eur' => 250, 'advantages_text' => 'Artırılmış komisyon + Öncelikli destek',             'display_order' => 2],
            ['tier_code' => 'lg_altin',  'tier_name' => 'Altın',  'tier_emoji' => '🥇', 'dealer_type_code' => 'lead_generation',  'min_count' => 26,  'max_count' => 50,  'commission_rate_eur' => 300, 'advantages_text' => 'Yüksek komisyon + Ortak pazarlama desteği',          'display_order' => 3],
            ['tier_code' => 'lg_platin', 'tier_name' => 'Platin', 'tier_emoji' => '💎', 'dealer_type_code' => 'lead_generation',  'min_count' => 51,  'max_count' => 100, 'commission_rate_eur' => 320, 'advantages_text' => 'Premium komisyon + Özel müşteri temsilcisi',          'display_order' => 4],
            ['tier_code' => 'lg_elmas',  'tier_name' => 'Elmas',  'tier_emoji' => '👑', 'dealer_type_code' => 'lead_generation',  'min_count' => 101, 'max_count' => null,'commission_rate_eur' => 400, 'advantages_text' => 'En yüksek komisyon + Stratejik ortaklık toplantıları', 'display_order' => 5],

            // ── Freelance Danışman (3 kademe) ──────────────────────────────
            ['tier_code' => 'fl_aktif',  'tier_name' => 'Aktif',  'tier_emoji' => '🚀', 'dealer_type_code' => 'freelance_danisman','min_count' => 1,   'max_count' => 15,  'commission_rate_eur' => 500, 'advantages_text' => 'Başlangıç komisyonu + Dealer Paneli + Temel süreç eğitimi', 'display_order' => 1],
            ['tier_code' => 'fl_uzman',  'tier_name' => 'Uzman',  'tier_emoji' => '⭐', 'dealer_type_code' => 'freelance_danisman','min_count' => 16,  'max_count' => 30,  'commission_rate_eur' => 600, 'advantages_text' => 'Artırılmış komisyon + Öncelikli operasyon/vize inceleme desteği', 'display_order' => 2],
            ['tier_code' => 'fl_elit',   'tier_name' => 'Elit',   'tier_emoji' => '🏆', 'dealer_type_code' => 'freelance_danisman','min_count' => 31,  'max_count' => null,'commission_rate_eur' => 750, 'advantages_text' => 'Yüksek komisyon + Co-branding ortak pazarlama desteği', 'display_order' => 3],
        ];

        $companies = Company::query()->select('id')->get();
        if ($companies->isEmpty()) {
            $this->command?->warn('Hiç company yok — DealerCommissionTier seedi atlandı.');
            return;
        }

        foreach ($companies as $company) {
            foreach ($tiers as $tier) {
                DealerCommissionTier::query()
                    ->updateOrCreate(
                        ['company_id' => $company->id, 'tier_code' => $tier['tier_code']],
                        $tier + ['is_active' => true]
                    );
            }
        }

        $this->command?->info('DealerCommissionTier: ' . (count($tiers) * $companies->count()) . ' kayıt seedlendi (' . $companies->count() . ' company × 8 tier).');
    }
}
