<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform Owner — DB Backup yönetimi.
 *
 * Otomatik schedule: Daily 03:00 (routes/console.php → backup:create --keep=14)
 * Storage: storage/app/backups/db_*.sql.gz
 *
 * Manuel tetikleme + listeleme + indirme + silme:
 *   /platform/backups
 */
class PlatformBackupController extends Controller
{
    public function index(): View
    {
        $disk = Storage::disk('local');
        $files = $disk->files('backups');

        $backups = collect($files)
            ->filter(fn ($f) => preg_match('/db_.+\.sql\.gz$/', basename($f)))
            ->map(function ($f) use ($disk) {
                $size = $disk->size($f);
                $mtime = $disk->lastModified($f);
                return [
                    'name'        => basename($f),
                    'path'        => $f,
                    'size'        => $size,
                    'size_label'  => $this->humanSize($size),
                    'created_at'  => Carbon::createFromTimestamp($mtime),
                    'age_label'   => Carbon::createFromTimestamp($mtime)->diffForHumans(),
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        $totalSize = $backups->sum('size');
        $latest = $backups->first();

        return view('platform.backups.index', [
            'backups'        => $backups,
            'totalSize'      => $this->humanSize($totalSize),
            'totalCount'     => $backups->count(),
            'latest'         => $latest,
            'latestAge'      => $latest ? Carbon::parse($latest['created_at'])->diffInHours(Carbon::now()) : null,
            'scheduleInfo'   => 'Günlük 03:00 (Europe/Berlin) · 14 gün retention',
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        try {
            Artisan::call('backup:create', ['--keep' => 14]);
            $out = trim((string) Artisan::output());

            \App\Models\PlatformAuditLog::record(
                'platform.backup.manual',
                [
                    'triggered_by' => $request->user()?->email,
                    'output'       => mb_substr($out, 0, 500),
                ],
                \App\Models\PlatformAuditLog::SEVERITY_INFO
            );

            return redirect()->route('platform.backups.index')
                ->with('success', '✓ Manuel yedek alındı.');
        } catch (\Throwable $e) {
            return redirect()->route('platform.backups.index')
                ->with('error', 'Yedek alınamadı: ' . $e->getMessage());
        }
    }

    public function download(string $filename): StreamedResponse
    {
        // Path traversal koruması
        if (!preg_match('/^db_[\d_-]+\.sql\.gz$/', $filename)) {
            abort(404);
        }

        $disk = Storage::disk('local');
        $path = 'backups/' . $filename;
        if (!$disk->exists($path)) {
            abort(404);
        }

        \App\Models\PlatformAuditLog::record(
            'platform.backup.downloaded',
            [
                'file'         => $filename,
                'triggered_by' => auth()->user()?->email,
                'ip'           => request()->ip(),
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        return $disk->download($path, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function destroy(string $filename): RedirectResponse
    {
        if (!preg_match('/^db_[\d_-]+\.sql\.gz$/', $filename)) {
            abort(404);
        }

        $disk = Storage::disk('local');
        $path = 'backups/' . $filename;
        if (!$disk->exists($path)) {
            return redirect()->route('platform.backups.index')->with('error', 'Yedek bulunamadı.');
        }

        $disk->delete($path);

        \App\Models\PlatformAuditLog::record(
            'platform.backup.deleted',
            [
                'file'         => $filename,
                'triggered_by' => auth()->user()?->email,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        return redirect()->route('platform.backups.index')
            ->with('success', "✓ {$filename} silindi.");
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
        return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
    }
}
