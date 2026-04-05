<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public readonly string $reportType,  // 'senior_performance' | 'dealer_commission' | 'kpi_monthly'
        public readonly int    $companyId,
        public readonly array  $params,
        public readonly string $cacheKey,
    ) {}

    public function handle(): void
    {
        $data = match ($this->reportType) {
            'senior_performance' => $this->seniorPerformanceData(),
            'dealer_commission'  => $this->dealerCommissionData(),
            'kpi_monthly'        => $this->kpiMonthlyData(),
            default              => [],
        };

        Cache::put($this->cacheKey, $data, now()->addHours(6));
    }

    private function seniorPerformanceData(): array
    {
        $month = $this->params['month'] ?? now()->format('Y-m');
        return \App\Models\SeniorPerformanceSnapshot::where('company_id', $this->companyId)
            ->where('period_month', $month)
            ->with('senior:id,name,email')
            ->get()
            ->toArray();
    }

    private function dealerCommissionData(): array
    {
        return \App\Models\DealerStudentRevenue::where('company_id', $this->companyId)
            ->with('dealer:id,full_name,email')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->toArray();
    }

    private function kpiMonthlyData(): array
    {
        $month = $this->params['month'] ?? now()->format('Y-m');
        return [
            'period'       => $month,
            'new_guests'   => \App\Models\GuestApplication::where('company_id', $this->companyId)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])->count(),
            'new_students' => \App\Models\User::where('role', 'student')
                ->where('company_id', $this->companyId)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])->count(),
        ];
    }
}
