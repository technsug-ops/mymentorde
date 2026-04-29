<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Expatrio "Study Buddy" public API wrapper.
 *
 * Endpoint: https://study-buddy.api.expatrio.com
 * Auth: yok — frontend'in çağırdığı public endpoint
 * Rate-limit: client-side throttle (default 600ms = ~1.6 req/sn) ile self-imposed.
 *
 * UYARI: Bu API dokümante edilmemiş ("internal" gibi gösterilebilir). Production'da
 * uzun vadeli kullanım için Expatrio ile partnership önerilir. Şimdilik düşük volume
 * (haftalık sync) güvenli kullanım kapsamında.
 *
 * Origin/Referer header'ları frontend'in kullandığı değerlerle aynıdır — CORS uyumlu.
 */
class ExpatrioStudyBuddyClient
{
    private const BASE_URL = 'https://study-buddy.api.expatrio.com';
    private const ORIGIN   = 'https://www.expatrio.com';
    private const REFERER  = 'https://www.expatrio.com/study-buddy/';

    /** Default request throttle (microseconds) — 600 ms ≈ 1.6 req/sn */
    private int $throttleMicros = 600_000;

    private ?float $lastRequestAt = null;

    public function __construct(?int $throttleMs = null)
    {
        if ($throttleMs !== null && $throttleMs >= 0) {
            $this->throttleMicros = $throttleMs * 1000;
        }
    }

    /**
     * Tüm üniversiteleri listeler (~500+ kayıt).
     *
     * @return array<int, array{id:string, name:string}>
     */
    public function listUniversities(): array
    {
        return $this->jsonGet('/studybuddy/universities') ?: [];
    }

    /**
     * Study field listesi (8 ana akademik alan).
     *
     * @return array<int, array{id:string, name:string}>
     */
    public function listStudyFields(): array
    {
        return $this->jsonGet('/studybuddy/studyfields') ?: [];
    }

    /**
     * Program search — paginated POST.
     *
     * @param  int    $limit    sayfa başına program (max 1000+ destekler)
     * @param  int    $offset   offset
     * @param  array  $filters  ek filtreler (degree, language, locationId, studyFieldId, ...)
     * @return array{total:int, programs:array<int, array<string,mixed>>}
     */
    public function searchPrograms(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $body = array_merge(['limit' => $limit, 'offset' => $offset], $filters);
        $resp = $this->post('/studybuddy/programs/search', $body);

        if (! $resp->ok()) {
            throw new RuntimeException(
                "Expatrio searchPrograms failed: HTTP {$resp->status()} body=" . substr($resp->body(), 0, 200)
            );
        }

        $data = $resp->json();
        return [
            'total'    => (int) ($data['total'] ?? 0),
            'programs' => (array) ($data['programs'] ?? []),
        ];
    }

    /**
     * Tek program detayı (full data — deadline, requirements, costs vb.).
     *
     * @return array<string,mixed>|null  null döner → 404
     */
    public function getProgram(string $id): ?array
    {
        $resp = $this->rawGet('/studybuddy/programs/' . urlencode($id));
        if ($resp->status() === 404) return null;
        if (! $resp->ok()) {
            Log::warning('ExpatrioStudyBuddyClient.getProgram failed', [
                'id' => $id, 'http' => $resp->status(), 'body' => substr($resp->body(), 0, 200),
            ]);
            return null;
        }
        return (array) $resp->json();
    }

    // ───────────────── Private HTTP helpers ─────────────────

    /** Throttle + GET + json decode (raw GET). */
    private function jsonGet(string $path): ?array
    {
        $resp = $this->rawGet($path);
        if (! $resp->ok()) {
            Log::warning('ExpatrioStudyBuddyClient GET failed', ['path' => $path, 'http' => $resp->status()]);
            return null;
        }
        return $resp->json();
    }

    private function rawGet(string $path): Response
    {
        $this->throttle();
        return Http::withHeaders($this->headers())
            ->timeout(20)
            ->get(self::BASE_URL . $path);
    }

    private function post(string $path, array $body): Response
    {
        $this->throttle();
        return Http::withHeaders($this->headers())
            ->timeout(30)
            ->asJson()
            ->post(self::BASE_URL . $path, $body);
    }

    private function headers(): array
    {
        return [
            'Accept'     => 'application/json',
            'Origin'     => self::ORIGIN,
            'Referer'    => self::REFERER,
            'User-Agent' => 'MentorDE/1.0 (Educational; +https://panel.mentorde.com/)',
        ];
    }

    /** Self-imposed rate limit. Bekler — Expatrio sunucusunu yormamak için. */
    private function throttle(): void
    {
        if ($this->lastRequestAt !== null) {
            $sinceMicros = (int) ((microtime(true) - $this->lastRequestAt) * 1_000_000);
            $waitMicros = $this->throttleMicros - $sinceMicros;
            if ($waitMicros > 0) usleep($waitMicros);
        }
        $this->lastRequestAt = microtime(true);
    }
}
