<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\VisaMissingFieldsMail;
use App\Models\GuestApplication;
use App\Services\EventLogService;
use App\Services\VisaFieldMapperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Manager — Almanya vize başvurusu (VIDEX) rehberi.
 * Aday öğrencinin verisini Auswärtiges Amt Auslandsportal VIDEX form'unun
 * 7 bölümlü yapısına eşler. Manager kopyala-yapıştır ile hatasız doldurur.
 *
 * Eksik alanlar için 2 seçenek:
 *  1) Mail gönder (VisaMissingFieldsMail) — basit
 *  2) Belge talep linki (mevcut doc_request modülü) — premium feature
 */
class VisaGuideController extends Controller
{
    public function __construct(
        private readonly VisaFieldMapperService $mapper,
        private readonly EventLogService $eventLogService,
    ) {}

    /**
     * Öğrenci üzerinden vize rehberi (öğrenciye dönüşmüş guest_application'ı çözer).
     * URL: /manager/students/{studentId}/vize-rehber
     */
    public function showForStudent(string $studentId): View
    {
        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->firstOrFail();

        return $this->renderShow($guest, $studentId);
    }

    /**
     * Legacy guest URL desteği — bookmark'lar kırılmasın.
     */
    public function show(GuestApplication $guest): View
    {
        return $this->renderShow($guest, $guest->converted_student_id);
    }

    private function renderShow(GuestApplication $guest, ?string $studentId): View
    {
        $tabs = $this->mapper->buildAllTabs($guest);
        $missing = $this->mapper->missingFields($guest);

        $canRequestDocs = (bool) optional(Auth::user())->can_request_documents;

        return view('manager.visa-guide.show', [
            'guest'          => $guest,
            'studentId'      => $studentId,
            'tabs'           => $tabs,
            'missing'        => $missing,
            'canRequestDocs' => $canRequestDocs,
        ]);
    }

    /**
     * Vize meta alanlarını kaydet (application_meta.visa.* nested namespace).
     */
    public function saveMeta(Request $request, GuestApplication $guest): RedirectResponse
    {
        $vertretungKeys = array_keys(VisaFieldMapperService::VERTRETUNG_OPTIONS);
        $geschlechtKeys = array_keys(VisaFieldMapperService::GESCHLECHT_OPTIONS);
        $familienstandKeys = array_keys(VisaFieldMapperService::FAMILIENSTAND_OPTIONS);
        $zweckKeys = array_keys(VisaFieldMapperService::ZWECK_OPTIONS);
        $luTypeKeys = array_keys(VisaFieldMapperService::LEBENSUNTERHALT_OPTIONS);
        $unterbringungKeys = array_keys(VisaFieldMapperService::UNTERBRINGUNG_OPTIONS);
        $refTypeKeys = array_keys(VisaFieldMapperService::REFERENCE_TYPE_OPTIONS);

        $data = $request->validate([
            'visa'                                => 'array',
            'visa.vertretung'                     => 'nullable|in:' . implode(',', $vertretungKeys),
            'visa.geschlecht'                     => 'nullable|in:' . implode(',', $geschlechtKeys),
            'visa.familienstand'                  => 'nullable|in:' . implode(',', $familienstandKeys),
            'visa.frueher_staatsang'              => 'nullable|string|max:120',
            'visa.has_children'                   => 'nullable|in:Ja,Nein',
            'visa.geburtsname'                    => 'nullable|string|max:120',
            'visa.beruf_erlernt'                  => 'nullable|string|max:200',
            'visa.beruf_aktuell'                  => 'nullable|string|max:200',

            // Eltern
            'visa.vater_nachname'                 => 'nullable|string|max:120',
            'visa.vater_vorname'                  => 'nullable|string|max:120',
            'visa.vater_staatsang'                => 'nullable|string|max:120',
            'visa.vater_geburtsdatum'             => 'nullable|string|max:32',
            'visa.vater_geburtsort'               => 'nullable|string|max:120',
            'visa.vater_wohnort'                  => 'nullable|string|max:200',
            'visa.mutter_nachname'                => 'nullable|string|max:120',
            'visa.mutter_vorname'                 => 'nullable|string|max:120',
            'visa.mutter_staatsang'               => 'nullable|string|max:120',
            'visa.mutter_geburtsdatum'            => 'nullable|string|max:32',
            'visa.mutter_geburtsort'              => 'nullable|string|max:120',
            'visa.mutter_wohnort'                 => 'nullable|string|max:200',

            // Kontakt extras
            'visa.hausnummer'                     => 'nullable|string|max:32',
            'visa.wohnsitz_anderes_land'          => 'nullable|in:Ja,Nein',

            // Pasaport ek
            'visa.reisedoc_ausgestellt_von'       => 'nullable|string|max:120',

            // Reise
            'visa.travel_zweck'                   => 'nullable|in:' . implode(',', $zweckKeys),
            'visa.travel_erwerb'                  => 'nullable|string|max:300',
            'visa.travel_von'                     => 'nullable|string|max:32',
            'visa.travel_bis'                     => 'nullable|string|max:32',
            'visa.travel_ueber12'                 => 'nullable|in:Ja,Nein',

            // Referenz
            'visa.reference_type'                 => 'nullable|in:' . implode(',', $refTypeKeys),
            'visa.reference_name'                 => 'nullable|string|max:200',
            'visa.reference_city'                 => 'nullable|string|max:120',
            'visa.reference_aufgabe'              => 'nullable|string|max:200',
            'visa.reference_register_name'        => 'nullable|string|max:120',
            'visa.reference_register_ort'         => 'nullable|string|max:120',
            'visa.reference_register_nr'          => 'nullable|string|max:64',
            'visa.reference_contact_lastname'     => 'nullable|string|max:120',
            'visa.reference_contact_firstname'    => 'nullable|string|max:120',
            'visa.reference_contact_address'      => 'nullable|string|max:300',
            'visa.reference_contact_phone'        => 'nullable|string|max:64',
            'visa.reference_contact_email'        => 'nullable|email|max:200',

            // Lebensunterhalt
            'visa.lebensunterhalt_type'           => 'nullable|in:' . implode(',', $luTypeKeys),
            'visa.lebensunterhalt_verpflicht'     => 'nullable|in:Ja,Nein',
            'visa.aufenthalt_strasse'             => 'nullable|string|max:200',
            'visa.aufenthalt_plz_ort'             => 'nullable|string|max:200',
            'visa.unterbringung'                  => 'nullable|in:' . implode(',', $unterbringungKeys),
            'visa.unterbringung_note'             => 'nullable|string|max:300',
            'visa.staendig_ausland'               => 'nullable|in:Ja,Nein',
            'visa.familie_einreise'               => 'nullable|in:Ja,Nein',
            'visa.krankenversicherung'            => 'nullable|in:Ja,Nein',
            'visa.fruehere_aufenthalte'           => 'nullable|in:Ja,Nein',

            // Erklärung
            'visa.erkl_vorbestraft'               => 'nullable|in:Ja,Nein',
            'visa.erkl_abgeschoben'               => 'nullable|in:Ja,Nein',
            'visa.erkl_krankheiten'               => 'nullable|in:Ja,Nein',
        ]);

        $current = is_array($guest->application_meta) ? $guest->application_meta : [];
        $existingVisa = is_array($current['visa'] ?? null) ? $current['visa'] : [];

        $incomingVisa = array_filter(
            (array) ($data['visa'] ?? []),
            fn ($v) => $v !== null && $v !== ''
        );

        $current['visa'] = array_merge($existingVisa, $incomingVisa);

        $guest->forceFill(['application_meta' => $current])->save();

        $this->eventLogService->log(
            eventType: 'visa_meta_saved',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' Vize meta verisini güncelledi.',
            meta: ['keys' => array_keys($incomingVisa)],
        );

        return redirect()->route('manager.visa-guide.show', $guest)->with('success', 'Vize bilgileri kaydedildi.');
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
            Mail::to($email)->queue(new VisaMissingFieldsMail(
                recipientName: $recipientName,
                missingLabels: array_values((array) $request->input('fields')),
                managerNote:   $request->input('note'),
                portalUrl:     url('/guest/registration'),
            ));

            $this->eventLogService->log(
                eventType: 'visa_missing_mail_sent',
                entityType: 'guest_application',
                entityId: (string) $guest->id,
                message: 'Manager #' . (Auth::id() ?? '?') . ' Vize eksik alan maili gönderdi.',
                meta: ['fields' => $request->input('fields'), 'note' => $request->input('note')],
            );

            return back()->with('success', 'Eksik alan maili öğrenciye gönderildi.');
        } catch (\Throwable $e) {
            Log::warning('visa.missing_mail.failed', ['guest_id' => $guest->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['mail' => 'Mail gönderilemedi: ' . $e->getMessage()]);
        }
    }
}
