<?php

namespace App\Events;

use App\Models\PublicBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stripe webhook checkout.session.completed → ödeme onaylandı.
 * Hem senior'a (kazanç bildirimi) hem manager'a (cashflow ping) atılır.
 *
 * Kanallar:
 *   senior.{senior_user_id}
 *   manager.{company_id}
 */
class PaymentReceived implements ShouldBroadcast
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
        $channels = [];

        if ($this->booking->senior_user_id) {
            $channels[] = new PrivateChannel('senior.' . (int) $this->booking->senior_user_id);
        }
        if ($this->booking->company_id) {
            $channels[] = new PrivateChannel('manager.' . (int) $this->booking->company_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'payment.received';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $amount = (int) ($this->booking->amount_gross_cents ?? 0);
        $eur    = number_format($amount / 100, 2, ',', '.');

        return [
            'id'           => (int) $this->booking->id,
            'invitee'      => (string) $this->booking->invitee_name,
            'amount_cents' => $amount,
            'currency'     => (string) ($this->booking->currency ?? 'EUR'),
            'amount_label' => $eur . ' ' . (string) ($this->booking->currency ?? 'EUR'),
            'url'          => '/senior/earnings',
            'title'        => 'Ödeme alındı',
            'message'      => $eur . ' ' . ($this->booking->currency ?? 'EUR') . ' — ' . $this->booking->invitee_name,
            'icon'         => 'wallet',
            'sent_at'      => now()->toIso8601String(),
        ];
    }
}
