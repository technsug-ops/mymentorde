<?php

namespace App\Console\Commands;

use App\Models\AccountAccessLog;
use App\Models\RoleChangeAudit;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportAuditReportCommand extends Command
{
    protected $signature = 'export:audit-report
                            {--start= : Start date (Y-m-d), default last 30 days}
                            {--end= : End date (Y-m-d), default today}
                            {--type=all : all|role|account}
                            {--path= : Custom output file path}';

    protected $description = 'Export audit logs to CSV (role changes + account access)';

    public function handle(): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        if (!in_array($type, ['all', 'role', 'account'], true)) {
            $this->error('Gecersiz type. Gecerli degerler: all, role, account');
            return 1;
        }

        $startOpt = trim((string) ($this->option('start') ?? ''));
        $endOpt   = trim((string) ($this->option('end') ?? ''));
        $start    = $startOpt !== '' ? Carbon::parse($startOpt)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end      = $endOpt !== ''   ? Carbon::parse($endOpt)->endOfDay()     : Carbon::now()->endOfDay();

        if ($start->gt($end)) {
            $this->error('start tarihi end tarihinden buyuk olamaz.');
            return 1;
        }

        $rows = collect();

        if (in_array($type, ['all', 'role'], true)) {
            $roleRows = RoleChangeAudit::query()
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at')
                ->get()
                ->map(fn (RoleChangeAudit $r): array => [
                    'source'      => 'role_change_audit',
                    'time'        => optional($r->created_at)?->toDateTimeString(),
                    'actor'       => (string) ($r->actor_user_id ?? ''),
                    'action'      => (string) ($r->action ?? ''),
                    'target_type' => (string) ($r->target_type ?? ''),
                    'target_id'   => (string) ($r->target_id ?? ''),
                    'detail'      => json_encode($r->payload ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            $rows = $rows->concat($roleRows);
        }

        if (in_array($type, ['all', 'account'], true)) {
            $accountRows = AccountAccessLog::query()
                ->whereBetween('accessed_at', [$start, $end])
                ->orderBy('accessed_at')
                ->get()
                ->map(fn (AccountAccessLog $r): array => [
                    'source'      => 'account_access_log',
                    'time'        => optional($r->accessed_at)?->toDateTimeString(),
                    'actor'       => (string) ($r->accessed_by ?? ''),
                    'action'      => (string) ($r->access_type ?? ''),
                    'target_type' => 'account_vault',
                    'target_id'   => (string) ($r->account_id ?? ''),
                    'detail'      => json_encode(['student_id' => $r->student_id, 'ip_address' => $r->ip_address], JSON_UNESCAPED_UNICODE),
                ]);
            $rows = $rows->concat($accountRows);
        }

        $rows = $rows->sortBy(fn ($row) => (string) ($row['time'] ?? ''))->values();

        $reportDir = storage_path('app/reports');
        if (!File::isDirectory($reportDir)) {
            File::makeDirectory($reportDir, 0755, true);
        }

        $pathOption = trim((string) ($this->option('path') ?? ''));
        $filePath   = $pathOption !== ''
            ? $pathOption
            : $reportDir . DIRECTORY_SEPARATOR . 'audit-report-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv';

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            $this->error('CSV dosyasi olusturulamadi: ' . $filePath);
            return 1;
        }

        fputcsv($handle, ['source', 'time', 'actor', 'action', 'target_type', 'target_id', 'detail']);
        foreach ($rows as $row) {
            fputcsv($handle, [$row['source'] ?? '', $row['time'] ?? '', $row['actor'] ?? '', $row['action'] ?? '', $row['target_type'] ?? '', $row['target_id'] ?? '', $row['detail'] ?? '']);
        }
        fclose($handle);

        $this->info("Audit export tamamlandi | type: {$type} | rows: {$rows->count()}");
        $this->line("Dosya: {$filePath}");

        return 0;
    }
}
