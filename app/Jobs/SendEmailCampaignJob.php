<?php

namespace App\Jobs;

use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public readonly int $campaignId)
    {
    }

    public function handle(): void
    {
        $campaign = EmailCampaign::query()->with('template')->find($this->campaignId);

        if (!$campaign) {
            return;
        }

        // Idempotency — başka bir worker zaten gönderiyorsa dur
        if (in_array((string) $campaign->status, ['sent', 'sending'], true)) {
            return;
        }

        $campaign->forceFill(['status' => 'sending'])->save();

        $snapshot = (array) ($campaign->recipient_snapshot ?? []);
        if ($snapshot === []) {
            Log::warning('SendEmailCampaignJob: recipient_snapshot boş, kampanya atlandı.', [
                'campaign_id' => $this->campaignId,
            ]);
            $campaign->forceFill(['status' => 'draft'])->save();
            return;
        }

        $sent = 0;
        foreach ($snapshot as $recipient) {
            $email = trim((string) ($recipient['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            // Aynı kampanya için tekrar log oluşturmayı önle
            $alreadySent = EmailSendLog::query()
                ->where('email_campaign_id', $campaign->id)
                ->where('recipient_email', $email)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            EmailSendLog::query()->create([
                'email_campaign_id' => (int) $campaign->id,
                'template_id'       => (int) $campaign->template_id,
                'recipient_user_id' => Arr::get($recipient, 'user_id'),
                'recipient_email'   => $email,
                'subject'           => (string) (($campaign->template?->subject_tr ?: $campaign->name) ?: $campaign->name),
                'language'          => 'tr',
                'trigger_event'     => $campaign->template?->trigger_event,
                'status'            => 'sent',
                'opened_at'         => null,
                'clicked_at'        => null,
                'clicked_links'     => [],
                'bounce_reason'     => null,
                'sent_at'           => now(),
                'created_at'        => now(),
            ]);
            $sent++;
        }

        $campaign->forceFill([
            'status'              => 'sent',
            'sent_at'             => now(),
            'stat_sent'           => (int) $sent,
            'stat_delivered'      => (int) $sent,
            'stat_opened'         => 0,
            'stat_open_rate'      => 0,
            'stat_clicked'        => 0,
            'stat_click_rate'     => 0,
            'stat_bounced'        => 0,
            'stat_unsubscribed'   => 0,
        ])->save();

        if ($campaign->template) {
            $campaign->template->forceFill([
                'stat_total_sent'  => (int) $campaign->template->stat_total_sent + $sent,
                'stat_last_sent_at' => now(),
            ])->save();
        }

        Log::info('SendEmailCampaignJob: kampanya gönderildi.', [
            'campaign_id' => $this->campaignId,
            'sent'        => $sent,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        EmailCampaign::query()
            ->where('id', $this->campaignId)
            ->update(['status' => 'draft']);

        Log::error('SendEmailCampaignJob: başarısız.', [
            'campaign_id' => $this->campaignId,
            'error'       => $e->getMessage(),
        ]);
    }
}
