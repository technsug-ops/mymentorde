<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV dışa aktarma için tekrarlanan streamDownload + fopen boilerplate'ini kaldırır.
 *
 * Kullanım:
 *   return CsvExportHelper::download('report.csv', function (resource $out): void {
 *       fputcsv($out, ['Col1', 'Col2']);
 *       fputcsv($out, ['Val1', 'Val2']);
 *   });
 */
final class CsvExportHelper
{
    /**
     * CSV StreamedResponse döndürür.
     *
     * @param  string   $filename  Tarayıcıya gönderilecek dosya adı
     * @param  callable $writer    İçeriği yazan closure — parametre: resource $out (fopen handle)
     */
    public static function download(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            $writer($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
