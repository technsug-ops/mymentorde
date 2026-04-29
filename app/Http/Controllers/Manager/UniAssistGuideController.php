<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\UniAssistMissingFieldsMail;
use App\Models\GuestApplication;
use App\Services\EventLogService;
use App\Services\UniAssistFieldMapperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Manager — Uni-Assist başvuru rehberi.
 * Aday öğrencinin verisini Uni-Assist'in 4 sekmeli "Mein Konto" form
 * yapısına eşler. Manager kopyala-yapıştır ile hatasız Uni-Assist
 * portal'ına kaydeder.
 *
 * Eksik alanlar için 2 seçenek:
 *  1) Mail gönder (UniAssistMissingFieldsMail) — basit
 *  2) Belge talep linki (mevcut doc_request modülü) — premium feature
 */
class UniAssistGuideController extends Controller
{
    public function __construct(
        private readonly UniAssistFieldMapperService $mapper,
        private readonly EventLogService $eventLogService,
    ) {}

    /**
     * Öğrenci üzerinden Uni-Assist rehberi (öğrenciye dönüşmüş guest_application'ı çözer).
     * URL: /manager/students/{studentId}/uni-assist-rehber
     */
    public function showForStudent(string $studentId): View
    {
        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->firstOrFail();

        return $this->renderShow($guest, $studentId);
    }

    /**
     * Legacy guest URL desteği — mevcut bookmark'lar kırılmasın.
     */
    public function show(GuestApplication $guest): View
    {
        return $this->renderShow($guest, $guest->converted_student_id);
    }

    private function renderShow(GuestApplication $guest, ?string $studentId): View
    {
        $tabs = $this->mapper->buildAllTabs($guest);
        $missing = $this->mapper->missingFields($guest);

        // Doküman talep linki yetkisi (premium gate)
        $canRequestDocs = (bool) optional(Auth::user())->can_request_documents;

        return view('manager.uni-assist-guide.show', [
            'guest'          => $guest,
            'studentId'      => $studentId,
            'tabs'           => $tabs,
            'missing'        => $missing,
            'canRequestDocs' => $canRequestDocs,
        ]);
    }

    /**
     * BID/BAN ve diğer manuel meta alanları kaydet.
     */
    public function saveMeta(Request $request, GuestApplication $guest): RedirectResponse
    {
        $schulabschlussKeys = array_keys(\App\Services\UniAssistFieldMapperService::SCHULABSCHLUSS_OPTIONS);
        $geschlechtKeys = array_keys(\App\Services\UniAssistFieldMapperService::GESCHLECHT_OPTIONS);

        $data = $request->validate([
            'meta'             => 'array',
            'meta.hochschulstart_bid'      => 'nullable|string|regex:/^B?\d{12}$/i',
            'meta.hochschulstart_ban'      => 'nullable|string|regex:/^\d{6}$/',
            'meta.co_address'              => 'nullable|string|max:255',
            'meta.address_extra'           => 'nullable|string|max:255',
            'meta.home_city'               => 'nullable|string|max:120',
            'meta.eu_marriage'             => 'nullable|in:Ja,Nein',
            'meta.is_refugee'              => 'sometimes|boolean',
            'meta.feststellungspruefung_done' => 'nullable|in:Ja,Nein',
            'meta.schulabschluss_type'     => 'nullable|in:' . implode(',', $schulabschlussKeys),
            'meta.geschlecht_de'           => 'nullable|in:' . implode(',', $geschlechtKeys),
        ]);

        $current = is_array($guest->application_meta) ? $guest->application_meta : [];
        $merged  = array_merge($current, array_filter(
            (array) ($data['meta'] ?? []),
            fn ($v) => $v !== null && $v !== ''
        ));

        $guest->forceFill(['application_meta' => $merged])->save();

        $this->eventLogService->log(
            eventType: 'uni_assist_meta_saved',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' Uni-Assist meta verisini güncelledi.',
            meta: ['keys' => array_keys($data['meta'] ?? [])],
        );

        return redirect()->route('manager.uni-assist-guide.show', $guest)->with('success', 'Bilgiler kaydedildi.');
    }

    /**
     * Eksik alanlar listesini öğrenciye mail ile gönder.
     */
    public function requestMissingFields(Request $request, GuestApplication $guest): RedirectResponse
    {
        $request->validate([
            'note'   => 'nullable|string|max:1000',
            'fields' => 'required|array|min:1',
            'fields.*' => 'string|max:200',
        ]);

        $email = trim((string) ($guest->email ?? ''));
        if ($email === '') {
            return back()->withErrors(['mail' => 'Bu adayın email adresi yok.']);
        }

        $recipientName = trim(((string) ($guest->first_name ?? '')) . ' ' . ((string) ($guest->last_name ?? '')));
        if ($recipientName === '') $recipientName = 'Sayın Öğrencimiz';

        try {
            Mail::to($email)->queue(new UniAssistMissingFieldsMail(
                recipientName: $recipientName,
                missingLabels: array_values((array) $request->input('fields')),
                managerNote:   $request->input('note'),
                portalUrl:     url('/guest/registration'),
            ));

            $this->eventLogService->log(
                eventType: 'uni_assist_missing_mail_sent',
                entityType: 'guest_application',
                entityId: (string) $guest->id,
                message: 'Manager #' . (Auth::id() ?? '?') . ' Uni-Assist eksik alan maili gönderdi.',
                meta: ['fields' => $request->input('fields'), 'note' => $request->input('note')],
            );

            return back()->with('success', 'Eksik alan maili öğrenciye gönderildi.');
        } catch (\Throwable $e) {
            Log::warning('uni_assist.missing_mail.failed', ['guest_id' => $guest->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['mail' => 'Mail gönderilemedi: ' . $e->getMessage()]);
        }
    }
}
