<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemEventLog;
use Illuminate\Http\Request;

class SystemEventLogController extends Controller
{
    public function index(Request $request)
    {
        $eventType = trim((string) $request->query('event_type', ''));
        $entityType = trim((string) $request->query('entity_type', ''));
        $entityId = trim((string) $request->query('entity_id', ''));

        return SystemEventLog::query()
            ->when($eventType !== '', fn ($q) => $q->where('event_type', $eventType))
            ->when($entityType !== '', fn ($q) => $q->where('entity_type', $entityType))
            ->when($entityId !== '', fn ($q) => $q->where('entity_id', $entityId))
            ->latest()
            ->limit(200)
            ->get();
    }
}

