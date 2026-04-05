<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ProjectExportController extends Controller
{
    public function safe(Request $request): StreamedResponse
    {
        Log::info('Config export (safe) indirildi', [
            'user_id'  => auth()->id(),
            'user'     => auth()->user()?->email,
            'ip'       => $request->ip(),
            'at'       => now()->toDateTimeString(),
        ]);

        return $this->downloadExport(false);
    }

    public function full(Request $request): Response
    {
        abort(403, 'Full export devre disi birakildi. Secretlari icerdigi icin production ortaminda kullanilmaz.');
    }

    private function downloadExport(bool $full): StreamedResponse
    {
        $projectRoot = base_path();
        $timestamp = Carbon::now()->format('Ymd_His');
        $mode = $full ? 'full' : 'safe';
        $zipName = "mentorde_code_export_{$mode}_{$timestamp}.zip";

        $exportDir = storage_path('app/exports');
        File::ensureDirectoryExists($exportDir);

        $zipPath = $exportDir.DIRECTORY_SEPARATOR.$zipName;
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $excludedDirs = [
            '.git',
            '.idea',
            '.vscode',
            'node_modules',
            'storage',
        ];

        if (! $full) {
            $excludedDirs[] = 'vendor';
        }

        $excludedFiles = [
            '*.sqlite',
            '*.zip',
        ];

        if (! $full) {
            $excludedFiles[] = '.env';
            $excludedFiles[] = '*service-account*.json';
        }

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'ZIP dosyasi olusturulamadi.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $fullPath = $item->getPathname();
            $relativePath = ltrim(str_replace($projectRoot, '', $fullPath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($this->isExcludedPath($relativePath, $excludedDirs)) {
                continue;
            }

            if ($this->isExcludedFile($relativePath, $excludedFiles)) {
                continue;
            }

            $zip->addFile($fullPath, str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    private function isExcludedPath(string $relativePath, array $excludedDirs): bool
    {
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        foreach ($excludedDirs as $dir) {
            $dir = trim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
            if ($normalized === $dir || str_starts_with($normalized, $dir.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedFile(string $relativePath, array $patterns): bool
    {
        $basename = basename($relativePath);

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $basename, FNM_CASEFOLD) || fnmatch($pattern, $relativePath, FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }
}

