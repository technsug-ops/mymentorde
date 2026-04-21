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

    /**
     * Google'dan değişen event'leri çek, local randevulara yansıt.
     *
     * Güvenlik yaklaşımı: SADECE google_event_id ile eşleşen mevcut randevular güncellenir/silinir.
     * Google'da portal'dan bağımsız oluşturulan event'ler yok sayılır (öğrenci bağlamı olmadan
     * anlamlı randevu oluşturamayız).
     *
     * @return array{processed:int, updated:int, cancelled:int, errors:int}
     */
    public function pullForConnection(GoogleCalendarConnection $conn): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'cancelled' => 0, 'errors' => 0];

        if (! $conn->sync_pull) {
            return $stats;
        }

        $token = $this->accessToken($conn);
        if (! $token) {
            $stats['errors']++;
            return $stats;
        }

        // updatedMin: son sync'ten bu yana değişenler. İlk sync için 30 gün geri git.
        $updatedMin = $conn->last_synced_at?->copy()->subMinutes(5)
            ?? now()->subDays(30);

        $nextPageToken = null;
        $maxIterations = 5; // abuse önlemi — en fazla 5 sayfa (500 event)

        do {
            $query = [
                'updatedMin'    => $updatedMin->toRfc3339String(),
                'showDeleted'   => 'true',
                'singleEvents'  => 'true',
                'maxResults'    => 100,
                'orderBy'       => 'updated',
            ];
            if ($nextPageToken) {
                $query['pageToken'] = $nextPageToken;
            }

            $res = Http::withToken($token)
                ->get(self::BASE . "/calendars/{$conn->calendar_id}/events", $query);

            if (! $res->successful()) {
                $this->markSyncFailed($conn, 'pull', $res->body());
                $stats['errors']++;
                return $stats;
            }

            $body = $res->json();
            $items = $body['items'] ?? [];

            foreach ($items as $ev) {
                $stats['processed']++;
                $this->applyRemoteEvent($conn, $ev, $stats);
            }

            $nextPageToken = $body['nextPageToken'] ?? null;
            $maxIterations--;
        } while ($nextPageToken && $maxIterations > 0);

        $conn->update([
            'last_sync_status' => 'ok',
            'last_sync_error'  => null,
            'last_synced_at'   => now(),
        ]);

        return $stats;
    }

    /** Tek bir Google event'ini local'e yansıt (yalnızca mevcut kayıtlar). */
    private function applyRemoteEvent(GoogleCalendarConnection $conn, array $ev, array &$stats): void
    {
        $googleEventId = (string) ($ev['id'] ?? '');
        if ($googleEventId === '') return;

        // Local'de bu event'i hangi randevu tutuyor? Sadece aynı senior'un randevuları.
        $seniorEmail = \App\Models\User::where('id', $conn->user_id)->value('email');
        $apt = StudentAppointment::where('google_event_id', $googleEventId)
            ->where('senior_email', $seniorEmail)
            ->first();

        if (! $apt) {
            // Bizim oluşturmadığımız / eşleşmeyen event — görmezden gel
            return;
        }

        // Event silinmiş mi?
        $status = (string) ($ev['status'] ?? '');
        if ($status === 'cancelled') {
            if ($apt->status !== 'cancelled') {
                $apt->forceFill([
                    'status'          => 'cancelled',
                    'cancelled_at'    => now(),
                    'cancel_category' => 'other',
                    'cancel_reason'   => 'Google Takvim\'den iptal edildi.',
                ])->saveQuietly();
                $stats['cancelled']++;
            }
            return;
        }

        // Event zamanı güncellenmiş mi?
        $startStr = $ev['start']['dateTime'] ?? ($ev['start']['date'] ?? null);
        $endStr   = $ev['end']['dateTime']   ?? ($ev['end']['date']   ?? null);
        if (! $startStr) return;

        $start = \Carbon\Carbon::parse($startStr);
        $durationMin = $endStr ? $start->diffInMinutes(\Carbon\Carbon::parse($endStr)) : (int) ($apt->duration_minutes ?? 45);

        // Son sync tarihinden sonra Google tarafında değişim var mı?
        $gUpdated = isset($ev['updated']) ? \Carbon\Carbon::parse($ev['updated']) : null;
        $localUpdated = $apt->updated_at;

        // Eğer local daha yeni ise Google'ın verisi eskimiş — skip (push ile zaten güncellendi)
        if ($gUpdated && $localUpdated && $localUpdated->gt($gUpdated)) {
            return;
        }

        $changed = false;
        $newTitle = (string) ($ev['summary'] ?? $apt->title);

        if (! $apt->scheduled_at || ! $apt->scheduled_at->eq($start)) {
            $apt->scheduled_at = $start;
            $changed = true;
        }
        if ((int) $apt->duration_minutes !== (int) $durationMin && $durationMin > 0) {
            $apt->duration_minutes = (int) $durationMin;
            $changed = true;
        }
        if ($apt->title !== $newTitle && $newTitle !== '') {
            $apt->title = $newTitle;
            $changed = true;
        }

        if ($changed) {
            $apt->google_synced_at = now();
            $apt->saveQuietly(); // observer'ı tetikleme (sonsuz döngüye girmesin)
            $stats['updated']++;
        }
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
