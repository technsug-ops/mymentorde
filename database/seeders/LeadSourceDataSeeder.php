<?php

namespace Database\Seeders;

use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use Illuminate\Database\Seeder;

class LeadSourceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaignId = MarketingCampaign::query()->value('id');

        $items = [
            ['guest_id' => 'GST100001', 'initial_source' => 'instagram', 'verified_source' => 'instagram'],
            ['guest_id' => 'GST100002', 'initial_source' => 'google_ads', 'verified_source' => 'google_ads'],
            ['guest_id' => 'GST100003', 'initial_source' => 'dealer_ref', 'verified_source' => null],
            ['guest_id' => 'GST100004', 'initial_source' => 'web_organic', 'verified_source' => 'web_organic'],
            ['guest_id' => 'GST100005', 'initial_source' => 'tiktok', 'verified_source' => null],
        ];

        foreach ($items as $item) {
            LeadSourceDatum::updateOrCreate(
                ['guest_id' => $item['guest_id']],
                [
                    'initial_source' => $item['initial_source'],
                    'verified_source' => $item['verified_source'],
                    'source_detail' => null,
                    'campaign_id' => $campaignId,
                    'utm_params' => [
                        'utm_source' => $item['initial_source'],
                        'utm_medium' => 'cpc',
                        'utm_campaign' => 'seed-campaign',
                    ],
                ]
            );
        }
    }
}
