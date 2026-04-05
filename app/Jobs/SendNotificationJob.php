<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\NotificationDispatch;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 60;

    public function __construct(public readonly int $notificationId)
    {
    }

    public function handle(): void
    {
        $notification = NotificationDispatch::query()->find($this->notificationId);

        if (!$notification) {
            return;
        }

        if ((string) $notification->status === 'sent') {
            return;
        }

        $channel        = (string) ($notification->channel ?? '');
        $recipientEmail = trim((string) ($notification->recipient_email ?? ''));

        if ($channel === 'email') {
            if ($recipientEmail === '') {
                $notification->update([
                    'status'      => 'failed',
                    'failed_at'   => now(),
                    'fail_reason' => 'recipient_missing',
                ]);
                return;
            }

            try {
                Mail::to($recipientEmail)->send(new NotificationMail(
                    trim((string) ($notification->subject ?: config('app.name', 'MentorDE').' Bildirimi')),
                    (string) ($notification->body ?? ''),
                ));
            } catch (\Throwable $e) {
                Log::warning('SendNotificationJob: e-posta gönderilemedi, yeniden denenecek.', [
                    'id'      => $this->notificationId,
                    'email'   => $recipientEmail,
                    'attempt' => $this->attempts(),
                    'error'   => $e->getMessage(),
                ]);
                throw $e; // Kuyruğun $tries kadar yeniden denemesine izin ver
            }
        } elseif ($channel === 'in_app') {
            // In-app: DB kaydı yeterli, frontend polling ile gösterir
        } elseif ($channel === 'whatsapp') {
            $recipientPhone = trim((string) ($notification->recipient_phone ?? ''));

            if ($recipientPhone === '') {
                $notification->update([
                    'status'      => 'failed',
                    'failed_at'   => now(),
                    'fail_reason' => 'recipient_phone_missing',
                ]);
                return;
            }

            try {
                $whatsapp = app(WhatsAppService::class);
                $body     = trim((string) ($notification->body ?? $notification->subject ?? ''));
                $sent     = $whatsapp->sendTemplate(
                    $recipientPhone,
                    'mentorde_notification',
                    $body !== '' ? [$body] : []
                );

                if (!$sent) {
                    // API yanıt verdi ama başarısız — kalıcı hata, retry gerekmez
                    $notification->update([
                        'status'      => 'failed',
                        'failed_at'   => now(),
                        'fail_reason' => 'whatsapp_send_failed',
                    ]);
                    return;
                }
            } catch (\Throwable $e) {
                Log::warning('SendNotificationJob: WhatsApp gönderilemedi, yeniden denenecek.', [
                    'id'      => $this->notificationId,
                    'phone'   => $recipientPhone,
                    'attempt' => $this->attempts(),
                    'error'   => $e->getMessage(),
                ]);
                throw $e; // Kuyruğun $tries kadar yeniden denemesine izin ver
            }
        }

        $notification->update([
            'status'      => 'sent',
            'sent_at'     => now(),
            'failed_at'   => null,
            'fail_reason' => null,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        NotificationDispatch::query()
            ->where('id', $this->notificationId)
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => mb_substr($e->getMessage(), 0, 500),
            ]);
    }
}
