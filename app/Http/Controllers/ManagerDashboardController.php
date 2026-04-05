<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Services\DashboardPayloadService;
use App\Support\CsvExportHelper;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    use ManagerDashboardTrait;

    public function __construct(private readonly DashboardPayloadService $payload) {}

    public function index(Request $request)
    {
        [$monthStart, $monthEnd, $selectedSenior] = $this->resolveFilters($request);
        $snapshotFilters = $this->resolveSnapshotFilters($request);
        $data = $this->payload->build($monthStart, $monthEnd, $selectedSenior, $snapshotFilters);

        return view('manager.dashboard', $data);
    }

    public function exportCsv(Request $request)
    {
        [$monthStart, $monthEnd, $selectedSenior] = $this->resolveFilters($request);
        $snapshotFilters = $this->resolveSnapshotFilters($request);
        $data = $this->payload->build($monthStart, $monthEnd, $selectedSenior, $snapshotFilters);

        $filename = sprintf(
            'manager-dashboard-%s_%s.csv',
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        return CsvExportHelper::download($filename, function ($out) use ($data): void {
            fputcsv($out, ['MentorDE Manager Dashboard']);
            fputcsv($out, ['Period', $data['filters']['start_date'], $data['filters']['end_date']]);
            fputcsv($out, ['Senior Filter', $data['filters']['senior_email'] !== '' ? $data['filters']['senior_email'] : 'all']);
            fputcsv($out, []);

            fputcsv($out, ['KPI', 'Value']);
            foreach ($data['stats'] as $key => $value) {
                fputcsv($out, [$key, (string) $value]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Funnel Step', 'Count', 'Rate']);
            foreach ($data['funnel'] as $row) {
                fputcsv($out, [$row['label'], (string) $row['count'], (string) $row['rate']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Senior', 'Email', 'Resolved Approvals', 'Notes Written', 'Last Action']);
            foreach ($data['seniorPerformance'] as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['email'],
                    (string) $row['resolved_approvals'],
                    (string) $row['notes_written'],
                    (string) ($row['last_action_at'] ?? '-'),
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Revenue Trend', 'Revenue', 'Approval Count']);
            foreach ($data['trend'] as $row) {
                fputcsv($out, [$row['label'], (string) $row['revenue'], (string) $row['approval_count']]);
            }
        });
    }

    public function reportPrint(Request $request)
    {
        [$monthStart, $monthEnd, $selectedSenior] = $this->resolveFilters($request);
        $snapshotFilters = $this->resolveSnapshotFilters($request);
        $data = $this->payload->build($monthStart, $monthEnd, $selectedSenior, $snapshotFilters);
        $data['generatedAt'] = now()->toDateTimeString();

        return view('manager.report-print', $data);
    }
}
