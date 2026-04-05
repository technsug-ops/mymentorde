<?php

namespace App\Services\Integrations\Adapters\Calendar;

use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleCalendarAdapter extends AbstractCalendarAdapter
{
    protected function providerCode(): string
    {
        return 'google_calendar';
    }

    private const BASE = 'https://www.googleapis.com/calendar/v3';

    public function createEvent(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createEvent($data);
        }

        try {
            $startTime = $data['start_time'] ?? now()->addHour()->toRfc3339String();
            $endTime   = $data['end_time']   ?? now()->addHours(2)->toRfc3339String();

            $body = [
                'summary'     => $data['title'] ?? ($data['summary'] ?? 'Event'),
                'description' => $data['description'] ?? '',
                'location'    => $data['location'] ?? '',
                'start'       => ['dateTime' => $startTime, 'timeZone' => 'UTC'],
                'end'         => ['dateTime' => $endTime,   'timeZone' => 'UTC'],
                'attendees'   => array_map(
                    fn ($e) => ['email' => $e],
                    (array) ($data['attendees'] ?? [])
                ),
            ];

            // Meet bağlantısı isteniyorsa
            if (!empty($data['add_meet'])) {
                $body['conferenceData'] = [
                    'createRequest' => [
                        'requestId'             => uniqid('gcal_', true),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ];
                $url = self::BASE . '/calendars/primary/events?conferenceDataVersion=1';
            } else {
                $url = self::BASE . '/calendars/primary/events';
            }

            $resp = Http::withToken($token)
                ->timeout(20)
                ->post($url, $body);

            if (!$resp->successful()) {
                return parent::createEvent($data);
            }

            $id = (string) ($resp->json('id') ?? '');
            return $id !== '' ? $id : parent::createEvent($data);
        } catch (Throwable) {
            return parent::createEvent($data);
        }
    }

    public function cancelEvent(string $eventId): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::cancelEvent($eventId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->delete(self::BASE . "/calendars/primary/events/{$eventId}");

            return $resp->successful();
        } catch (Throwable) {
            return parent::cancelEvent($eventId);
        }
    }

    public function getAvailability(string $userId, string $date): array
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getAvailability($userId, $date);
        }

        try {
            $start = $date . 'T00:00:00Z';
            $end   = $date . 'T23:59:59Z';

            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . '/freeBusy', [
                    'timeMin' => $start,
                    'timeMax' => $end,
                    'items'   => [['id' => 'primary']],
                ]);

            if (!$resp->successful()) {
                return parent::getAvailability($userId, $date);
            }

            $busy = data_get($resp->json('calendars.primary.busy'), null, []);
            if (!is_array($busy)) {
                return parent::getAvailability($userId, $date);
            }

            // Meşgul zamanları çıkar, 1-saatlik slotlar üret
            $busyRanges = array_map(fn ($b) => [
                'start' => (int) (strtotime($b['start']) / 3600) % 24,
                'end'   => (int) ceil(strtotime($b['end']) / 3600) % 24,
            ], $busy);

            $slots = [];
            for ($h = 9; $h <= 18; $h++) {
                $isBusy = false;
                foreach ($busyRanges as $range) {
                    if ($h >= $range['start'] && $h < $range['end']) {
                        $isBusy = true;
                        break;
                    }
                }
                if (!$isBusy) {
                    $slots[] = sprintf('%02d:00', $h);
                }
            }

            return $slots ?: parent::getAvailability($userId, $date);
        } catch (Throwable) {
            return parent::getAvailability($userId, $date);
        }
    }
}
