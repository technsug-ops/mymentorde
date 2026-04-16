<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Toplu Kayıt Yükleme — eski aday öğrenci kayıtlarını CSV formatında
 * sisteme aktarır. UTF-8 BOM destekli (Excel uyumlu).
 *
 * Akış:
 *   1. index    → upload ekranı + CSV şablonu indirme linki
 *   2. template → boş CSV şablonu (UTF-8 BOM, header satırı)
 *   3. preview  → upload edilen CSV'yi parse eder, validation yapar,
 *                 PREVIEW tablosunu session'a koyar (henüz INSERT yok)
 *   4. commit   → preview doğrulandıktan sonra gerçek INSERT yapılır
 */
class BulkImportController extends Controller
{
    private const COLUMNS = [
        'first_name', 'last_name', 'email', 'phone',
        'birth_date', 'gender', 'application_country',
        'application_type', 'target_city', 'target_term',
        'lead_source', 'dealer_code', 'campaign_code', 'language_level', 'notes',
    ];

    public function index()
    {
        return view('manager.bulk-import.index', [
            'columns' => self::COLUMNS,
            'preview' => session('bulk_import_preview'),
        ]);
    }

    public function template()
    {
        $filename = 'toplu_aday_ogrenci_sablonu.csv';
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, self::COLUMNS, ',', '"');
            // Örnek satır
            fputcsv($out, [
                'Ayşe', 'Demir', 'ayse.demir@ornek.com', '+90 555 123 45 67',
                '2003-05-15', 'female', 'de',
                'bachelor', 'Berlin', 'WS 2026',
                'organic', '', '', 'B2', 'Daha önce görüşüldü',
            ], ',', '"');
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:4096']]);
        $path = $request->file('file')->getRealPath();

        $rows = $this->parseCsv($path);
        if (empty($rows)) {
            return back()->withErrors(['file' => 'Dosya boş veya okunamadı.']);
        }

        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), array_shift($rows));
        $missing = array_diff(['first_name', 'last_name', 'email'], $header);
        if (!empty($missing)) {
            return back()->withErrors(['file' => 'Zorunlu kolonlar eksik: ' . implode(', ', $missing)]);
        }

        $existingEmails = GuestApplication::whereIn('email', $this->extractEmailColumn($rows, $header))
            ->pluck('email')->map(fn ($e) => strtolower((string) $e))->toArray();

        $preview = [];
        $okCount = 0;
        $errCount = 0;
        $duplicateCount = 0;
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($header as $k => $col) {
                $data[$col] = isset($row[$k]) ? trim((string) $row[$k]) : '';
            }

            $errors = $this->validateRow($data);
            $isDuplicate = in_array(strtolower((string) ($data['email'] ?? '')), $existingEmails, true);
            if ($isDuplicate) {
                $errors[] = 'Bu email zaten kayıtlı (mükerrer).';
                $duplicateCount++;
            }

            $preview[] = [
                'line'       => $i + 2,
                'data'       => $data,
                'errors'     => $errors,
                'duplicate'  => $isDuplicate,
            ];
            empty($errors) ? $okCount++ : $errCount++;
        }

        session(['bulk_import_preview' => [
            'rows'        => $preview,
            'ok'          => $okCount,
            'err'         => $errCount,
            'duplicates'  => $duplicateCount,
            'total'       => count($preview),
            'uploaded_at' => now()->toDateTimeString(),
        ]]);

        return redirect()->route('manager.bulk-import.index')
            ->with('status', 'Önizleme hazır: ' . $okCount . ' geçerli, ' . $errCount . ' hatalı.');
    }

    public function commit(Request $request)
    {
        $preview = session('bulk_import_preview');
        if (!$preview || empty($preview['rows'])) {
            return back()->withErrors(['commit' => 'Önce dosya yükleyip önizleme yapmalısınız.']);
        }

        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 1;
        $inserted = 0;
        $skipped = 0;
        DB::beginTransaction();
        try {
            foreach ($preview['rows'] as $item) {
                if (!empty($item['errors'])) {
                    $skipped++;
                    continue;
                }
                $data = $item['data'];
                GuestApplication::create([
                    'company_id'             => $cid,
                    'first_name'             => $data['first_name'] ?? '',
                    'last_name'              => $data['last_name'] ?? '',
                    'email'                  => strtolower($data['email'] ?? ''),
                    'phone'                  => $data['phone'] ?? null,
                    'gender'                 => $data['gender'] ?? null,
                    'application_country'    => $data['application_country'] ?? null,
                    'application_type'       => $data['application_type'] ?? 'bachelor',
                    'target_city'            => $data['target_city'] ?? null,
                    'target_term'            => $data['target_term'] ?? null,
                    'language_level'         => $data['language_level'] ?? null,
                    'lead_source'            => $data['lead_source'] ?? 'bulk_import',
                    'dealer_code'            => $data['dealer_code'] ?: null,
                    'campaign_code'          => $data['campaign_code'] ?: null,
                    'lead_status'            => 'new',
                    'kvkk_consent'           => true,
                    'notes'                  => $data['notes'] ?? null,
                    'tracking_token'         => Str::random(40),
                ]);
                $inserted++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['commit' => 'Kaydetme hatası: ' . $e->getMessage()]);
        }

        session()->forget('bulk_import_preview');
        return redirect()->route('manager.bulk-import.index')
            ->with('status', "✓ $inserted kayıt içeri aktarıldı, $skipped kayıt atlandı (hatalı).");
    }

    public function reset()
    {
        session()->forget('bulk_import_preview');
        return redirect()->route('manager.bulk-import.index')->with('status', 'Önizleme temizlendi.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        // UTF-8 BOM strip
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }
        // Semicolon or comma auto-detect (Excel TR genellikle ;)
        $firstLine = explode("\n", $content)[0] ?? '';
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $rows = [];
        $handle = fopen('data://text/plain;base64,' . base64_encode($content), 'r');
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function extractEmailColumn(array $rows, array $header): array
    {
        $idx = array_search('email', $header, true);
        if ($idx === false) {
            return [];
        }
        return array_filter(array_map(fn ($r) => strtolower(trim((string) ($r[$idx] ?? ''))), $rows));
    }

    private function validateRow(array $data): array
    {
        $errors = [];
        foreach (['first_name', 'last_name', 'email'] as $req) {
            if (empty($data[$req] ?? '')) {
                $errors[] = "Zorunlu alan boş: $req";
            }
        }
        if (!empty($data['email'] ?? '') && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçersiz email formatı';
        }
        if (!empty($data['birth_date'] ?? '')) {
            try {
                Carbon::parse($data['birth_date']);
            } catch (\Throwable $e) {
                $errors[] = 'birth_date formatı hatalı (YYYY-MM-DD bekleniyor)';
            }
        }
        if (!empty($data['gender'] ?? '') && !in_array(strtolower($data['gender']), ['male', 'female', 'not_specified'], true)) {
            $errors[] = "gender sadece male/female/not_specified olabilir";
        }
        return $errors;
    }
}
