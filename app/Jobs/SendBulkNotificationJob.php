<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationMail;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly array  $recipientIds,   // User id listesi
        public readonly string $subject,
        public readonly string $body,
        public readonly string $channel = 'email', // 'email' | 'db'
    ) {}

    public function handle(): void
    {
        $users = \App\Models\User::whereIn('id', $this->recipientIds)
            ->select('id', 'email', 'name', 'company_id')
            ->get();

        foreach ($users as $user) {
            if ($this->channel === 'email' && $user->email) {
                Mail::to($user->email)->queue(new NotificationMail($this->subject, $this->body));
            }
            // DB kanalı: notification_dispatches tablosuna ekle
            if (in_array($this->channel, ['db', 'both'])) {
                \App\Models\NotificationDispatch::create([
                    'user_id'    => $user->id,
                    'company_id' => $user->company_id ?? null,
                    'channel'    => 'in_app',
                    'title'      => $this->subject,
                    'body'       => $this->body,
                    'sent_at'    => now(),
                ]);
            }
        }
    }
}
