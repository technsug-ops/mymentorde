<?php

namespace App\Services\Integrations\Adapters\Video;

use Illuminate\Support\Facades\Http;
use Throwable;

class TeamsAdapter extends AbstractVideoAdapter
{
    protected function providerCode(): string
    {
        return 'teams';
    }

    private const BASE = 'https://graph.microsoft.com/v1.0';

    public function createMeeting(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createMeeting($data);
        }

        try {
            $startTime = $data['start_time'] ?? now()->addHour()->toIso8601String();
            $endTime   = $data['end_time']   ?? now()->addHours(2)->toIso8601String();

            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . '/me/onlineMeetings', [
                    'subject'       => $data['topic'] ?? ($data['title'] ?? 'Meeting'),
                    'startDateTime' => $startTime,
                    'endDateTime'   => $endTime,
                ]);

            if (!$resp->successful()) {
                return parent::createMeeting($data);
            }

            $id = (string) ($resp->json('id') ?? '');
            return $id !== '' ? $id : parent::createMeeting($data);
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

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->delete(self::BASE . "/me/onlineMeetings/{$meetingId}");

            return $resp->successful();
        } catch (Throwable) {
            return parent::cancelMeeting($meetingId);
        }
    }

    public function getJoinUrl(string $meetingId): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::getJoinUrl($meetingId);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->get(self::BASE . "/me/onlineMeetings/{$meetingId}");

            if (!$resp->successful()) {
                return parent::getJoinUrl($meetingId);
            }

            $url = (string) ($resp->json('joinWebUrl') ?? '');
            return $url !== '' ? $url : parent::getJoinUrl($meetingId);
        } catch (Throwable) {
            return parent::getJoinUrl($meetingId);
        }
    }
}
