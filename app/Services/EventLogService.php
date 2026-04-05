<?php

namespace App\Services;

use App\Models\SystemEventLog;

class EventLogService
{
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
        return SystemEventLog::query()->create([
            'company_id' => $companyId ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : null),
            'event_type' => trim($eventType),
            'entity_type' => $entityType ? trim($entityType) : null,
            'entity_id' => $entityId ? trim($entityId) : null,
            'message' => trim($message),
            'meta' => $meta,
            'actor_email' => $actorEmail ? trim($actorEmail) : null,
        ]);
    }
}

