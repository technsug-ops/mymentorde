<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Platform Owner bir tenant'ın tier'ını değiştirdi → manager paneline
 * "tier güncellendi" toast'u; modüller otomatik açılır/kapanır.
 *
 * Kanallar:
 *   manager.{company_id}     → o tenant'ın manager'ına bildir
 *   platform.owner           → tüm platform owner'lara audit ping
 */
class TierUpgraded implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Company $company,
        public string $newTier,
        public ?string $previousTier = null,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('manager.' . (int) $this->company->id),
            new PrivateChannel('platform.owner'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tier.upgraded';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'company_id'    => (int) $this->company->id,
            'company_name'  => (string) ($this->company->name ?? ''),
            'new_tier'      => $this->newTier,
            'previous_tier' => $this->previousTier,
            'url'           => '/manager/settings',
            'title'         => 'Abonelik güncellendi',
            'message'       => 'Paketiniz "' . $this->newTier . '" olarak güncellendi.',
            'icon'          => 'crown',
            'sent_at'       => now()->toIso8601String(),
        ];
    }
}
