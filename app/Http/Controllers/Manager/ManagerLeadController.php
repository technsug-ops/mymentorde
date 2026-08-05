<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\AdvisorAssignmentService;
use App\Services\LeadTransferService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Elle aday girişi.
 *
 * NEDEN GEREKLİ: sisteme aday sokmanın tek yolu public başvuru formuydu.
 * Telefonda konuşulan bir öğrenciyi kaydetmek için firmanın formu öğrenci
 * adına doldurması gerekiyordu — CAPTCHA ve e-posta doğrulaması yüzünden
 * pratikte zahmetli.
 *
 * Kayıt İÇİNDE BULUNULAN ŞİRKET BAĞLAMINA yazılır. Personel şirket
 * değiştiriciyle partnere geçmişse aday partnere düşer — istenen davranış bu.
 */
class ManagerLeadController extends Controller
{
    public function __construct(
        private readonly AdvisorAssignmentService $advisors,
    ) {
    }

    public function create(): View
    {
        return view('manager.leads.create');
    }

    /**
     * Adayı başka bir firmaya devret.
     *
     * BURADA, platform konsolunda DEĞİL: hangi adayın hangi firmaya taşınacağı
     * operasyonel bir karardır ve süreci yürüten firmayı ilgilendirir. Yazılım
     * servisi sağlayıcısının müşterisinin adayını taşıması savunulamaz.
     *
     * YETKİ: kişi hem adayın MEVCUT firmasını hem HEDEF firmayı görebilmeli.
     * Böylece partner ne MentorDE'den aday çekebilir ne başka partnere itebilir;
     * yalnızca gözettiği firmalar arasında taşıma yapar.
     */
    public function transferForm(Request $request): View
    {
        return view('manager.leads.transfer', [
            'companies' => $this->visibleCompanies($request->user()),
        ]);
    }

    public function transfer(Request $request, LeadTransferService $transfers): RedirectResponse
    {
        $data = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
            'company_id'     => ['required', 'integer', 'exists:companies,id'],
        ], [
            'application_id.required' => 'Aday numarası girilmedi.',
            'company_id.required'     => 'Hedef firma seçilmedi.',
        ]);

        $visible = $request->user()->visibleCompanyIds();

        $lead = GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('id', (int) $data['application_id'])
            ->first();

        // Göremediği adayın varlığını da sızdırmamak için aynı mesaj.
        if (!$lead || !in_array((int) $lead->company_id, $visible, true)) {
            return back()->withInput()->withErrors([
                'application_id' => 'Bu numarayla erişebileceğiniz bir aday bulunamadı.',
            ]);
        }

        if (!in_array((int) $data['company_id'], $visible, true)) {
            return back()->withInput()->withErrors([
                'company_id' => 'Bu firmaya devretme yetkiniz yok.',
            ]);
        }

        $target = Company::query()->findOrFail((int) $data['company_id']);

        try {
            $result = $transfers->transfer($lead, $target);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['company_id' => $e->getMessage()]);
        }

        $message = 'Aday #' . $lead->id . ' → ' . ($target->brand_name ?: $target->name) . ' devredildi.';

        if ($result['senior_cleared']) {
            $message .= ' Eski danışman ataması kaldırıldı — yeni firma kendi danışmanını atamalı.';
        }

        return redirect()->route('manager.leads.transfer.form')->with('status', $message);
    }

    /** @return \Illuminate\Support\Collection<int,Company> */
    private function visibleCompanies(?User $user)
    {
        $ids = $user?->visibleCompanyIds() ?? [];

        if ($ids === []) {
            return collect();
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_name']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'          => ['required', 'string', 'max:120'],
            'last_name'           => ['required', 'string', 'max:120'],
            'email'               => ['required', config('validation.email'), 'max:190'],
            'phone'               => ['nullable', 'string', 'max:60'],
            'application_type'    => ['required', 'string', 'max:64'],
            'application_country' => ['nullable', 'string', 'max:120'],
            'target_term'         => ['nullable', 'string', 'max:60'],
            'target_city'         => ['nullable', 'string', 'max:100'],
            'lead_source'         => ['nullable', 'string', 'max:64'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ], [
            'email.required' => 'E-posta zorunlu — öğrencinin portal hesabı bu adresle açılır.',
        ]);

        $email = strtolower(trim((string) $data['email']));

        // Aynı şirkette aynı e-postayla açık aday varsa ikinci kayıt açma.
        // Kapsamlı sorgu bilinçli: BAŞKA firmadaki kaydı görmemeli, görse
        // "bu kişi rakipte de var" bilgisini sızdırırdı.
        $existing = GuestApplication::query()
            ->where('email', $email)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->first(['id']);

        if ($existing) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Bu e-postayla açık bir aday kaydı zaten var (#' . $existing->id . ').']);
        }

        $companyId = (int) (TenantContext::writeId() ?? 0);

        $lead = GuestApplication::query()->create([
            // Modeldeki üretici: benzersizlik KAPSAM DIŞI kontrol edilir,
            // aksi halde başka firmadaki kodla çakışabilirdi.
            'tracking_token'      => GuestApplication::generateTrackingToken(),
            'first_name'          => trim((string) $data['first_name']),
            'last_name'           => trim((string) $data['last_name']),
            'email'               => $email,
            'phone'               => trim((string) ($data['phone'] ?? '')) ?: null,
            'application_type'    => (string) $data['application_type'],
            'application_country' => trim((string) ($data['application_country'] ?? 'de')) ?: 'de',
            'target_term'         => trim((string) ($data['target_term'] ?? '')) ?: null,
            'target_city'         => trim((string) ($data['target_city'] ?? '')) ?: null,
            // Elle girilen kayıt organik değil — kaynağı açıkça işaretle.
            'lead_source'         => trim((string) ($data['lead_source'] ?? '')) ?: 'manual_entry',
            'notes'               => trim((string) ($data['notes'] ?? '')) ?: null,
            'lead_status'         => 'new',
            // Danışman OPERASYON şirketinden gelir — partnerin kendi danışmanı yok.
            'assigned_senior_email' => $this->advisors->pickFor($companyId, (string) $data['application_type']),
        ]);

        return redirect('/manager/dashboard')
            ->with('status', 'Aday kaydı oluşturuldu (#' . $lead->id . ').'
                . ($lead->assigned_senior_email
                    ? ' Danışman: ' . $lead->assigned_senior_email
                    : ' Otomatik danışman bulunamadı — elle atanmalı.'));
    }
}
