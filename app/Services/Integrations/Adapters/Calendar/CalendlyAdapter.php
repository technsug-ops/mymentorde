<?php

namespace App\Services\Integrations\Adapters\Calendar;

use Illuminate\Support\Facades\Http;

class CalendlyAdapter extends AbstractCalendarAdapter
{
    protected function providerCode(): string
    {
        return 'calendly';
    }

    /**
     * Token varsa Calendly API'den gerçek scheduling URL döndürür.
     * Token yoksa veya API hatası olursa stub URL ile geri döner.
     */
    public function getSchedulingLink(string $userId): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getSchedulingLink($userId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(5)
                ->get('https://api.calendly.com/event_types', ['count' => 1]);

            if ($resp->successful()) {
                $link = $resp->json('collection.0.scheduling_url', '');
                if ($link !== '') {
                    return $link;
                }
            }
        } catch (\Throwable) {
            // API erişilemiyor → stub'a geri dön
        }

        return parent::getSchedulingLink($userId);
    }
}

