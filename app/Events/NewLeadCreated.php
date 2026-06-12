<?php

namespace App\Events;

use App\Models\GuestApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Yeni guest application → manager + marketing_admin + platform_owner'a ping.
 * Lead-gen real-time bildirimi — başvuru gelir gelmez panel dolar.
 *
 * Kanal: manager.{company_id}  (private)
 */
class NewLeadCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public GuestApplication $application)
    {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('manager.' . (int) $this->application->company_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lead.new';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $name = trim(($this->application->first_name ?? '') . ' ' . ($this->application->last_name ?? ''));

        return [
            'id'               => (int) $this->application->id,
            'tracking_token'   => (string) $this->application->tracking_token,
            'name'             => $name !== '' ? $name : (string) $this->application->email,
            'email'            => (string) $this->application->email,
            'application_type' => (string) ($this->application->application_type ?? ''),
            'lead_source'      => (string) ($this->application->lead_source ?? ''),
            'assigned_senior'  => (string) ($this->application->assigned_senior_email ?? ''),
            'url'              => '/manager/guests/' . (int) $this->application->id,
            'title'            => 'Yeni başvuru',
            'message'          => ($name !== '' ? $name : $this->application->email) . ' aday başvurusu oluşturdu',
            'icon'             => 'user-plus',
            'sent_at'          => now()->toIso8601String(),
        ];
    }
}
