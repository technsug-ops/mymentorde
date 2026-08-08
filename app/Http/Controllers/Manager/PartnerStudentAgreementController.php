<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\PartnerAgreement;
use App\Models\PartnerStudentAgreement;
use App\Services\EventLogService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Öğrenci bazlı anlaşma — partnerin PORTALA ödeyeceği bedel.
 *
 * ⚠ Partnerin öğrencisinden ne aldığı burada YOK. O, partnerin kendi işi;
 * öğrenciyi portal white-label takip ediyor. Buradaki rakam iki firma
 * arasındaki alacak ve dönüşümün kapısı.
 *
 * ── İKİ YOL ─────────────────────────────────────────────────────────────
 * 1. STANDART BEDEL (tek adım). Çerçeve anlaşmada öğrenci başı bedel varsa
 *    o tutar zaten karşılıklı imzalanmıştır; partner tek tıkla kapatır.
 *    Teklif–kabul turu beklemek, üzerinde çoktan anlaşılmış bir rakam için
 *    akışı boş yere durdururdu.
 *
 * 2. FARKLI BEDEL (teklif–kabul). Standarttan sapan tutarı operasyon teklif
 *    eder, partner kabul eder. Partner kendi başına indirim yazamaz.
 */
class PartnerStudentAgreementController extends Controller
{
    public function __construct(private readonly EventLogService $eventLog)
    {
    }

    private function companyId(): int
    {
        return (int) (TenantContext::writeId() ?? 0);
    }

    /**
     * Partner, adayın anlaşmasını ÇERÇEVEDEKİ standart bedelle kapatır.
     *
     * Tutarı istek gövdesinden ALMIYORUZ — bilerek. Alsaydık partner kendi
     * bedelini yazabilirdi; oysa standart tutarın kaynağı imzalı çerçevedir.
     */
    public function settleAtStandardFee(Request $request, GuestApplication $guest): RedirectResponse
    {
        $partnerCompanyId = $this->companyId();

        $this->assertPartnerOwnsGuest($guest, $partnerCompanyId);

        if (PartnerStudentAgreement::isSettledForGuest((int) $guest->id)) {
            return back()->with('status', 'Bu aday için anlaşma zaten yapılmış.');
        }

        $agreement = PartnerAgreement::query()
            ->active()
            ->forPartner($partnerCompanyId)
            ->latest('signed_at')
            ->first();

        if (! $agreement) {
            return back()->withErrors([
                'agreement' => 'Yürürlükte imzalı bir çerçeve anlaşmanız yok. Önce çerçeve anlaşma imzalanmalı.',
            ]);
        }

        $fee = $agreement->standardFee();

        if ($fee === null) {
            return back()->withErrors([
                'agreement' => 'Çerçeve anlaşmada öğrenci başına standart bedel tanımlı değil. Operasyondan teklif isteyin.',
            ]);
        }

        $this->createAccepted($request, $guest, $agreement, $fee);

        return back()->with('status', sprintf(
            'Anlaşma kapatıldı — %s EUR. Adayı öğrenciye çevirebilirsiniz.',
            number_format($fee, 2, ',', '.')
        ));
    }

    /** Operasyon, standarttan farklı bir bedel teklif eder. */
    public function propose(Request $request, GuestApplication $guest): RedirectResponse
    {
        $companyId = $this->companyId();

        abort_if(Company::isPartnerPanel($companyId), 403, 'Teklifi operasyon verir.');

        $partnerCompanyId = (int) ($guest->company_id ?? 0);

        // Yetki sınırı: aday gerçekten bizim alt firmamızın mı?
        abort_unless(
            in_array($partnerCompanyId, Company::descendantIds($companyId), true),
            403,
            'Bu aday sizin partnerinizin değil.'
        );

        $data = $request->validate([
            'fee_eur' => ['required', 'numeric', 'min:0', 'max:999999'],
            'note'    => ['nullable', 'string', 'max:500'],
        ], [
            'fee_eur.required' => 'Tutar zorunlu.',
            'fee_eur.numeric'  => 'Tutar sayı olmalı (örn. 800 veya 800.50).',
        ]);

        // Açık teklif varsa yenisini üretmek yerine güncelle — aynı aday için
        // iki farklı rakam ekranda yan yana durursa hangisi geçerli belli olmaz.
        $existing = PartnerStudentAgreement::query()
            ->where('guest_application_id', $guest->id)
            ->where('status', PartnerStudentAgreement::STATUS_PROPOSED)
            ->first();

        $attributes = [
            'company_id'           => $companyId,
            'partner_company_id'   => $partnerCompanyId,
            'agreement_id'         => $this->activeAgreementIdFor($partnerCompanyId),
            'guest_application_id' => (int) $guest->id,
            'student_id'           => trim((string) ($guest->converted_student_id ?? '')) ?: null,
            'subject_name'         => $this->subjectName($guest),
            'fee_eur'              => (float) $data['fee_eur'],
            'status'               => PartnerStudentAgreement::STATUS_PROPOSED,
            'proposed_at'          => now(),
            'proposed_by'          => (string) ($request->user()?->email ?? ''),
            'note'                 => $data['note'] ?? null,
        ];

        $existing ? $existing->update($attributes) : PartnerStudentAgreement::query()->create($attributes);

        return back()->with('status', sprintf(
            '%s EUR teklif edildi. Partnerin kabulü bekleniyor.',
            number_format((float) $data['fee_eur'], 2, ',', '.')
        ));
    }

    /** Partner teklifi kabul eder. */
    public function accept(Request $request, PartnerStudentAgreement $studentAgreement): RedirectResponse
    {
        $partnerCompanyId = $this->companyId();

        abort_unless(
            (int) $studentAgreement->partner_company_id === $partnerCompanyId,
            403,
            'Bu anlaşma sizin firmanıza ait değil.'
        );

        abort_unless(
            (string) $studentAgreement->status === PartnerStudentAgreement::STATUS_PROPOSED,
            422,
            'Bu anlaşma kabule açık değil.'
        );

        $studentAgreement->update([
            'status'      => PartnerStudentAgreement::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'accepted_by' => (string) ($request->user()?->email ?? ''),
        ]);

        $this->logSettled($studentAgreement, $request);

        return back()->with('status', 'Anlaşma kabul edildi. Adayı öğrenciye çevirebilirsiniz.');
    }

    /** Partner teklifi reddeder — operasyon yeni tutar teklif edebilir. */
    public function reject(Request $request, PartnerStudentAgreement $studentAgreement): RedirectResponse
    {
        abort_unless(
            (int) $studentAgreement->partner_company_id === $this->companyId(),
            403,
            'Bu anlaşma sizin firmanıza ait değil.'
        );

        abort_unless(
            (string) $studentAgreement->status === PartnerStudentAgreement::STATUS_PROPOSED,
            422,
            'Bu anlaşma reddedilebilir durumda değil.'
        );

        $studentAgreement->update(['status' => PartnerStudentAgreement::STATUS_REJECTED]);

        return back()->with('status', 'Teklif reddedildi. Operasyon yeni tutar teklif edebilir.');
    }

    // ── Yardımcılar ─────────────────────────────────────────────────────────

    private function createAccepted(
        Request $request,
        GuestApplication $guest,
        PartnerAgreement $agreement,
        float $fee
    ): void {
        $studentAgreement = PartnerStudentAgreement::query()->create([
            'company_id'           => (int) $agreement->company_id,
            'partner_company_id'   => (int) $agreement->partner_company_id,
            'agreement_id'         => (int) $agreement->id,
            'guest_application_id' => (int) $guest->id,
            'student_id'           => trim((string) ($guest->converted_student_id ?? '')) ?: null,
            'subject_name'         => $this->subjectName($guest),
            'fee_eur'              => $fee,
            'status'               => PartnerStudentAgreement::STATUS_ACCEPTED,
            'proposed_at'          => now(),
            'proposed_by'          => 'cerceve_anlasma',
            'accepted_at'          => now(),
            'accepted_by'          => (string) ($request->user()?->email ?? ''),
            'note'                 => 'Çerçeve anlaşmadaki standart bedel.',
        ]);

        $this->logSettled($studentAgreement, $request);
    }

    private function logSettled(PartnerStudentAgreement $studentAgreement, Request $request): void
    {
        // Olay adayın (yani partnerin) kutusuna yazılır — gelişmeyi o izliyor.
        $this->eventLog->log(
            'partner_student_agreement.settled',
            'guest_application',
            (string) $studentAgreement->guest_application_id,
            sprintf(
                'Öğrenci bazlı anlaşma kapandı — %s EUR.',
                number_format((float) $studentAgreement->fee_eur, 2, ',', '.')
            ),
            ['agreement_id' => $studentAgreement->agreement_id],
            (string) ($request->user()?->email ?? '')
        );
    }

    private function activeAgreementIdFor(int $partnerCompanyId): ?int
    {
        $id = PartnerAgreement::query()
            ->active()
            ->forPartner($partnerCompanyId)
            ->latest('signed_at')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function subjectName(GuestApplication $guest): ?string
    {
        return trim((string) (($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''))) ?: null;
    }

    /**
     * Aday gerçekten bu partnere mi ait?
     *
     * ⚠ Modelde global kapsam YOK (iki sahipli kayıt). Bu kontrol
     * kaldırılırsa partner başka firmanın adayı için anlaşma açabilir.
     */
    private function assertPartnerOwnsGuest(GuestApplication $guest, int $partnerCompanyId): void
    {
        abort_unless(
            $partnerCompanyId > 0 && (int) ($guest->company_id ?? 0) === $partnerCompanyId,
            403,
            'Bu aday sizin firmanıza ait değil.'
        );
    }
}
