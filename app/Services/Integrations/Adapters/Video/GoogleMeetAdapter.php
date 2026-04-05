<?php

namespace App\Services\Integrations\Adapters\Video;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Meet, Google Calendar API üzerinden oluşturulur.
 * Token: google_meet providerCode veya google_calendar paylaşılabilir.
 * createMeeting dönüş değeri: "{eventId}|{joinUrl}" formatında (getJoinUrl için ayrıştırılır).
 */
class GoogleMeetAdapter extends AbstractVideoAdapter
{
    protected function providerCode(): string
    {
        return 'google_meet';
    }

    private const CAL_BASE = 'https://www.googleapis.com/calendar/v3';

    public function createMeeting(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createMeeting($data);
        }

        try {
            $requestId = uniqid('meet_', true);
            $startTime = $data['start_time'] ?? now()->addHour()->toRfc3339String();
            $endTime   = $data['end_time']   ?? now()->addHours(2)->toRfc3339String();

            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::CAL_BASE . '/calendars/primary/events?conferenceDataVersion=1', [
                    'summary'         => $data['topic'] ?? ($data['title'] ?? 'Meeting'),
                    'description'     => $data['agenda'] ?? '',
                    'start'           => ['dateTime' => $startTime, 'timeZone' => 'UTC'],
                    'end'             => ['dateTime' => $endTime,   'timeZone' => 'UTC'],
                    'attendees'       => array_map(
                        fn ($e) => ['email' => $e],
                        (array) ($data['attendees'] ?? [])
                    ),
                    'conferenceData'  => [
                        'createRequest' => [
                            'requestId'             => $requestId,
                            'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                        ],
                    ],
                ]);

            if (!$resp->successful()) {
                return parent::createMeeting($data);
            }

            $eventId = (string) ($resp->json('id') ?? '');
            $joinUrl = (string) data_get($resp->json('conferenceData.entryPoints'), '0.uri', '');

            if ($eventId === '') {
                return parent::createMeeting($data);
            }

            // eventId|joinUrl formatında sakla
            return $eventId . '|' . $joinUrl;
        } catch (Throwable) {
            return parent::createMeeting($data);
        }
    }

    public function cancelMeeting(string $meetingId): bool
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::cancelMeeting($meetingId);
        }

        // meetingId = eventId|joinUrl olabilir
        $eventId = explode('|', $meetingId)[0];

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->delete(self::CAL_BASE . "/calendars/primary/events/{$eventId}");

            return $resp->successful();
        } catch (Throwable) {
            return parent::cancelMeeting($meetingId);
        }
    }

    public function getJoinUrl(string $meetingId): string
    {
        // meetingId = eventId|joinUrl formatında ise direkt çıkar
        if (str_contains($meetingId, '|')) {
            $parts = explode('|', $meetingId, 2);
            if (!empty($parts[1])) {
                return $parts[1];
            }
            $eventId = $parts[0];
        } else {
            $eventId = $meetingId;
        }

        $token = $this->getToken();
        if (!$token) {
            return parent::getJoinUrl($meetingId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->get(self::CAL_BASE . "/calendars/primary/events/{$eventId}");

            if (!$resp->successful()) {
                return parent::getJoinUrl($meetingId);
            }

            $url = (string) data_get($resp->json('conferenceData.entryPoints'), '0.uri', '');
            return $url !== '' ? $url : parent::getJoinUrl($meetingId);
        } catch (Throwable) {
            return parent::getJoinUrl($meetingId);
        }
    }
}
