<?php

namespace App\Http\Middleware;

use App\Models\ApiPartner;
use App\Models\ApiPartnerRequest;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner API authentication + per-key rate limit + audit log.
 *
 * Header: Authorization: Bearer mtde_live_xxxxxxxx
 * (alternatif fallback: ?api_key=mtde_live_xxxxxxxx query param — log'a düşer
 *  ama bazı entegrasyonlar header gönderemediği için destekleniyor)
 *
 * Akış:
 *  1. Bearer token parse
 *  2. sha256 hash ile partner lookup
 *  3. is_active kontrol
 *  4. Per-key rate limit (rate_limit_per_hour)
 *  5. request->attributes->set('api_partner', $partner) — controller erişebilir
 *  6. Response sonrası audit log (terminate fazında)
 */
class VerifyApiKey
{
    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        $plainKey = $this->extractKey($request);
        if ($plainKey === null) {
            return $this->logAndRespond($request, null, 401, $started, [
                'error'   => 'unauthorized',
                'message' => 'Missing API key. Use Authorization: Bearer <key> header.',
            ]);
        }

        $partner = ApiPartner::findByPlaintextKey($plainKey);
        if ($partner === null) {
            return $this->logAndRespond($request, null, 401, $started, [
                'error'   => 'unauthorized',
                'message' => 'Invalid API key.',
            ]);
        }

        if (! $partner->is_active) {
            return $this->logAndRespond($request, $partner, 403, $started, [
                'error'   => 'forbidden',
                'message' => 'API key is disabled. Contact MentorDE.',
            ]);
        }

        // Per-key sliding window rate limit
        $rlKey = 'apikey:' . $partner->id;
        if ($this->limiter->tooManyAttempts($rlKey, $partner->rate_limit_per_hour)) {
            $retry = $this->limiter->availableIn($rlKey);
            return $this->logAndRespond($request, $partner, 429, $started, [
                'error'   => 'rate_limited',
                'message' => "Rate limit exceeded ({$partner->rate_limit_per_hour}/hour). Try again in {$retry}s.",
                'retry_after_seconds' => $retry,
            ], ['Retry-After' => (string) $retry]);
        }
        $this->limiter->hit($rlKey, 3600); // 1 saat pencere

        // Controller'a partner objesi geç
        $request->attributes->set('api_partner', $partner);

        /** @var Response $response */
        $response = $next($request);

        // Audit log (asenkron yapılabilir ama şimdilik inline — usage az)
        $this->logRequest($request, $partner, $response->getStatusCode(), $started, $response);

        // Lifetime sayaç + last_used_at (sadece 2xx için)
        if ($response->getStatusCode() < 400) {
            $partner->recordHit();
        }

        return $response;
    }

    private function extractKey(Request $request): ?string
    {
        $auth = $request->header('Authorization', '');
        if (is_string($auth) && stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
            if ($token !== '') {
                return $token;
            }
        }
        // Fallback (loglanır, ama bazı CMS'ler header set edemiyor)
        $param = $request->query('api_key');
        return is_string($param) && $param !== '' ? $param : null;
    }

    private function logAndRespond(Request $request, ?ApiPartner $partner, int $code, float $started, array $body, array $extraHeaders = []): JsonResponse
    {
        $response = new JsonResponse($body, $code);
        foreach ($extraHeaders as $h => $v) {
            $response->headers->set($h, $v);
        }
        $this->logRequest($request, $partner, $code, $started, $response);
        return $response;
    }

    private function logRequest(Request $request, ?ApiPartner $partner, int $statusCode, float $started, Response $response): void
    {
        // Sadece /api/v1/* endpoint'leri logla — gürültü olmasın
        $path = '/' . ltrim($request->path(), '/');
        if (! str_starts_with($path, '/api/')) {
            return;
        }

        try {
            $resultCount = null;
            $content = $response->getContent();
            if ($content && str_starts_with($content, '{')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['meta']['total'])) {
                    $resultCount = (int) $decoded['meta']['total'];
                }
            }

            ApiPartnerRequest::query()->create([
                'api_partner_id'   => $partner?->id,
                'endpoint'         => substr($path, 0, 200),
                'method'           => $request->method(),
                'ip'               => $request->ip(),
                'query_params'     => $request->query(),
                'response_code'    => $statusCode,
                'response_time_ms' => (int) round((microtime(true) - $started) * 1000),
                'result_count'     => $resultCount,
                'user_agent'       => substr((string) $request->userAgent(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            // Audit log fail edince API'yi kıramayız
            report($e);
        }
    }
}
