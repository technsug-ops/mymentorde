<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingExternalMetric;
use Illuminate\Console\Command;

class SeedMulticompanyDemoCommand extends Command
{
    protected $signature = 'demo:seed-multicompany {--reset-acme : Delete ACME Edu demo data before seeding}';
    protected $description = 'Seed demo multi-company marketing data to verify company switching';

    public function handle(): int
    {
        $resetAcme = (bool) $this->option('reset-acme');

        $mentor = Company::query()->firstOrCreate(['code' => 'mentorde'], ['name' => 'MentorDE', 'is_active' => true]);
        $acme   = Company::query()->firstOrCreate(['code' => 'acmeedu'],  ['name' => 'ACME Edu', 'is_active' => true]);

        if ($resetAcme) {
            MarketingExternalMetric::query()->where('company_id', $acme->id)->delete();
            LeadSourceDatum::query()->where('company_id', $acme->id)->delete();
            MarketingCampaign::query()->where('company_id', $acme->id)->delete();
        }

        $mentorCampaign = MarketingCampaign::query()->updateOrCreate(
            ['company_id' => $mentor->id, 'name' => 'MentorDE Demo Campaign'],
            ['description' => 'Demo campaign for multi-company comparison', 'channel' => 'google_ads', 'budget' => 1800, 'spent_amount' => 1400, 'currency' => 'EUR', 'status' => 'active', 'utm_params' => ['utm_campaign' => 'mentor_demo_2026'], 'metrics' => ['impressions' => 18000, 'clicks' => 920], 'created_by' => 'system:demo_seed']
        );

        $acmeCampaign = MarketingCampaign::query()->updateOrCreate(
            ['company_id' => $acme->id, 'name' => 'ACME Winter Push'],
            ['description' => 'ACME paid social + search launch', 'channel' => 'instagram_ads', 'budget' => 5200, 'spent_amount' => 4100, 'currency' => 'EUR', 'status' => 'active', 'utm_params' => ['utm_campaign' => 'acme_winter_2026'], 'metrics' => ['impressions' => 64000, 'clicks' => 3300], 'created_by' => 'system:demo_seed']
        );

        foreach ([
            ['guest_id' => 'MDEMO-001', 'source' => 'google',    'utm_campaign' => 'mentor_demo_2026', 'converted' => true],
            ['guest_id' => 'MDEMO-002', 'source' => 'instagram', 'utm_campaign' => 'mentor_demo_2026', 'converted' => false],
            ['guest_id' => 'MDEMO-003', 'source' => 'organic',   'utm_campaign' => 'mentor_blog_2026', 'converted' => false],
        ] as $row) {
            LeadSourceDatum::query()->updateOrCreate(
                ['company_id' => $mentor->id, 'guest_id' => $row['guest_id']],
                ['initial_source' => $row['source'], 'verified_source' => $row['converted'] ? $row['source'] : null, 'campaign_id' => $mentorCampaign->id, 'utm_source' => $row['source'], 'utm_medium' => $row['source'] === 'organic' ? 'organic' : 'paid', 'utm_campaign' => $row['utm_campaign'], 'funnel_registered' => true, 'funnel_form_completed' => true, 'funnel_converted' => (bool) $row['converted'], 'funnel_converted_at' => $row['converted'] ? now()->subDays(2) : null]
            );
        }

        foreach ([
            ['guest_id' => 'ACME-001', 'source' => 'instagram', 'utm_campaign' => 'acme_winter_2026', 'converted' => true],
            ['guest_id' => 'ACME-002', 'source' => 'instagram', 'utm_campaign' => 'acme_winter_2026', 'converted' => true],
            ['guest_id' => 'ACME-003', 'source' => 'google',    'utm_campaign' => 'acme_search_2026', 'converted' => false],
            ['guest_id' => 'ACME-004', 'source' => 'google',    'utm_campaign' => 'acme_search_2026', 'converted' => false],
            ['guest_id' => 'ACME-005', 'source' => 'tiktok',    'utm_campaign' => 'acme_tiktok_2026', 'converted' => true],
            ['guest_id' => 'ACME-006', 'source' => 'organic',   'utm_campaign' => 'acme_blog_2026',   'converted' => false],
        ] as $row) {
            LeadSourceDatum::query()->updateOrCreate(
                ['company_id' => $acme->id, 'guest_id' => $row['guest_id']],
                ['initial_source' => $row['source'], 'verified_source' => $row['converted'] ? $row['source'] : null, 'campaign_id' => $acmeCampaign->id, 'utm_source' => $row['source'], 'utm_medium' => $row['source'] === 'organic' ? 'organic' : 'paid', 'utm_campaign' => $row['utm_campaign'], 'funnel_registered' => true, 'funnel_form_completed' => true, 'funnel_converted' => (bool) $row['converted'], 'funnel_converted_at' => $row['converted'] ? now()->subDays(1) : null]
            );
        }

        foreach ([
            ['provider' => 'meta',       'campaign_key' => 'acme-meta-01', 'campaign_name' => 'ACME Meta Reels',         'source' => 'meta',      'medium' => 'paid_social', 'impressions' => 42000, 'clicks' => 2100, 'spend' => 1700, 'leads' => 320, 'conversions' => 42],
            ['provider' => 'google_ads', 'campaign_key' => 'acme-gads-01', 'campaign_name' => 'ACME Search DE',           'source' => 'google',    'medium' => 'cpc',         'impressions' => 51000, 'clicks' => 2800, 'spend' => 2300, 'leads' => 480, 'conversions' => 51],
            ['provider' => 'ga4',        'campaign_key' => 'acme-ga4-01',  'campaign_name' => 'ACME Site Attribution',    'source' => 'instagram', 'medium' => 'paid_social', 'impressions' => 0,     'clicks' => 0,    'spend' => 0,    'leads' => 950, 'conversions' => 66],
        ] as $idx => $row) {
            $metricDate = now()->subDays($idx)->toDateString();
            $hash = hash('sha256', implode('|', [$acme->id, $row['provider'], $metricDate, $row['campaign_key'], $row['source'], $row['medium']]));
            MarketingExternalMetric::query()->updateOrCreate(
                ['row_hash' => $hash],
                ['company_id' => $acme->id, 'provider' => $row['provider'], 'account_ref' => 'demo-' . $row['provider'], 'metric_date' => $metricDate, 'campaign_key' => $row['campaign_key'], 'campaign_name' => $row['campaign_name'], 'source' => $row['source'], 'medium' => $row['medium'], 'impressions' => $row['impressions'], 'clicks' => $row['clicks'], 'spend' => $row['spend'], 'leads' => $row['leads'], 'conversions' => $row['conversions'], 'raw_payload' => ['demo' => true], 'synced_at' => now()]
            );
        }

        $this->info('demo:seed-multicompany tamamlandi');
        $this->line("MentorDE (#{$mentor->id}) leads: " . LeadSourceDatum::query()->where('company_id', $mentor->id)->count() . " | campaigns: " . MarketingCampaign::query()->where('company_id', $mentor->id)->count() . " | external rows: " . MarketingExternalMetric::query()->where('company_id', $mentor->id)->count());
        $this->line("ACME Edu (#{$acme->id}) leads: " . LeadSourceDatum::query()->where('company_id', $acme->id)->count() . " | campaigns: " . MarketingCampaign::query()->where('company_id', $acme->id)->count() . " | external rows: " . MarketingExternalMetric::query()->where('company_id', $acme->id)->count());

        return 0;
    }
}
