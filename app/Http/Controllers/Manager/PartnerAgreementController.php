<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PartnerAgreement;
use App\Models\PartnerStudentAgreement;
use App\Services\EventLogService;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Portal ↔ partner ÇERÇEVE anlaşması — iki taraflı ekran.
 *
 * Aynı adres iki tarafa da açık ama gördükleri iş farklı:
 *   • Operasyon (portalı işleten firma) → anlaşma yazar, gönderir, fesheder
 *   • Partner                           → gelen anlaşmayı okur ve İMZALAR
 *
 * ⚠ Kayıt iki sahipli (`SharedBetweenTwoCompanies`): global kapsam yok,
 * sınır her sorguda elle kuruluyor. Buradaki tek kapı `visibleTo()` —
 * kaldırılırsa tüm firmaların anlaşması dökülür.
 */
class PartnerAgreementController extends Controller
{
    public function __construct(private readonly EventLogService $eventLog)
    {
    }

    private function companyId(): int
    {
        return (int) (TenantContext::writeId() ?? 0);
    }

    private function isPartnerSide(): bool
    {
        return Company::isPartnerPanel($this->companyId());
    }

    public function index(): View
    {
        $companyId = $this->companyId();

        $agreements = PartnerAgreement::query()
            ->visibleTo($companyId)
            ->with('partnerCompany:id,name')
            ->latest('id')
            ->get();

        // Operasyon tarafı yeni anlaşma açabilsin diye alt firma listesi.
        // Partner tarafına gerek yok — o yalnızca kendine geleni görür.
        $partnerOptions = $this->isPartnerSide()
            ? collect()
            : Company::query()
                ->withoutGlobalScope('company')
                ->whereIn('id', Company::descendantIds($companyId))
                ->orderBy('name')
                ->get(['id', 'name']);

        return view('manager.partner-agreements.index', [
            'agreements'     => $agreements,
            'partnerOptions' => $partnerOptions,
            'isPartnerSide'  => $this->isPartnerSide(),
            'companyId'      => $companyId,
        ]);
    }

    /** Operasyon yeni çerçeve anlaşma açar. */
    public function store(Request $request): RedirectResponse
    {
        abort_if($this->isPartnerSide(), 403, 'Çerçeve anlaşmayı operasyon açar.');

        $companyId = $this->companyId();

        $data = $request->validate([
            'partner_company_id'       => ['required', 'integer'],
            'title'                    => ['required', 'string', 'max:200'],
            'standard_student_fee_eur' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'valid_from'               => ['nullable', 'date'],
            'valid_until'              => ['nullable', 'date', 'after_or_equal:valid_from'],
            'body_text'                => ['nullable', 'string', 'max:50000'],
        ], [
            'partner_company_id.required' => 'Partner firma seçin.',
            'title.required'              => 'Anlaşma başlığı zorunlu.',
            'valid_until.after_or_equal'  => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ]);

        // ⚠ Yetki sınırı: yalnızca KENDİ alt firmalarınla anlaşma açabilirsin.
        // Doğrulanmasaydı istek gövdesine yazılan herhangi bir firma id'si
        // kabul edilirdi.
        abort_unless(
            in_array((int) $data['partner_company_id'], Company::descendantIds($companyId), true),
            403,
            'Bu firma sizin partneriniz değil.'
        );

        $agreement = PartnerAgreement::query()->create([
            'company_id'               => $companyId,
            'partner_company_id'       => (int) $data['partner_company_id'],
            'title'                    => $data['title'],
            'body_text'                => $data['body_text'] ?? null,
            'standard_student_fee_eur' => $data['standard_student_fee_eur'] ?? null,
            'valid_from'               => $data['valid_from'] ?? null,
            'valid_until'              => $data['valid_until'] ?? null,
            'status'                   => PartnerAgreement::STATUS_DRAFT,
            'created_by'               => (string) ($request->user()?->email ?? ''),
        ]);

        return back()->with('status', '"' . $agreement->title . '" taslak olarak oluşturuldu.');
    }

    /** Taslağı partnere gönder — imza bekleyen hâle geçer. */
    public function send(Request $request, PartnerAgreement $agreement): RedirectResponse
    {
        $this->assertOperationOwns($agreement);

        abort_unless(
            (string) $agreement->status === PartnerAgreement::STATUS_DRAFT,
            422,
            'Yalnızca taslak anlaşma gönderilebilir.'
        );

        $agreement->update([
            'status'  => PartnerAgreement::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return back()->with('status', 'Anlaşma partnere gönderildi, imzası bekleniyor.');
    }

    /**
     * Partner anlaşmayı imzalar.
     *
     * İmza PARTNERİN işi; operasyonun partner adına imzalaması, iki taraflı
     * olmasının anlamını ortadan kaldırırdı.
     */
    public function sign(Request $request, PartnerAgreement $agreement): RedirectResponse
    {
        abort_unless(
            (int) $agreement->partner_company_id === $this->companyId(),
            403,
            'Bu anlaşma sizin firmanıza ait değil.'
        );

        abort_unless(
            (string) $agreement->status === PartnerAgreement::STATUS_SENT,
            422,
            'Bu anlaşma imzaya açık değil.'
        );

        $data = $request->validate([
            'signed_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $attributes = [
            'status'          => PartnerAgreement::STATUS_SIGNED,
            'signed_at'       => now(),
            'signed_by_email' => (string) ($request->user()?->email ?? ''),
        ];

        if ($request->hasFile('signed_file')) {
            $attributes['signed_file_path'] = $request->file('signed_file')->store(
                sprintf('partner-agreements/%d', (int) $agreement->partner_company_id),
                'local'
            );
        }

        $agreement->update($attributes);

        // Olay partnerin kutusuna yazılır — kendi imzaladığı anlaşma.
        $this->eventLog->log(
            'partner_agreement.signed',
            null,
            null,
            'Çerçeve anlaşma imzalandı: ' . $agreement->title,
            ['agreement_id' => $agreement->id],
            (string) ($request->user()?->email ?? ''),
            (int) $agreement->partner_company_id
        );

        return back()->with('status', 'Anlaşma imzalandı. Öğrenci bazlı anlaşmalar artık açılabilir.');
    }

    /** Anlaşmayı feshet — yeni öğrenci anlaşması açılamaz hâle gelir. */
    public function terminate(Request $request, PartnerAgreement $agreement): RedirectResponse
    {
        $this->assertOperationOwns($agreement);

        $data = $request->validate([
            'termination_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $agreement->update([
            'status'             => PartnerAgreement::STATUS_TERMINATED,
            'terminated_at'      => now(),
            'termination_reason' => $data['termination_reason'] ?? null,
        ]);

        // ⚠ Mevcut öğrenci anlaşmalarına DOKUNULMUYOR: kabul edilmiş bir
        // bedel, çerçeve bitti diye geçersiz olmaz. Fesih yalnızca YENİ
        // anlaşma açılmasını durdurur.
        $openCount = PartnerStudentAgreement::query()
            ->where('agreement_id', $agreement->id)
            ->where('status', PartnerStudentAgreement::STATUS_ACCEPTED)
            ->count();

        return back()->with('status', sprintf(
            'Anlaşma feshedildi. Yürürlükteki %d öğrenci anlaşması etkilenmedi.',
            $openCount
        ));
    }

    /** Operasyon tarafı bu anlaşmanın sahibi mi? */
    private function assertOperationOwns(PartnerAgreement $agreement): void
    {
        abort_if($this->isPartnerSide(), 403, 'Bu işlem operasyon firmasınındır.');

        abort_unless(
            (int) $agreement->company_id === $this->companyId(),
            403,
            'Bu anlaşma sizin firmanıza ait değil.'
        );
    }
}
