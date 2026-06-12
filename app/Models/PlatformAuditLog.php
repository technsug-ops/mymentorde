<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Owner audit log — event-tabanli denetim kaydi.
 *
 * Ornek event'ler:
 *   - platform.impersonate.start / stop
 *   - platform.settings.update
 *   - platform.security.update
 *   - platform.billing.invoice_sent / mark_paid / generate
 *   - platform.company.tier_changed / modules_updated / created
 *   - platform.payment.charge / refund / fail
 *
 * @property int $id
 * @property string $event
 * @property int|null $actor_user_id
 * @property string|null $actor_email
 * @property string|null $actor_role
 * @property string|null $actor_ip
 * @property string|null $target_type
 * @property int|null $target_id
 * @property array|null $context
 * @property string $severity   info|warning|critical
 * @property \Illuminate\Support\Carbon $created_at
 */
class PlatformAuditLog extends Model
{
    public const UPDATED_AT = null;

    public const SEVERITY_INFO     = 'info';
    public const SEVERITY_WARNING  = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_INFO,
        self::SEVERITY_WARNING,
        self::SEVERITY_CRITICAL,
    ];

    protected $table = 'platform_audit_logs';

    protected $fillable = [
        'event',
        'actor_user_id',
        'actor_email',
        'actor_role',
        'actor_ip',
        'target_type',
        'target_id',
        'context',
        'severity',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // RELATIONS
    // ────────────────────────────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withDefault([
            'name'  => 'Sistem',
            'email' => null,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ────────────────────────────────────────────────────────────────────────

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeWarnings(Builder $query): Builder
    {
        return $query->where('severity', self::SEVERITY_WARNING);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForActor(Builder $query, int $actorUserId): Builder
    {
        return $query->where('actor_user_id', $actorUserId);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeForEventPrefix(Builder $query, string $prefix): Builder
    {
        return $query->where('event', 'like', $prefix . '%');
    }

    public function scopeForTarget(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('target_type', $type);
        if ($id !== null) {
            $query->where('target_id', $id);
        }
        return $query;
    }

    // ────────────────────────────────────────────────────────────────────────
    // STATIC HELPER — quick record (graceful, never throws)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Kisa yol — controller'lardan tek satirla cagrilabilir.
     * Hata olursa sessizce loglar (asla request'i kirmiyor).
     *
     * @param  string  $event   ornek: 'platform.impersonate.start'
     * @param  array   $context arbitrary metadata (old/new values, vb.)
     * @param  string  $severity info|warning|critical
     */
    public static function record(string $event, array $context = [], string $severity = self::SEVERITY_INFO): ?self
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('platform_audit_logs')) {
                return null;
            }

            $request = request();
            $user    = $request?->user();

            $payload = [
                'event'         => $event,
                'actor_user_id' => $user?->id,
                'actor_email'   => $user?->email,
                'actor_role'    => $user?->role ?? null,
                'actor_ip'      => $request?->ip(),
                'target_type'   => $context['target_type'] ?? null,
                'target_id'     => isset($context['target_id']) ? (int) $context['target_id'] : null,
                'context'       => $context['data'] ?? $context,
                'severity'      => in_array($severity, self::SEVERITIES, true) ? $severity : self::SEVERITY_INFO,
            ];

            // target_type/target_id'yi context icinden cikar (cift kayit olmasin)
            if (isset($payload['context']['target_type'])) {
                unset($payload['context']['target_type']);
            }
            if (isset($payload['context']['target_id'])) {
                unset($payload['context']['target_id']);
            }

            return self::create($payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PlatformAuditLog::record failed', [
                'event' => $event,
                'err'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRESENTATION HELPERS
    // ────────────────────────────────────────────────────────────────────────

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'plat-badge-inactive',
            self::SEVERITY_WARNING  => 'plat-badge-gold',
            default                 => 'plat-badge-active',
        };
    }

    public function severityIcon(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'alert-triangle',
            self::SEVERITY_WARNING  => 'circle-alert',
            default                 => 'info',
        };
    }

    /**
     * Insan dostu event etiketi — 'platform.impersonate.start' → 'Impersonate Basladi'
     */
    public function humanEvent(): string
    {
        $map = [
            'platform.impersonate.start'         => 'Impersonate Baslangic',
            'platform.impersonate.stop'          => 'Impersonate Bitis',
            'platform.settings.update'           => 'Platform Ayar Guncellendi',
            'platform.security.update'           => 'Guvenlik Ayari Guncellendi',
            'platform.billing.invoice_generated' => 'Fatura Olusturuldu',
            'platform.billing.invoice_sent'      => 'Fatura Gonderildi',
            'platform.billing.invoice_paid'      => 'Fatura Odendi',
            'platform.company.tier_changed'      => 'Tier Degistirildi',
            'platform.company.modules_updated'   => 'Moduller Guncellendi',
            'platform.company.created'           => 'Yeni Company Olusturuldu',
            'platform.payment.charge'            => 'Odeme Alindi',
            'platform.payment.refund'            => 'Iade Yapildi',
            'platform.payment.fail'              => 'Odeme Basarisiz',
        ];
        return $map[$this->event] ?? $this->event;
    }
}
