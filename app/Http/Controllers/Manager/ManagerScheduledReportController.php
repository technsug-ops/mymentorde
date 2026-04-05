<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Models\ManagerScheduledReport;
use Illuminate\Http\Request;

class ManagerScheduledReportController extends Controller
{
    use ManagerDashboardTrait;

    public function index(Request $request)
    {
        $cid     = $this->companyId();
        $reports = ManagerScheduledReport::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->latest()
            ->paginate(20);

        return view('manager.scheduled-reports', [
            'reports'     => $reports,
            'reportTypes' => ManagerScheduledReport::REPORT_TYPE_LABELS,
            'frequencies' => ManagerScheduledReport::FREQUENCY_LABELS,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'report_type'  => ['required', 'in:weekly_summary,monthly_summary,senior_performance'],
            'frequency'    => ['required', 'in:weekly,monthly'],
            'day_of_week'  => ['nullable', 'integer', 'min:1', 'max:7'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'send_to'      => ['required', 'string', 'max:2000'],
            'senior_filter'=> ['nullable', 'email'],
        ]);

        $cid    = $this->companyId();
        $sendTo = collect(explode(',', (string) $data['send_to']))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
            ->values()->all();

        ManagerScheduledReport::create([
            'company_id'   => $cid,
            'report_type'  => $data['report_type'],
            'frequency'    => $data['frequency'],
            'day_of_week'  => (int) ($data['day_of_week'] ?? 1),
            'day_of_month' => (int) ($data['day_of_month'] ?? 1),
            'send_to'      => $sendTo,
            'senior_filter'=> $data['senior_filter'] ?? null,
            'is_active'    => true,
            'created_by'   => optional($request->user())->email,
        ]);

        return redirect('/manager/scheduled-reports')->with('status', 'Zamanlanmış rapor oluşturuldu.');
    }

    public function update(Request $request, ManagerScheduledReport $scheduledReport): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'report_type'  => ['required', 'in:weekly_summary,monthly_summary,senior_performance'],
            'frequency'    => ['required', 'in:weekly,monthly'],
            'day_of_week'  => ['nullable', 'integer', 'min:1', 'max:7'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'send_to'      => ['required', 'string', 'max:2000'],
            'senior_filter'=> ['nullable', 'email'],
        ]);

        $sendTo = collect(explode(',', (string) $data['send_to']))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
            ->values()->all();

        $scheduledReport->update([
            'report_type'  => $data['report_type'],
            'frequency'    => $data['frequency'],
            'day_of_week'  => (int) ($data['day_of_week'] ?? 1),
            'day_of_month' => (int) ($data['day_of_month'] ?? 1),
            'send_to'      => $sendTo,
            'senior_filter'=> $data['senior_filter'] ?? null,
        ]);

        return redirect('/manager/scheduled-reports')->with('status', 'Zamanlanmış rapor güncellendi.');
    }

    public function destroy(ManagerScheduledReport $scheduledReport): \Illuminate\Http\RedirectResponse
    {
        $scheduledReport->delete();
        return redirect('/manager/scheduled-reports')->with('status', 'Zamanlanmış rapor silindi.');
    }

    public function toggle(ManagerScheduledReport $scheduledReport): \Illuminate\Http\RedirectResponse
    {
        $scheduledReport->update(['is_active' => ! $scheduledReport->is_active]);
        $state = $scheduledReport->is_active ? 'aktif' : 'pasif';
        return redirect('/manager/scheduled-reports')->with('status', "Rapor {$state} edildi.");
    }
}
