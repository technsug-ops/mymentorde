<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Models\ManagerReport;
use App\Services\DashboardPayloadService;
use App\Support\CsvExportHelper;
use Illuminate\Http\Request;

class ManagerReportSnapshotController extends Controller
{
    use ManagerDashboardTrait;

    public function __construct(private readonly DashboardPayloadService $payload) {}

    public function store(Request $request)
    {
        $request->validate([
            'report_type' => ['required', 'string', 'max:32'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'senior_email'=> ['nullable', 'email'],
            'sent_to'     => ['nullable', 'string', 'max:2000'],
        ]);

        [$monthStart, $monthEnd, $selectedSenior] = $this->resolveFilters($request);
        $snapshotFilters = $this->resolveSnapshotFilters($request);
        $data = $this->payload->build($monthStart, $monthEnd, $selectedSenior, $snapshotFilters);

        $sentTo = collect(explode(',', (string) $request->input('sent_to', '')))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        ManagerReport::query()->create([
            'company_id'   => $this->companyId() ?: null,
            'report_type'  => (string) $request->input('report_type', 'manual'),
            'period_start' => $data['filters']['start_date'],
            'period_end'   => $data['filters']['end_date'],
            'senior_email' => $data['filters']['senior_email'] ?: null,
            'sent_to'      => $sentTo,
            'send_status'  => 'draft',
            'sent_at'      => null,
            'stats'        => $data['stats'],
            'funnel'       => $data['funnel'],
            'trend'        => $data['trend'],
            'created_by'   => optional($request->user())->email,
        ]);

        return redirect('/manager/dashboard?start_date=' . urlencode($data['filters']['start_date']) . '&end_date=' . urlencode($data['filters']['end_date']) . '&senior_email=' . urlencode($data['filters']['senior_email']))
            ->with('status', 'Rapor snapshot kaydedildi.');
    }

    public function markSent(ManagerReport $managerReport): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeReport($managerReport);
        $managerReport->update(['send_status' => 'sent', 'sent_at' => now()]);

        return redirect()->back()->with('status', "Snapshot #{$managerReport->id} gonderildi olarak isaretlendi.");
    }

    public function markDraft(ManagerReport $managerReport): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeReport($managerReport);
        $managerReport->update(['send_status' => 'draft', 'sent_at' => null]);

        return redirect()->back()->with('status', "Snapshot #{$managerReport->id} draft durumuna alindi.");
    }

    public function markSentBulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        [$query, $redirectUrl] = $this->buildBulkQuery($request);
        $affected = $query->where('send_status', '!=', 'sent')->update(['send_status' => 'sent', 'sent_at' => now()]);

        return redirect($redirectUrl)->with('status', "{$affected} snapshot gonderildi olarak isaretlendi.");
    }

    public function markDraftBulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        [$query, $redirectUrl] = $this->buildBulkQuery($request);
        $affected = $query->where('send_status', '!=', 'draft')->update(['send_status' => 'draft', 'sent_at' => null]);

        return redirect($redirectUrl)->with('status', "{$affected} snapshot draft durumuna alindi.");
    }

    public function destroy(ManagerReport $managerReport): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeReport($managerReport);
        $managerReport->delete();

        return redirect()->back()->with('status', 'Rapor snapshot silindi.');
    }

    public function show(ManagerReport $managerReport)
    {
        $this->authorizeReport($managerReport);

        $previousReport = ManagerReport::query()
            ->where('report_type', $managerReport->report_type)
            ->where(function ($q) use ($managerReport): void {
                $managerReport->senior_email
                    ? $q->where('senior_email', $managerReport->senior_email)
                    : $q->whereNull('senior_email');
            })
            ->where('id', '<', $managerReport->id)
            ->latest('id')
            ->first();

        $stats         = is_array($managerReport->stats) ? $managerReport->stats : [];
        $previousStats = is_array(optional($previousReport)->stats) ? $previousReport->stats : [];
        $deltas        = [
            'monthly_revenue' => round(((float) ($stats['monthly_revenue'] ?? 0)) - ((float) ($previousStats['monthly_revenue'] ?? 0)), 2),
            'conversion_rate' => round(((float) ($stats['conversion_rate'] ?? 0)) - ((float) ($previousStats['conversion_rate'] ?? 0)), 1),
            'risk_score'      => round(((float) ($stats['risk_score'] ?? 0)) - ((float) ($previousStats['risk_score'] ?? 0)), 1),
        ];

        return view('manager.snapshot-show', [
            'report'         => $managerReport,
            'stats'          => $stats,
            'funnel'         => is_array($managerReport->funnel) ? $managerReport->funnel : [],
            'trend'          => is_array($managerReport->trend) ? $managerReport->trend : [],
            'previousReport' => $previousReport,
            'deltas'         => $deltas,
        ]);
    }

    public function exportCsv(ManagerReport $managerReport)
    {
        $stats  = is_array($managerReport->stats)  ? $managerReport->stats  : [];
        $funnel = is_array($managerReport->funnel) ? $managerReport->funnel : [];
        $trend  = is_array($managerReport->trend)  ? $managerReport->trend  : [];

        $filename = sprintf(
            'manager-snapshot-%d-%s_%s.csv',
            $managerReport->id,
            optional($managerReport->period_start)->toDateString(),
            optional($managerReport->period_end)->toDateString()
        );

        return CsvExportHelper::download($filename, function ($out) use ($managerReport, $stats, $funnel, $trend): void {
            fputcsv($out, ['MentorDE Manager Snapshot']);
            fputcsv($out, ['Snapshot ID', (string) $managerReport->id]);
            fputcsv($out, ['Type', (string) $managerReport->report_type]);
            fputcsv($out, ['Period', (string) optional($managerReport->period_start)->toDateString(), (string) optional($managerReport->period_end)->toDateString()]);
            fputcsv($out, ['Senior', (string) ($managerReport->senior_email ?: 'all')]);
            fputcsv($out, ['Created By', (string) ($managerReport->created_by ?: '-')]);
            fputcsv($out, []);

            fputcsv($out, ['KPI', 'Value']);
            foreach ($stats as $key => $value) {
                fputcsv($out, [(string) $key, is_array($value) ? json_encode($value) : (string) $value]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Funnel Step', 'Count', 'Rate']);
            foreach ($funnel as $row) {
                fputcsv($out, [(string) ($row['label'] ?? ''), (string) ($row['count'] ?? 0), (string) ($row['rate'] ?? 0)]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Trend Month', 'Revenue', 'Approval Count']);
            foreach ($trend as $row) {
                fputcsv($out, [(string) ($row['label'] ?? ''), (string) ($row['revenue'] ?? 0), (string) ($row['approval_count'] ?? 0)]);
            }
        });
    }

    public function print(ManagerReport $managerReport)
    {
        return view('manager.snapshot-print', [
            'report' => $managerReport,
            'stats'  => is_array($managerReport->stats)  ? $managerReport->stats  : [],
            'funnel' => is_array($managerReport->funnel) ? $managerReport->funnel : [],
            'trend'  => is_array($managerReport->trend)  ? $managerReport->trend  : [],
        ]);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function buildBulkQuery(Request $request): array
    {
        $request->validate([
            'snapshot_type'        => ['nullable', 'string', 'max:32'],
            'snapshot_start'       => ['nullable', 'date'],
            'snapshot_end'         => ['nullable', 'date'],
            'snapshot_send_status' => ['nullable', 'string', 'max:16'],
            'start_date'           => ['nullable', 'date'],
            'end_date'             => ['nullable', 'date'],
            'senior_email'         => ['nullable', 'email'],
        ]);

        $snapshotType       = (string) $request->input('snapshot_type', '');
        $snapshotStart      = (string) $request->input('snapshot_start', '');
        $snapshotEnd        = (string) $request->input('snapshot_end', '');
        $snapshotSendStatus = (string) $request->input('snapshot_send_status', '');

        $cid   = $this->companyId();
        $query = ManagerReport::query()->when($cid > 0, fn ($q) => $q->where('company_id', $cid));

        if ($snapshotType !== '') {
            $query->where('report_type', $snapshotType);
        }
        if ($snapshotStart !== '') {
            $query->whereDate('period_start', '>=', $snapshotStart);
        }
        if ($snapshotEnd !== '') {
            $query->whereDate('period_end', '<=', $snapshotEnd);
        }
        if ($snapshotSendStatus !== '') {
            $query->where('send_status', $snapshotSendStatus);
        }

        $redirectUrl = '/manager/dashboard'
            . '?start_date=' . urlencode((string) $request->input('start_date', ''))
            . '&end_date=' . urlencode((string) $request->input('end_date', ''))
            . '&senior_email=' . urlencode((string) $request->input('senior_email', ''))
            . '&snapshot_type=' . urlencode($snapshotType)
            . '&snapshot_start=' . urlencode($snapshotStart)
            . '&snapshot_end=' . urlencode($snapshotEnd)
            . '&snapshot_send_status=' . urlencode($snapshotSendStatus);

        return [$query, $redirectUrl];
    }
}
