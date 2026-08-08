<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Api\GuestApplicationAdminController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Services\EventLogService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Partner firmanın sözleşme kapatması ve öğrenciye dönüşümü.
 *
 * ── BU KAYIT İSTEĞE BAĞLI ───────────────────────────────────────────────
 * Partnerin öğrencisiyle yaptığı sözleşme bu sistemin konusu DEĞİL.
 * Öğrenciyi portal (YourGermanUni) white-label takip ediyor, operasyonu
 * MentorDE yürütüyor; partnerin öğrenciden ne aldığı ve sözleşme metni
 * onun kendi işi. İsterse kaydeder — kanıt sistemde dursun diye.
 *
 * ⚠ Bu kayıt DÖNÜŞÜMÜ KİLİTLEMEZ. Dönüşümün kapısı portal ile partner
 * arasındaki öğrenci bazlı anlaşmadır (bkz. PartnerStudentAgreement);
 * partnerin kendi müşteri sözleşmesi değil. İkisini karıştırmak, partneri
 * bizim işimiz olmayan bir veriyi girmeye zorlardı.
 *
 * MentorDE'nin kendi sözleşme süreci sistemin içinde yürüyor (talep →
 * gönderim → imza → onay, şablon, dijital imza). Partnerde o adımların
 * karşılığı yok; burada tek adım var: "imzalandı, kaydı şu".
 *
 * ── DÖNÜŞÜM YENİDEN YAZILMADI ───────────────────────────────────────────
 * Dönüşüm 150 satırlık bir iş: öğrenci kimliği üretimi, köprü kaydının
 * yeniden kullanımı, danışman ataması, bayi komisyonu, karşılama bildirimleri,
 * onboarding görevleri, portal kullanıcısının rolü. Kopyalanması iki ayrı
 * gerçek üretirdi. Bu yüzden mevcut uç nokta ÇAĞRILIYOR
 * (GuestApplicationAdminController::convert) — o uç zaten partner farkındalıklı:
 * dönüşüm partnerin, danışman seçimi operasyon şirketinin.
 */
class PartnerContractController extends Controller
{
    public function __construct(private readonly EventLogService $eventLog)
    {
    }

    /**
     * "Öğrenciyle sözleşme imzalandı" — isteğe bağlı kayıt.
     *
     * Talep/gönderim/onay ara durumları atlanıyor: dışarıda imzalanmış bir
     * sözleşme için o adımların karşılığı yok. Tutar da ZORUNLU DEĞİL —
     * partnerin öğrenciden ne aldığı bizim işimiz değil; yazmak isterse yeri
     * olsun diye duruyor.
     */
    public function close(Request $request, GuestApplication $guest): RedirectResponse
    {
        $this->assertPartnerCompany();

        if ($guest->converted_to_student) {
            return back()->withErrors([
                'contract' => 'Bu aday zaten öğrenciye dönüştürülmüş.',
            ]);
        }

        $data = $request->validate([
            'contract_amount_eur'  => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'contract_signed_on'   => ['nullable', 'date', 'before_or_equal:today'],
            'contract_amount_note' => ['nullable', 'string', 'max:500'],
            'signed_file'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'contract_amount_eur.numeric'        => 'Tutar sayı olmalı (örn. 2000 veya 2000.50).',
            'contract_signed_on.before_or_equal' => 'İmza tarihi gelecekte olamaz.',
            'signed_file.mimes'                  => 'Belge PDF, JPG veya PNG olmalı.',
            'signed_file.max'                    => 'Belge en fazla 10 MB olabilir.',
        ]);

        $signedAt = ! empty($data['contract_signed_on'])
            ? \Illuminate\Support\Carbon::parse($data['contract_signed_on'])
            : now();

        $attributes = [
            'contract_status'      => 'approved',
            'contract_signed_at'   => $signedAt,
            'contract_approved_at' => now(),
            'contract_amount_note' => $data['contract_amount_note'] ?? null,
        ];

        // ⚠ Tutar yalnızca GİRİLDİYSE yazılıyor ve sabitleniyor. Boş bırakılan
        // alanı 0 olarak kaydetmek, "bu öğrenciden hiç para alınmadı" diye
        // okunacak bir rakamı finansa sokardı.
        if (isset($data['contract_amount_eur']) && $data['contract_amount_eur'] !== null) {
            $attributes['contract_amount_eur']       = (float) $data['contract_amount_eur'];
            $attributes['contract_amount_locked_at'] = now();
            $attributes['contract_amount_set_by']    = (string) ($request->user()?->email ?? '');
        }

        if ($request->hasFile('signed_file')) {
            $attributes['contract_signed_file_path'] = $this->storeSignedFile($request, $guest);
        }

        $guest->forceFill($attributes)->save();

        // Olay, işlemi yapanın değil KONUNUN şirketine yazılır — partner kendi
        // adayının gelişmesini görmeli (bkz. EventLogService).
        $this->eventLog->log(
            'contract.closed_externally',
            'guest_application',
            (string) $guest->id,
            'Öğrenci sözleşmesi dışarıda imzalandı olarak işaretlendi.',
            ['signed_at' => $signedAt->toDateString()],
            (string) ($request->user()?->email ?? '')
        );

        return back()->with('status', 'Öğrenci sözleşmesi kaydedildi.');
    }

    /** Adayı öğrenciye çevir — asıl işi mevcut dönüşüm ucu yapıyor. */
    public function convert(Request $request, GuestApplication $guest): RedirectResponse
    {
        $this->assertPartnerCompany();

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = app(GuestApplicationAdminController::class)->convert($guest, $request);

        $payload = (array) ($response->getData(true) ?? []);

        if ($response->getStatusCode() >= 400 || ($payload['success'] ?? true) === false) {
            return back()->withErrors([
                'convert' => $this->humanizeConversionError($payload),
            ]);
        }

        $studentId = (string) ($guest->fresh()?->converted_student_id ?? '');

        return back()->with('status', $studentId !== ''
            ? 'Öğrenci kaydı açıldı: ' . $studentId
            : 'Aday öğrenciye dönüştürüldü.');
    }

    /**
     * Bu ekran yalnızca partner modundaki firmalar için.
     *
     * MentorDE'nin kendi sözleşme akışı sistemin içinde yürüyor; oraya
     * "dışarıda imzalandı" kestirmesi koymak şablonu, eki ve onay adımını
     * atlamanın kapısını açardı.
     */
    private function assertPartnerCompany(): void
    {
        abort_unless(
            Company::isPartnerPanel((int) (TenantContext::writeId() ?? 0)),
            403,
            'Bu akış partner firmalar içindir.'
        );
    }

    /** İmzalı belgeyi firma bazında ayrılmış klasöre yaz. */
    private function storeSignedFile(Request $request, GuestApplication $guest): string
    {
        return $request->file('signed_file')->store(
            sprintf('contracts/%d', (int) ($guest->company_id ?: 0)),
            'local'
        );
    }

    /**
     * Dönüşüm ucu makine okunur "missing" listesi döndürüyor; ekranda
     * anlaşılır tek cümleye çeviriyoruz.
     *
     * @param array<string,mixed> $payload
     */
    private function humanizeConversionError(array $payload): string
    {
        $missing = (array) data_get($payload, 'error.details.missing', data_get($payload, 'details.missing', []));

        $labels = [
            'sozlesme_onayi'  => 'sözleşme kapatılmamış',
            'paket_secimi'    => 'paket seçilmemiş',
            'belgeler'        => 'belgeler hazır işaretlenmemiş',
            'on_kayit_formu'  => 'ön kayıt formu gönderilmemiş',
        ];

        if ($missing !== []) {
            return 'Dönüşüm için eksik: ' . implode(', ', array_map(
                static fn ($key): string => $labels[$key] ?? (string) $key,
                $missing
            )) . '.';
        }

        return (string) data_get($payload, 'error.message', 'Dönüşüm tamamlanamadı.');
    }
}
