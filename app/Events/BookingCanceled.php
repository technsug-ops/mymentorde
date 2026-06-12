<?php

namespace App\Events;

use App\Models\PublicBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Booking iptal edildiğinde karşı tarafa ping atar.
 *   - invitee iptal etti → senior.{id} kanalına
 *   - senior  iptal etti → user.{invitee_user_id} kanalına (varsa)
 *
 * Kanallar dinamik — broadcastOn() canceled_by'a göre seçer.
 */
class BookingCanceled implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public PublicBooking $booking,
        public string $reason = '',
        public string $canceledBy = 'invitee',
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = [];

        // Karsi tarafa ping at: invitee iptal → senior'a; senior iptal → varsa invitee user'a
        if ($this->canceledBy === 'invitee') {
            $channels[] = new PrivateChannel('senior.' . (int) $this->booking->senior_user_id);
        } elseif ($this->canceledBy === 'senior') {
            $inviteeUserId = (int) ($this->booking->student_user_id
                ?? $this->booking->booked_by_user_id
                ?? 0);
            if ($inviteeUserId > 0) {
                $channels[] = new PrivateChannel('user.' . $inviteeUserId);
            }
        }

        // Manager'a da bildir (company-scope)
        if ($this->booking->company_id) {
            $channels[] = new PrivateChannel('manager.' . (int) $this->booking->company_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'booking.canceled';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id'          => (int) $this->booking->id,
            'invitee'     => (string) $this->booking->invitee_name,
            'starts_at'   => optional($this->booking->starts_at)->toIso8601String(),
            'canceled_by' => $this->canceledBy,
            'reason'      => mb_substr($this->reason, 0, 200),
            'url'         => $this->canceledBy === 'invitee'
                ? '/senior/appointments'
                : '/student/appointments',
            'title'       => 'Randevu iptal edildi',
            'message'     => $this->booking->invitee_name . ' randevusunu iptal etti',
            'icon'        => 'calendar-x',
            'sent_at'     => now()->toIso8601String(),
        ];
    }
}
