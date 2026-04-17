<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\GuestTimelineMilestone;
use Carbon\Carbon;

/**
 * K3 — Guest başvuru takvimi milestone üretici + .ics export.
 */
class GuestTimelineService
{
    /**
     * Guest için kişisel milestone'ları oluştur / güncelle.
     * Sözleşme imzalandığında veya paket seçildiğinde çağrılır.
     */
    public function generateMilestones(GuestApplication $guest): void
    {
        $targetTerm = strtolower($guest->target_term ?? 'winter');
        $year       = now()->year;

        $targetDate = match ($targetTerm) {
            'summer' => Carbon::create($year + 1, 4, 1),
            default  => Carbon::create($year, 10, 1),
        };

        // Hedef tarih 3 aydan kısa süreyorsa bir sonraki yıla taşı
        if ($targetDate->lt(now()->addMonths(3))) {
            $targetDate->addYear();
        }

        $milestones = [
            // ── Kayıt (1 adım) ──
            ['code' => 'form_complete',      'label' => 'Kayıt Formu Tamamla',          'target_date' => now()->addDays(7),                  'category' => 'registration'],
            // ── Belgeler (1 adım) ──
            ['code' => 'docs_upload',        'label' => 'Zorunlu Belgeleri Yükle',      'target_date' => now()->addDays(14),                 'category' => 'documents'],
            // ── Sözleşme (2 adım) ──
            ['code' => 'package_select',     'label' => 'Paket Seç',                    'target_date' => now()->addDays(10),                 'category' => 'contract'],
            ['code' => 'contract_sign',      'label' => 'Sözleşme İmzala',              'target_date' => now()->addDays(21),                 'category' => 'contract'],
            // ── Üniversite (5 adım) ──
            ['code' => 'uni_assist_reg',     'label' => 'Uni-Assist Kaydı Oluştur',     'target_date' => $targetDate->copy()->subMonths(5),  'category' => 'university'],
            ['code' => 'uni_docs_prep',      'label' => 'Başvuru Belgelerini Hazırla',   'target_date' => $targetDate->copy()->subMonths(4)->subDays(15), 'category' => 'university'],
            ['code' => 'uni_apply',          'label' => 'Üniversite Başvurusu Yap',      'target_date' => $targetDate->copy()->subMonths(4),  'category' => 'university'],
            ['code' => 'uni_fee_pay',        'label' => 'Başvuru Ücretini Öde',          'target_date' => $targetDate->copy()->subMonths(3)->subDays(15), 'category' => 'university'],
            ['code' => 'uni_acceptance',     'label' => 'Kabul Mektubu (Zulassung)',     'target_date' => $targetDate->copy()->subMonths(3),  'category' => 'university'],
            // ── Vize (6 adım) ──
            ['code' => 'blocked_account',    'label' => 'Sperrkonto (Bloke Hesap) Aç',  'target_date' => $targetDate->copy()->subMonths(3),  'category' => 'visa'],
            ['code' => 'health_insurance',   'label' => 'Sağlık Sigortası Yaptır',      'target_date' => $targetDate->copy()->subMonths(2)->subDays(15), 'category' => 'visa'],
            ['code' => 'visa_docs_prep',     'label' => 'Vize Evraklarını Hazırla',      'target_date' => $targetDate->copy()->subMonths(2)->subDays(10), 'category' => 'visa'],
            ['code' => 'visa_appointment',   'label' => 'Vize Randevusu Al',             'target_date' => $targetDate->copy()->subMonths(2),  'category' => 'visa'],
            ['code' => 'visa_apply',         'label' => 'Vize Başvurusu Yap',            'target_date' => $targetDate->copy()->subWeeks(6),   'category' => 'visa'],
            ['code' => 'visa_approval',      'label' => 'Vize Onayı Al',                 'target_date' => $targetDate->copy()->subWeeks(4)->subDays(3), 'category' => 'visa'],
            // ── Seyahat (3 adım) ──
            ['code' => 'flight_book',        'label' => 'Uçak Bileti Al',               'target_date' => $targetDate->copy()->subWeeks(4),   'category' => 'travel'],
            ['code' => 'accommodation',      'label' => 'Konaklama / Yurt Kesinleştir', 'target_date' => $targetDate->copy()->subWeeks(3),   'category' => 'travel'],
            ['code' => 'travel_checklist',   'label' => 'Bavul & Checklist Hazırla',     'target_date' => $targetDate->copy()->subWeeks(2),   'category' => 'travel'],
            // ── Varış (4 adım) ──
            ['code' => 'arrival',            'label' => "Almanya'ya Varış",             'target_date' => $targetDate->copy()->subWeeks(1),   'category' => 'arrival'],
            ['code' => 'anmeldung',          'label' => 'Adres Tescili (Anmeldung)',     'target_date' => $targetDate->copy()->subDays(3),    'category' => 'arrival'],
            ['code' => 'bank_account',       'label' => 'Banka Hesabı Aç',              'target_date' => $targetDate->copy()->subDays(1),    'category' => 'arrival'],
            ['code' => 'immatrikulation',    'label' => 'Üniversite Kayıt (Immatrikulation)', 'target_date' => $targetDate->copy(),            'category' => 'arrival'],
        ];

        foreach ($milestones as $idx => $m) {
            GuestTimelineMilestone::updateOrCreate(
                ['guest_application_id' => $guest->id, 'milestone_code' => $m['code']],
                [
                    'label'      => $m['label'],
                    'target_date'=> $m['target_date']->toDateString(),
                    'category'   => $m['category'],
                    'sort_order' => $idx,
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * .ics (iCalendar) formatında takvim verisi üret.
     */
    public function exportIcs(GuestApplication $guest): string
    {
        $milestones = GuestTimelineMilestone::where('guest_application_id', $guest->id)
            ->orderBy('target_date')
            ->get();

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//MentorDE//Student Calendar//TR\r\nCALSCALE:GREGORIAN\r\n";

        foreach ($milestones as $m) {
            $date = Carbon::parse($m->target_date)->format('Ymd');
            $uid  = 'mentorde-' . $m->id . '-' . $guest->id . '@mentorde.de';
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTART;VALUE=DATE:{$date}\r\n";
            $ics .= "SUMMARY:{$m->label}\r\n";
            $ics .= "DESCRIPTION:MentorDE Almanya Başvuru Takvimi\r\n";
            $ics .= "CATEGORIES:" . strtoupper($m->category) . "\r\n";
            if ($m->completed_at) {
                $ics .= "STATUS:COMPLETED\r\n";
            }
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR";
        return $ics;
    }

    /**
     * Milestone'u tamamlandı olarak işaretle.
     */
    public function complete(GuestApplication $guest, string $milestoneCode): void
    {
        GuestTimelineMilestone::where('guest_application_id', $guest->id)
            ->where('milestone_code', $milestoneCode)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    /**
     * Milestone bazında ilerleme hesapla: [current, total, percent]
     * Binary milestone'lar (paket, sözleşme) → 0 veya 100.
     * Adımlı milestone'lar (form=8 step, docs=N required) → kademeli.
     */
    public function computeProgress(GuestApplication $guest): array
    {
        $progress = [];

        // ── form_complete: 8 kayıt formu adımı ──
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $companyId = (int) ($guest->company_id ?: 0);
        try {
            $groups = app(\App\Services\GuestRegistrationFieldSchemaService::class)->groups($companyId);
        } catch (\Throwable $e) {
            $groups = \App\Support\GuestRegistrationFormCatalog::groups();
        }
        $totalSteps = count($groups);
        $filledSteps = 0;
        foreach ($groups as $group) {
            $fields = $group['fields'] ?? [];
            $requiredFields = array_filter($fields, fn ($f) => !empty($f['required']));
            if (empty($requiredFields)) {
                $filledSteps++;
                continue;
            }
            $allFilled = true;
            foreach ($requiredFields as $f) {
                $key = $f['key'] ?? '';
                if ($key === '' || empty(trim((string) ($draft[$key] ?? '')))) {
                    $allFilled = false;
                    break;
                }
            }
            if ($allFilled) {
                $filledSteps++;
            }
        }
        $progress['form_complete'] = ['current' => $filledSteps, 'total' => $totalSteps, 'pct' => $totalSteps > 0 ? round($filledSteps / $totalSteps * 100) : 0];

        // ── docs_upload: zorunlu belge sayısı ──
        $appType = trim((string) ($guest->application_type ?? ''));
        $ownerId = trim((string) ($guest->converted_student_id ?? '')) !== ''
            ? (string) $guest->converted_student_id
            : 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        $requiredCodes = collect();
        if ($appType !== '') {
            $requiredCodes = \App\Models\GuestRequiredDocument::where('application_type', $appType)
                ->where('is_active', true)->where('is_required', true)
                ->pluck('category_code')
                ->map(fn ($v) => strtoupper(trim((string) $v)))
                ->filter()->unique()->values();
        }
        $uploadedCodes = \App\Models\Document::where('student_id', $ownerId)
            ->whereIn('status', ['uploaded', 'approved'])
            ->with('category:id,code')->get()
            ->map(fn ($d) => strtoupper(trim((string) ($d->category->code ?? ''))))
            ->filter()->unique()->values();
        $totalDocs = $requiredCodes->count();
        $doneDocs = $requiredCodes->intersect($uploadedCodes)->count();
        $progress['docs_upload'] = ['current' => $doneDocs, 'total' => $totalDocs, 'pct' => $totalDocs > 0 ? round($doneDocs / $totalDocs * 100) : 0];

        // ── package_select: binary ──
        $progress['package_select'] = ['current' => !empty($guest->selected_package_code) ? 1 : 0, 'total' => 1, 'pct' => !empty($guest->selected_package_code) ? 100 : 0];

        // ── contract_sign: 4 aşama (Talep → İnceleme → İmza → Onay) ──
        $contractStages = [
            'pending_manager'  => 1, // Talep edildi
            'requested'        => 2, // İnceleme / gönderildi
            'signed_uploaded'  => 3, // İmzalandı
            'approved'         => 4, // Onaylandı
            'active'           => 4,
        ];
        $cStatus = strtolower(trim((string) ($guest->contract_status ?? 'not_requested')));
        $cStep = $contractStages[$cStatus] ?? 0;
        $cTotal = 4;
        $progress['contract_sign'] = [
            'current' => $cStep,
            'total'   => $cTotal,
            'pct'     => $cTotal > 0 ? round($cStep / $cTotal * 100) : 0,
            'stages'  => ['Talep', 'İnceleme', 'İmza', 'Onay'],
            'stage'   => $cStep,
        ];

        return $progress;
    }

    /**
     * Retroaktif sync: geçmişte yapılmış aksiyonların milestone'larını otomatik tamamla.
     * Timeline sayfası her açıldığında çağrılır (idempotent — zaten tamamlanan milestone tekrar güncellenmez).
     */
    public function syncCompletions(GuestApplication $guest): void
    {
        // Kayıt formu gönderilmişse
        if ($guest->registration_form_submitted_at) {
            $this->complete($guest, 'form_complete');
        }

        // Tüm zorunlu belgeler yüklenmişse (docs_ready flag'i controller'da hesaplanır)
        if ($guest->docs_ready) {
            $this->complete($guest, 'docs_upload');
        }

        // Paket seçilmişse
        if (!empty($guest->selected_package_code)) {
            $this->complete($guest, 'package_select');
        }

        // Sözleşme imzalanmışsa
        if (in_array($guest->contract_status ?? '', ['signed_uploaded', 'approved', 'active'], true)) {
            $this->complete($guest, 'contract_sign');
        }
    }
}
