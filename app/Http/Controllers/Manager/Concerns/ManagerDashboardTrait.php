<?php

namespace App\Http\Controllers\Manager\Concerns;

use App\Models\ManagerReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ManagerDashboardTrait
{
    protected function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    protected function authorizeReport(ManagerReport $report): void
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) ($report->company_id ?? 0) !== $cid, 403);
    }

    protected function resolveFilters(Request $request): array
    {
        $request->validate([
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'senior_email' => ['nullable', 'email'],
        ]);

        $now          = Carbon::now();
        $startInput   = (string) $request->query('start_date', '');
        $endInput     = (string) $request->query('end_date', '');
        $selectedSenior = (string) $request->query('senior_email', '');

        $monthStart = $startInput !== '' ? Carbon::parse($startInput)->startOfDay() : $now->copy()->startOfMonth();
        $monthEnd   = $endInput   !== '' ? Carbon::parse($endInput)->endOfDay()     : $now->copy()->endOfMonth();

        return [$monthStart, $monthEnd, $selectedSenior];
    }

    protected function resolveSnapshotFilters(Request $request): array
    {
        return [
            'snapshot_type'        => (string) $request->query('snapshot_type', ''),
            'snapshot_start'       => (string) $request->query('snapshot_start', ''),
            'snapshot_end'         => (string) $request->query('snapshot_end', ''),
            'snapshot_send_status' => (string) $request->query('snapshot_send_status', ''),
        ];
    }
}
