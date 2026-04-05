<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAccessLog;
use App\Models\ConsentRecord;
use App\Models\ManagerRequest;
use App\Models\RoleChangeAudit;
use App\Models\SystemEventLog;
use App\Services\SecurityAnomalyService;
use Illuminate\Http\Request;

/**
 * K3 — Güvenlik: anomali listesi + compliance raporu
 */
class SecurityController extends Controller
{
    public function anomalies(SecurityAnomalyService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json(['ok' => true, 'anomalies' => $service->detect()]);
    }

    public function complianceReport(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = $request->query('period', now()->format('Y-m'));

        return response()->json([
            'period'             => $period,
            'generated_at'       => now()->toIso8601String(),
            'data_exports'       => SystemEventLog::where('event_type', 'gdpr.data_export')
                ->where('created_at', 'like', $period . '%')->count(),
            'erasure_requests'   => ManagerRequest::where('request_type', 'gdpr_erasure')
                ->where('created_at', 'like', $period . '%')->count(),
            'erasure_completed'  => ManagerRequest::where('request_type', 'gdpr_erasure')
                ->where('status', 'done')
                ->where('resolved_at', 'like', $period . '%')->count(),
            'pii_accesses'       => SystemEventLog::where('event_type', 'gdpr.pii_access')
                ->where('created_at', 'like', $period . '%')->count(),
            'consent_granted'    => ConsentRecord::whereNull('revoked_at')
                ->where('consented_at', 'like', $period . '%')->count(),
            'consent_revoked'    => ConsentRecord::whereNotNull('revoked_at')
                ->where('revoked_at', 'like', $period . '%')->count(),
            'retention_actions'  => 0,
            'vault_accesses'     => AccountAccessLog::where('created_at', 'like', $period . '%')->count(),
            'role_changes'       => RoleChangeAudit::where('created_at', 'like', $period . '%')->count(),
        ]);
    }
}
