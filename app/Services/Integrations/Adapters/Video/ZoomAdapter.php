<?php

namespace App\Services\Integrations\Adapters\Video;

use Illuminate\Support\Facades\Http;
use Throwable;

class ZoomAdapter extends AbstractVideoAdapter
{
    protected function providerCode(): string
    {
        return 'zoom';
    }

    private const BASE = 'https://api.zoom.us/v2';

    public function createMeeting(array $data): string
    {
        $token = $this->getToken();
        if (!$token) {
            return parent::createMeeting($data);
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(20)
                ->post(self::BASE . '/users/me/meetings', [
                    'topic'      => $data['topic'] ?? ($data['title'] ?? 'Meeting'),
                    'type'       => 2, // scheduled
                    'start_time' => $data['start_time'] ?? now()->addHour()->toIso8601String(),
                    'duration'   => (int) ($data['duration'] ?? 60),
                    'agenda'     => $data['agenda'] ?? '',
                    'settings'   => [
                        'join_before_host'  => true,
                        'host_video'        => true,
                        'participant_video' => true,
                        'mute_upon_entry'   => false,
                    ],
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
                ->delete(self::BASE . "/meetings/{$meetingId}");

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
                ->get(self::BASE . "/meetings/{$meetingId}");

            if (!$resp->successful()) {
                return parent::getJoinUrl($meetingId);
            }

            $url = (string) ($resp->json('join_url') ?? '');
            return $url !== '' ? $url : parent::getJoinUrl($meetingId);
        } catch (Throwable) {
            return parent::getJoinUrl($meetingId);
        }
    }
}
