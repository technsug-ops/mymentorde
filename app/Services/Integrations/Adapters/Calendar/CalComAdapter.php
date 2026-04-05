<?php

namespace App\Services\Integrations\Adapters\Calendar;

use Illuminate\Support\Facades\Http;
use Throwable;

class CalComAdapter extends AbstractCalendarAdapter
{
    protected function providerCode(): string
    {
        return 'cal_com';
    }

    private const BASE = 'https://api.cal.com/v1';

    public function getSchedulingLink(string $userId): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getSchedulingLink($userId);
        }

        try {
            $resp = Http::timeout(10)
                ->get(self::BASE . '/event-types', ['apiKey' => $token]);

            if (!$resp->successful()) {
                return parent::getSchedulingLink($userId);
            }

            $types = $resp->json('event_types') ?? $resp->json('eventTypes') ?? [];
            foreach ((array) $types as $type) {
                $link = (string) ($type['link'] ?? $type['slug'] ?? '');
                if ($link !== '') {
                    // link mutlak URL veya slug olabilir
                    return str_starts_with($link, 'http') ? $link : 'https://cal.com/' . ltrim($link, '/');
                }
            }

            return parent::getSchedulingLink($userId);
        } catch (Throwable) {
            return parent::getSchedulingLink($userId);
        }
    }
}
