<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailCampaignJob;
use App\Models\Marketing\EmailCampaign;
use Illuminate\Console\Command;

class DispatchScheduledEmailCampaignsCommand extends Command
{
    protected $signature   = 'email:dispatch-scheduled {--limit=20 : Her çalıştırmada max gönderilecek kampanya sayısı}';
    protected $description = 'Zamanı gelmiş scheduled email kampanyalarını kuyruğa al';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $campaigns = EmailCampaign::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->limit($limit)
            ->get(['id', 'name']);

        if ($campaigns->isEmpty()) {
            $this->line('Gönderilecek zamanlanmış kampanya yok.');
            return Command::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            SendEmailCampaignJob::dispatch((int) $campaign->id);
            $this->info("Kampanya #{$campaign->id} ({$campaign->name}) kuyruğa alındı.");
        }

        $this->info("{$campaigns->count()} kampanya gönderim kuyruğuna alındı.");
        return Command::SUCCESS;
    }
}
