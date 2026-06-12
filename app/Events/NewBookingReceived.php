<?php

namespace App\Events;

use App\Models\PublicBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Senior'a yeni public booking ping'i — randevu confirm edildiğinde
 * Pusher üzerinden ilgili senior'un dashboard'ına anlık toast düşer.
 *
 * Kanal: senior.{senior_user_id}  (private)
 * Frontend listener key: '.booking.new'  (broadcastAs override)
 */
class NewBookingReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public PublicBooking $booking)
    {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('senior.' . (int) $this->booking->senior_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.new';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id'           => (int) $this->booking->id,
            'invitee'      => (string) $this->booking->invitee_name,
            'starts_at'    => optional($this->booking->starts_at)->toIso8601String(),
            'currency'     => (string) ($this->booking->currency ?? 'EUR'),
            'amount_gross' => (int) ($this->booking->amount_gross_cents ?? 0),
            'is_paid'      => $this->booking->isPaid(),
            'url'          => '/senior/appointments',
            'title'        => 'Yeni randevu',
            'message'      => $this->booking->invitee_name . ' randevu aldı',
            'icon'         => 'calendar-check',
            'sent_at'      => now()->toIso8601String(),
        ];
    }
}
