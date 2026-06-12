<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Chat / messaging hub'da yeni mesaj geldiğinde alıcıya ping.
 *
 * Kanal: user.{recipient_id}  (private)
 *
 * payload zorunlu alanları:
 *   recipient_id  — kanal hedefi
 *   sender_name   — UI gösterimi
 *   preview       — ilk 120 karakter
 *   thread_url    — toast click hedef URL
 */
class MessageReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $recipientId,
        public string $senderName,
        public string $preview,
        public string $threadUrl = '/messages',
        public ?int $messageId = null,
        public ?int $threadId = null,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'thread_id'  => $this->threadId,
            'sender'     => $this->senderName,
            'preview'    => mb_substr($this->preview, 0, 120),
            'url'        => $this->threadUrl,
            'title'      => $this->senderName,
            'message'    => mb_substr($this->preview, 0, 120),
            'icon'       => 'message-square',
            'sent_at'    => now()->toIso8601String(),
        ];
    }
}
