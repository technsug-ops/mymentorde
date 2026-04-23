<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;

/**
 * PostHog wrapper — event capture + identity stitching için tek giriş noktası.
 *
 * Kullanım:
 *   app(AnalyticsService::class)->capture('lead_created', ['lead_id' => 42]);
 *   app(AnalyticsService::class)->identify($user, ['role' => 'student']);
 *
 * Config:
 *   services.posthog.enabled=false → tüm çağrılar no-op
 *   services.posthog.api_key yoksa → silent skip
 *
 * Kural:
 *   - PII (email, phone) property olarak gönderilmez — hash'li kullan
 *   - environment property her event'e otomatik eklenir
 *   - Naming: object_verb + snake_case (bkz. docs/EVENT_CATALOG.md)
 */
class AnalyticsService
{
    private bool $enabled = false;
    private bool $initialized = false;

    public function __construct()
    {
        $this->enabled = (bool) config('services.posthog.enabled', true)
            && !empty(config('services.posthog.api_key'));

        if ($this->enabled) {
            $this->initialize();
        }
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            PostHog::init(
                config('services.posthog.api_key'),
                ['host' => config('services.posthog.host', 'https://eu.posthog.com')]
            );
            $this->initialized = true;
        } catch (\Throwable $e) {
            Log::warning('PostHog init failed', ['error' => $e->getMessage()]);
            $this->enabled = false;
        }
    }

    /**
     * Event capture — backend'den PostHog'a event gönder.
     *
     * @param string $event Event adı (object_verb format, snake_case)
     * @param array  $properties Event property'leri
     * @param string|int|null $distinctId null → auth user veya 'anonymous'
     */
    public function capture(string $event, array $properties = [], $distinctId = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $distinctId = $distinctId
            ?? (Auth::id() ? (string) Auth::id() : 'anonymous');

        $mergedProperties = array_merge($this->baseProperties(), $properties);

        try {
            PostHog::capture([
                'distinctId' => (string) $distinctId,
                'event'      => $event,
                'properties' => $mergedProperties,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PostHog capture failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * User identify — login/register anında çağrılır. User properties set eder.
     *
     * @param mixed $user User model veya ID
     * @param array $properties Ekstra property'ler (role, company_id vs.)
     */
    public function identify($user, array $properties = []): void
    {
        if (!$this->enabled || !$user) {
            return;
        }

        $distinctId = is_object($user) ? (string) ($user->id ?? '') : (string) $user;
        if ($distinctId === '') {
            return;
        }

        $autoProps = [];
        if (is_object($user)) {
            if (!empty($user->email)) {
                $autoProps['email_hash']   = hash('sha256', $user->email);
                $autoProps['email_domain'] = explode('@', $user->email)[1] ?? null;
            }
            if (!empty($user->role)) {
                $autoProps['role'] = $user->role;
            }
            if (!empty($user->company_id)) {
                $autoProps['company_id'] = $user->company_id;
            }
            if (!empty($user->created_at)) {
                $autoProps['signup_date'] = $user->created_at->toIso8601String();
            }
        }

        $mergedProperties = array_merge($autoProps, $properties);

        try {
            PostHog::identify([
                'distinctId' => $distinctId,
                'properties' => $mergedProperties,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PostHog identify failed', [
                'distinct_id' => $distinctId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alias — anonim distinctId'yi tanımlı user'a bağlar.
     * Örn: lead_form → user kaydı olunca alias(lead_xxx → user_id).
     */
    public function alias(string $distinctId, string $alias): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            PostHog::alias([
                'distinctId' => $distinctId,
                'alias'      => $alias,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PostHog alias failed', [
                'distinct_id' => $distinctId,
                'alias'       => $alias,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Her event'e otomatik eklenen base property'ler.
     */
    private function baseProperties(): array
    {
        return [
            'environment' => app()->environment(),
            'source'      => 'backend',
            'app_version' => config('app.version', '1.0.0'),
        ];
    }

    /**
     * Feature flag kontrol — PostHog Feature Flags entegrasyonu.
     */
    public function isFeatureEnabled(string $flagKey, ?string $distinctId = null, bool $default = false): bool
    {
        if (!$this->enabled) {
            return $default;
        }

        $distinctId = $distinctId
            ?? (Auth::id() ? (string) Auth::id() : 'anonymous');

        try {
            return (bool) PostHog::isFeatureEnabled($flagKey, (string) $distinctId, [], [], [], $default);
        } catch (\Throwable $e) {
            Log::warning('PostHog feature flag check failed', [
                'flag'  => $flagKey,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }
}
