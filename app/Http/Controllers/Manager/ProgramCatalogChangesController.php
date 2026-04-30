<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramChangeLog;
use App\Models\ProgramSourceLink;
use App\Services\EventLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Manager — Program kataloğu değişiklik takibi.
 *
 * Sync sırasında ChangeDetectionService source diff'lerini program_change_logs'a
 * yazar. Bu controller manager'a değişiklikleri gösterir + accept/reject/ignore
 * aksiyonları sunar.
 *
 * URL'ler:
 *  GET  /manager/program-catalog/changes        — değişiklik özet listesi
 *  POST /manager/program-catalog/changes/{log}/review  — review aksiyonu
 *
 * Erişim: sadece manager. Multi-tenant: program_change_logs SHARED tablo
 * (kaynak veri değişimi tüm company'leri etkiler), ama review aksiyonunu
 * yapan manager kendi company'siyle audit'lenir.
 */
class ProgramCatalogChangesController extends Controller
{
    public function __construct(private readonly EventLogService $eventLogService) {}

    public function index(Request $request): View
    {
        $severity = $request->query('severity'); // null|info|warning|critical
        $reviewed = $request->query('reviewed', 'unreviewed'); // unreviewed|all|reviewed

        $query = ProgramChangeLog::query()->with('program');

        if ($severity && in_array($severity, ['info', 'warning', 'critical'], true)) {
            $query->where('severity', $severity);
        }

        if ($reviewed === 'unreviewed') {
            $query->whereNull('reviewed_at');
        } elseif ($reviewed === 'reviewed') {
            $query->whereNotNull('reviewed_at');
        }

        $logs = $query->orderByDesc('detected_at')->paginate(50);

        // Özet sayılar
        $stats = [
            'total_unreviewed'      => ProgramChangeLog::query()->whereNull('reviewed_at')->count(),
            'critical_unreviewed'   => ProgramChangeLog::query()->whereNull('reviewed_at')->where('severity', 'critical')->count(),
            'warning_unreviewed'    => ProgramChangeLog::query()->whereNull('reviewed_at')->where('severity', 'warning')->count(),
            'recent_7days'          => ProgramChangeLog::query()->where('detected_at', '>=', now()->subDays(7))->count(),
            'total_programs'        => Program::count(),
            'total_source_links'    => ProgramSourceLink::count(),
        ];

        return view('manager.program-catalog.changes', [
            'logs'        => $logs,
            'stats'       => $stats,
            'severity'    => $severity,
            'reviewed'    => $reviewed,
        ]);
    }

    public function review(Request $request, ProgramChangeLog $log): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:accepted,rejected,manual_override,ignored',
            'note'   => 'nullable|string|max:1000',
        ]);

        $log->update([
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at'         => now(),
            'reviewer_action'     => $data['action'],
            'reviewer_note'       => $data['note'] ?? null,
        ]);

        // Manuel override: program is_manually_curated=true yapılır → re-sync override etmez
        if ($data['action'] === 'manual_override' && $log->program_id) {
            Program::query()->whereKey($log->program_id)->update(['is_manually_curated' => true]);
        }

        $this->eventLogService->log(
            eventType: 'program_change_reviewed',
            entityType: 'program_change_log',
            entityId: (string) $log->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' program değişikliğini "' . $data['action'] . '" olarak işaretledi.',
            meta: ['action' => $data['action'], 'severity' => $log->severity, 'field' => $log->field_changed],
        );

        return back()->with('success', 'Değişiklik review edildi.');
    }
}
