<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\StudentAppointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Calendar v3 API ile 2-way sync için servis.
 * Token refresh, event push (create/update/delete), pull iskeleti.
 *
 * Not: google/apiclient paketine bağımlılık yok — direkt HTTP çağrıları Guzzle (Laravel Http facade).
 * KAS shared hosting'de hafif, composer bağımlılık yok.
 */
class GoogleCalendarService
{
    private const BASE = 'https://www.googleapis.com/calendar/v3';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /** Geçerli access token döndür, gerekiyorsa refresh yap. */
    public function accessToken(GoogleCalendarConnection $conn): ?string
    {
        if (! $conn->isAccessTokenExpired()) {
            return $conn->access_token;
        }

        if (empty($conn->refresh_token)) {
            Log::warning('Google Calendar: refresh_token yok, user re-auth gerekiyor', [
                'user_id' => $conn->user_id,
            ]);
            return null;
        }

        $res = Http::asForm()->post(self::TOKEN_URL, [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $conn->refresh_token,
            'grant_type'    => 'refresh_token',
        ]);

        if (! $res->successful()) {
            Log::error('Google Calendar: token refresh başarısız', [
                'user_id' => $conn->user_id,
                'status'  => $res->status(),
                'body'    => $res->body(),
            ]);
            $conn->update([
                'last_sync_status' => 'failed',
                'last_sync_error'  => 'Token yenilenemedi: ' . $res->body(),
            ]);
            return null;
        }

        $data = $res->json();
        $conn->update([
            'access_token' => $data['access_token'],
            'expires_at'   => now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
        ]);

        return $data['access_token'];
    }

    /** Randevuyu Google Calendar'a push et (create veya update). */
    public function pushAppointment(StudentAppointment $apt): bool
    {
        $conn = $this->connectionForSenior($apt->senior_email);
        if (! $conn || ! $conn->sync_push) return false;

        $token = $this->accessToken($conn);
        if (! $token) return false;

        $payload = $this->buildEventPayload($apt);

        if ($apt->google_event_id) {
            // Update
            $res = Http::withToken($token)
                ->put(self::BASE . "/calendars/{$conn->calendar_id}/events/{$apt->google_event_id}", $payload);
        } else {
            // Create
            $res = Http::withToken($token)
                ->post(self::BASE . "/calendars/{$conn->calendar_id}/events", $payload);
        }

        if (! $res->successful()) {
            $this->markSyncFailed($conn, 'push', $res->body());
            Log::error('Google Calendar push başarısız', [
                'appointment_id' => $apt->id,
                'status'         => $res->status(),
                'body'           => substr($res->body(), 0, 500),
            ]);
            return false;
        }

        $body = $res->json();
        $apt->forceFill([
            'google_event_id'   => $body['id'] ?? $apt->google_event_id,
            'google_synced_at'  => now(),
            'meeting_url'       => $apt->meeting_url ?: ($body['hangoutLink'] ?? $body['htmlLink'] ?? null),
        ])->saveQuietly();

        $conn->update([
            'last_sync_status' => 'ok',
            'last_sync_error'  => null,
            'last_synced_at'   => now(),
        ]);

        return true;
    }

    /** Randevu silindiğinde Google Calendar'dan da kaldır. */
    public function deleteAppointment(StudentAppointment $apt): bool
    {
        if (empty($apt->google_event_id)) return true; // zaten yok

        $conn = $this->connectionForSenior($apt->senior_email);
        if (! $conn || ! $conn->sync_push) return false;

        $token = $this->accessToken($conn);
        if (! $token) return false;

        $res = Http::withToken($token)
            ->delete(self::BASE . "/calendars/{$conn->calendar_id}/events/{$apt->google_event_id}");

        // 410 = zaten silinmiş, 404 = yok. Her ikisi de başarılı sayılır.
        if (! $res->successful() && ! in_array($res->status(), [404, 410], true)) {
            $this->markSyncFailed($conn, 'delete', $res->body());
            return false;
        }

        return true;
    }

    /** Senior email'ine karşılık gelen aktif bağlantı (yoksa null). */
    private function connectionForSenior(?string $email): ?GoogleCalendarConnection
    {
        if (empty($email)) return null;
        $userId = \App\Models\User::where('email', $email)->value('id');
        if (! $userId) return null;
        return GoogleCalendarConnection::where('user_id', $userId)->first();
    }

    private function buildEventPayload(StudentAppointment $apt): array
    {
        $start = $apt->scheduled_at ?? $apt->requested_at ?? now();
        $end   = $start->copy()->addMinutes((int) ($apt->duration_minutes ?? 45));

        $descParts = [];
        if ($apt->student_email) $descParts[] = 'Öğrenci: ' . $apt->student_email;
        if ($apt->note)          $descParts[] = "\nNot: " . $apt->note;
        if ($apt->channel)       $descParts[] = "\nKanal: " . $apt->channel;
        $descParts[] = "\n— MentorDE randevusu (ID: {$apt->id})";

        $payload = [
            'summary'     => $apt->title ?: 'Mentorde Randevusu',
            'description' => implode("\n", $descParts),
            'start'       => ['dateTime' => $start->toIso8601String(), 'timeZone' => 'Europe/Berlin'],
            'end'         => ['dateTime' => $end->toIso8601String(),   'timeZone' => 'Europe/Berlin'],
        ];

        if ($apt->student_email) {
            $payload['attendees'] = [['email' => $apt->student_email]];
        }

        if ($apt->channel === 'online') {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => 'mde-' . $apt->id . '-' . now()->timestamp,
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        return $payload;
    }

    private function markSyncFailed(GoogleCalendarConnection $conn, string $op, string $error): void
    {
        $conn->update([
            'last_sync_status' => 'failed',
            'last_sync_error'  => substr("{$op}: {$error}", 0, 1000),
            'last_synced_at'   => now(),
        ]);
    }
}
