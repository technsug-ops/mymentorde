<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Services\AdvisorAssignmentService;
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
            'tracking_token'      => strtoupper(Str::random(12)),
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
