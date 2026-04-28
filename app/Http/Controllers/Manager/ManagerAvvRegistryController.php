<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DataProcessingAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * AVV Registry — Auftragsverarbeitungsverträge yönetimi (DSGVO Art. 28).
 * 3. taraf sağlayıcılarla imzalanan veri işleme sözleşmelerinin arşivi.
 */
class ManagerAvvRegistryController extends Controller
{
    public function index()
    {
        $rows = DataProcessingAgreement::query()
            ->orderByRaw("FIELD(status, 'active','pending','expired','terminated')")
            ->orderBy('provider_name')
            ->get();

        $stats = [
            'total'        => $rows->count(),
            'active'       => $rows->where('status', 'active')->count(),
            'expiring'     => $rows->filter(fn ($r) => $r->isExpiringSoon())->count(),
            'expired'      => $rows->filter(fn ($r) => $r->isExpired())->count(),
            'missing_pdf'  => $rows->where('avv_pdf_path', null)->count(),
        ];

        return view('manager.avv.index', [
            'agreements' => $rows,
            'stats'      => $stats,
        ]);
    }

    public function create()
    {
        return view('manager.avv.form', ['avv' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAvv($request);
        $data['avv_pdf_path']        = $this->handleUpload($request);
        $data['updated_by_user_id']  = $request->user()?->id;
        $data['processed_categories']= $this->parseCategories($request);

        $row = DataProcessingAgreement::create($data);
        return redirect()->route('manager.avv.index')->with('flash_success', '✅ AVV kaydı eklendi: ' . $row->provider_name);
    }

    public function edit(DataProcessingAgreement $avv)
    {
        return view('manager.avv.form', ['avv' => $avv]);
    }

    public function update(Request $request, DataProcessingAgreement $avv)
    {
        $data = $this->validateAvv($request);
        $newPdf = $this->handleUpload($request);
        if ($newPdf !== null) {
            // Eski PDF'i sil
            if ($avv->avv_pdf_path && Storage::disk('local')->exists($avv->avv_pdf_path)) {
                Storage::disk('local')->delete($avv->avv_pdf_path);
            }
            $data['avv_pdf_path'] = $newPdf;
        }
        $data['updated_by_user_id']   = $request->user()?->id;
        $data['processed_categories'] = $this->parseCategories($request);

        $avv->update($data);
        return redirect()->route('manager.avv.index')->with('flash_success', '✅ AVV güncellendi: ' . $avv->provider_name);
    }

    public function destroy(DataProcessingAgreement $avv)
    {
        $name = $avv->provider_name;
        if ($avv->avv_pdf_path && Storage::disk('local')->exists($avv->avv_pdf_path)) {
            Storage::disk('local')->delete($avv->avv_pdf_path);
        }
        $avv->delete();
        return redirect()->route('manager.avv.index')->with('flash_success', '🗑️ Kaldırıldı: ' . $name);
    }

    public function downloadPdf(DataProcessingAgreement $avv)
    {
        if (!$avv->avv_pdf_path || !Storage::disk('local')->exists($avv->avv_pdf_path)) {
            abort(404, 'AVV PDF bulunamadı.');
        }
        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', $avv->provider_name) . '_AVV.pdf';
        return Storage::disk('local')->download($avv->avv_pdf_path, $name);
    }

    private function handleUpload(Request $request): ?string
    {
        $file = $request->file('avv_pdf');
        if (!$file) return null;
        $path = $file->store('avv-archive', 'local');
        return $path ?: null;
    }

    /**
     * @return array<int,string>
     */
    private function parseCategories(Request $request): array
    {
        $raw = (string) $request->input('processed_categories_text', '');
        return collect(preg_split('/\r?\n/', $raw))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function validateAvv(Request $request): array
    {
        return $request->validate([
            'provider_name'    => 'required|string|max:190',
            'provider_url'     => 'nullable|url|max:255',
            'contact_email'    => 'nullable|email|max:190',
            'signed_date'      => 'nullable|date',
            'expires_date'     => 'nullable|date|after_or_equal:signed_date',
            'country'          => 'nullable|string|max:100',
            'eu_based'         => 'boolean',
            'purpose_summary'  => 'nullable|string|max:500',
            'status'           => 'required|in:active,pending,expired,terminated',
            'notes'            => 'nullable|string|max:5000',
            'avv_pdf'          => 'nullable|file|mimes:pdf|max:10240',
        ]);
    }
}
