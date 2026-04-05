<?php

namespace App\Console\Commands;

use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSendLog;
use App\Models\NotificationDispatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessEmailQueueCommand extends Command
{
    protected $signature   = 'email:process-queue {--limit=50 : Her çalıştırmada max gönderilecek email sayısı}';
    protected $description = 'Email gönderim kuyruğunu işle — bekleyen email\'leri gönder';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Bekleyen email'leri al (send_at <= now, status=pending)
        $pending = EmailSendLog::query()
            ->where('status', 'pending')
            ->whereHas('emailCampaign', fn ($q) => $q->where('status', 'sending')
                ->where('send_at', '<=', now()))
            ->with(['emailCampaign.emailTemplate', 'recipient'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent   = 0;
        $failed = 0;

        foreach ($pending as $log) {
            try {
                // NotificationDispatch üzerinden gönder
                $dispatch = NotificationDispatch::create([
                    'user_id'          => $log->recipient_user_id ?? null,
                    'channel'          => 'email',
                    'subject'          => (string) ($log->emailCampaign?->subject ?? $log->emailCampaign?->emailTemplate?->subject ?? ''),
                    'body'             => (string) ($log->emailCampaign?->emailTemplate?->body_html ?? ''),
                    'recipient_email'  => (string) ($log->recipient_email ?? ''),
                    'status'           => 'pending',
                    'company_id'       => $log->emailCampaign?->company_id,
                ]);

                $log->update([
                    'status'    => 'sent',
                    'sent_at'   => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $log->update([
                    'status'       => 'failed',
                    'bounce_type'  => 'system_error',
                ]);
                $failed++;
                $this->warn("Log #{$log->id} gönderilemedi: " . $e->getMessage());
            }
        }

        $this->info("Email kuyruğu: {$sent} gönderildi, {$failed} başarısız.");

        return Command::SUCCESS;
    }
}
