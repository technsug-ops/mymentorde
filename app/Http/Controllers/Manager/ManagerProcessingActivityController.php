<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ProcessingActivity;
use Illuminate\Http\Request;

/**
 * ROPA (Verarbeitungsverzeichnis) — DSGVO Art. 30 zorunlu kayıt.
 * Manager şirketin tüm veri işleme aktivitelerini buradan yönetir.
 */
class ManagerProcessingActivityController extends Controller
{
    public function index()
    {
        $rows = ProcessingActivity::query()
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('manager.ropa.index', [
            'activities' => $rows,
            'legalBasis' => ProcessingActivity::LEGAL_BASIS,
        ]);
    }

    public function create()
    {
        return view('manager.ropa.form', [
            'activity'   => null,
            'legalBasis' => ProcessingActivity::LEGAL_BASIS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateActivity($request);
        $activity = ProcessingActivity::create(array_merge($data, [
            'updated_by_user_id' => $request->user()?->id,
        ]));
        return redirect()->route('manager.ropa.index')->with('flash_success', '✅ İşlem aktivitesi eklendi: ' . $activity->name);
    }

    public function edit(ProcessingActivity $activity)
    {
        return view('manager.ropa.form', [
            'activity'   => $activity,
            'legalBasis' => ProcessingActivity::LEGAL_BASIS,
        ]);
    }

    public function update(Request $request, ProcessingActivity $activity)
    {
        $data = $this->validateActivity($request);
        $activity->update(array_merge($data, [
            'updated_by_user_id' => $request->user()?->id,
        ]));
        return redirect()->route('manager.ropa.index')->with('flash_success', '✅ İşlem aktivitesi güncellendi: ' . $activity->name);
    }

    public function destroy(ProcessingActivity $activity)
    {
        $name = $activity->name;
        $activity->delete();
        return redirect()->route('manager.ropa.index')->with('flash_success', '🗑️ Kaldırıldı: ' . $name);
    }

    public function exportCsv()
    {
        $rows = ProcessingActivity::query()->orderBy('name')->get();
        $filename = 'ropa-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM (Excel UTF-8)
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Aktivite Adı', 'Sorumlu', 'Amaç', 'Veri Kategorileri', 'Kişi Kategorileri',
                'Alıcılar', 'Hukuki Dayanak', '3. Ülke Aktarımı', 'Saklama (gün)',
                'Güvenlik Önlemleri', 'Aktif', 'Son İnceleme',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->name, $r->responsible, $r->purpose,
                    implode(', ', (array) ($r->data_categories ?? [])),
                    implode(', ', (array) ($r->subject_categories ?? [])),
                    implode(', ', (array) ($r->recipients ?? [])),
                    ProcessingActivity::LEGAL_BASIS[$r->legal_basis] ?? $r->legal_basis,
                    $r->third_country_transfer ? ('Evet — ' . $r->third_country_country) : 'Hayır',
                    $r->retention_days,
                    $r->security_measures,
                    $r->is_active ? 'Evet' : 'Hayır',
                    $r->reviewed_at?->format('d.m.Y'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /**
     * @return array<string,mixed>
     */
    private function validateActivity(Request $request): array
    {
        return $request->validate([
            'name'                   => 'required|string|max:190',
            'responsible'            => 'nullable|string|max:190',
            'purpose'                => 'nullable|string|max:5000',
            'data_categories'        => 'nullable|array',
            'data_categories.*'      => 'string|max:120',
            'subject_categories'     => 'nullable|array',
            'subject_categories.*'   => 'string|max:120',
            'recipients'             => 'nullable|array',
            'recipients.*'           => 'string|max:190',
            'legal_basis'            => 'nullable|string|max:100',
            'third_country_transfer' => 'boolean',
            'third_country_country'  => 'nullable|string|max:100',
            'retention_days'         => 'nullable|integer|min:0|max:36500',
            'security_measures'      => 'nullable|string|max:5000',
            'notes'                  => 'nullable|string|max:5000',
            'is_active'              => 'boolean',
        ]);
    }
}
