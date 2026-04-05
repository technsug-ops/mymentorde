<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ExportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $exportType,   // 'students' | 'dealers' | 'guests'
        public readonly int    $companyId,
        public readonly array  $filters,
        public readonly string $cacheKey,     // sonuç buraya yazılır
    ) {}

    public function handle(): void
    {
        $rows = match ($this->exportType) {
            'students' => $this->buildStudentRows(),
            'dealers'  => $this->buildDealerRows(),
            'guests'   => $this->buildGuestRows(),
            default    => [],
        };

        $csv = $this->arrayToCsv($rows);
        // 1 saat cache'le, controller polling yaparak okuyacak
        Cache::put($this->cacheKey, $csv, now()->addHour());
    }

    private function buildStudentRows(): array
    {
        $query = \App\Models\User::where('role', 'student')
            ->when($this->companyId > 0, fn($q) => $q->where('company_id', $this->companyId))
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('id');

        $rows = [['ID', 'Ad Soyad', 'E-Posta', 'Kayıt Tarihi']];
        foreach ($query->cursor() as $u) {
            $rows[] = [$u->id, $u->name, $u->email, $u->created_at?->format('Y-m-d')];
        }
        return $rows;
    }

    private function buildDealerRows(): array
    {
        $query = \App\Models\Dealer::when($this->companyId > 0, fn($q) => $q->where('company_id', $this->companyId))
            ->select('id', 'full_name', 'email', 'status', 'created_at')
            ->orderBy('id');

        $rows = [['ID', 'Ad Soyad', 'E-Posta', 'Durum', 'Kayıt']];
        foreach ($query->cursor() as $d) {
            $rows[] = [$d->id, $d->full_name, $d->email, $d->status, $d->created_at?->format('Y-m-d')];
        }
        return $rows;
    }

    private function buildGuestRows(): array
    {
        $query = \App\Models\GuestApplication::when($this->companyId > 0, fn($q) => $q->where('company_id', $this->companyId))
            ->select('id', 'first_name', 'last_name', 'email', 'lead_status', 'created_at')
            ->orderBy('id');

        $rows = [['ID', 'Ad', 'Soyad', 'E-Posta', 'Durum', 'Tarih']];
        foreach ($query->cursor() as $g) {
            $rows[] = [$g->id, $g->first_name, $g->last_name, $g->email, $g->lead_status, $g->created_at?->format('Y-m-d')];
        }
        return $rows;
    }

    private function arrayToCsv(array $rows): string
    {
        $buf = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel
        fwrite($buf, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($buf, $row, ';');
        }
        rewind($buf);
        $csv = stream_get_contents($buf);
        fclose($buf);
        return $csv;
    }
}
