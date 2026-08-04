<?php

namespace App\Services;

use App\Models\SystemEventLog;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Süreç olay akışı — "ana gelişmeler".
 *
 * Bayi, kendi getirdiği adayın operasyon özetini bu akıştan görüyor
 * (bkz. DealerLeadController::leadTimeline). Partner firmalar için de aynı şey
 * geçerli: öğrencinin sürecini MentorDE yürütse bile firma gelişmeleri görmeli.
 *
 * ── OLAY KİMİN ŞİRKETİNE YAZILIR ────────────────────────────────────────────
 * İşlemi YAPANIN değil, işlemin YAPILDIĞI kaydın şirketine.
 *
 * Eskiden `current_company_id` (aktörün şirketi) kullanılıyordu. MentorDE
 * personeli bir partner firmanın öğrencisi üzerinde çalıştığında olay MentorDE
 * kutusuna yazılıyordu; partner firma kendi öğrencisinin gelişmesini göremezdi.
 * Konu (subject) esas alınınca akış doğru tarafa düşer.
 */
class EventLogService
{
    /**
     * entity_type → olayın konusunu tutan tablo.
     *
     * Bilinmeyen tip = konu çözülemez; o zaman aktörün bağlamına düşülür
     * (eski davranış). Sessizce yanlış şirkete yazmaktansa bilinen tipleri
     * açıkça saymak daha güvenli.
     *
     * @var array<string,string>
     */
    private const ENTITY_TABLES = [
        'guest'             => 'guest_applications',
        'guest_application' => 'guest_applications',
        'lead'              => 'guest_applications',
        'student'           => 'users',
        'user'              => 'users',
        'dealer'            => 'dealers',
    ];

    /**
     * @param array<string,mixed>|null $meta
     */
    public function log(
        string $eventType,
        ?string $entityType,
        ?string $entityId,
        string $message,
        ?array $meta = null,
        ?string $actorEmail = null,
        ?int $companyId = null
    ): SystemEventLog {
        $resolvedCompanyId = $companyId
            ?: $this->companyOfSubject($entityType, $entityId)
            ?: TenantContext::writeId();

        return SystemEventLog::query()->create([
            'company_id' => $resolvedCompanyId,
            'event_type' => trim($eventType),
            'entity_type' => $entityType ? trim($entityType) : null,
            'entity_id' => $entityId ? trim($entityId) : null,
            'message' => trim($message),
            'meta' => $meta,
            'actor_email' => $actorEmail ? trim($actorEmail) : null,
        ]);
    }

    /** Olayın konusu olan kaydın şirketi. */
    private function companyOfSubject(?string $entityType, ?string $entityId): ?int
    {
        $type = strtolower(trim((string) $entityType));
        $id = trim((string) $entityId);

        if ($type === '' || $id === '' || !isset(self::ENTITY_TABLES[$type])) {
            return null;
        }

        $table = self::ENTITY_TABLES[$type];

        try {
            if (!Schema::hasColumn($table, 'company_id')) {
                return null;
            }

            // Konunun kendi tenant'ı okunuyor — global scope BİLEREK atlanıyor:
            // aktör o şirketi göremiyor olsa bile olay doğru tarafa yazılmalı.
            $companyId = (int) DB::table($table)->where('id', $id)->value('company_id');

            return $companyId > 0 ? $companyId : null;
        } catch (\Throwable) {
            // Tablo yok ya da id tipi uyumsuz — aktörün bağlamına düş.
            return null;
        }
    }
}
