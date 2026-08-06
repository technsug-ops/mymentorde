<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyMailSetting;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Firmanın kendi mail taşıyıcısı — platform konsolundan yönetilir.
 *
 * ⚠ TEST EDİLMEDEN AKTİFLEŞMEZ. Yanlış kimlik bilgisi o firmanın TÜM
 * mailini sessizce keser; hata ancak kullanıcı "mailim gelmedi" dediğinde
 * fark edilir. Bu yüzden aktifleştirme ayrı bir adım ve başarılı testi
 * şart koşuyor.
 *
 * ⚠ ŞİFRE BİR DAHA GÖSTERİLMEZ. Form boş gelirse mevcut değer korunur;
 * yalnızca yeni değer girilirse değişir.
 */
class CompanyMailSettingController extends Controller
{
    public function update(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->findOrFail($company);

        $data = $request->validate([
            'driver'       => ['required', Rule::in(array_keys(CompanyMailSetting::DRIVERS))],
            'host'         => ['nullable', 'string', 'max:190'],
            'port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username'     => ['nullable', 'string', 'max:190'],
            'encryption'   => ['nullable', Rule::in(['tls', 'ssl'])],
            'password'     => ['nullable', 'string', 'max:500'],
            'api_key'      => ['nullable', 'string', 'max:500'],
            'from_address' => ['nullable', 'email', 'max:190'],
        ]);

        $setting = CompanyMailSetting::firstOrNew(['company_id' => $companyModel->id]);

        $setting->fill([
            'company_id'   => $companyModel->id,
            'driver'       => $data['driver'],
            'host'         => $data['host'] ?? null,
            'port'         => $data['port'] ?? null,
            'username'     => $data['username'] ?? null,
            'encryption'   => $data['encryption'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'updated_by'   => (string) ($request->user()?->email ?? ''),
        ]);

        // Boş bırakılan sır alanları MEVCUT değeri korur — panelde bir daha
        // gösterilmediği için kullanıcı her kaydedişte yeniden yazamaz.
        if (trim((string) ($data['password'] ?? '')) !== '') {
            $setting->password = $data['password'];
        }

        if (trim((string) ($data['api_key'] ?? '')) !== '') {
            $setting->api_key = $data['api_key'];
        }

        // Kimlik bilgisi değişti → önceki test sonucu artık geçersiz.
        $setting->is_active       = false;
        $setting->last_test_error = null;

        $setting->save();

        return back()->with('status',
            'Mail taşıyıcısı kaydedildi. Devreye girmesi için önce TEST edin — '
            . 'test başarılı olmadan kullanılmaz.'
        );
    }

    /**
     * Kimlik bilgilerini gerçek bir gönderimle dener.
     *
     * Başarılıysa taşıyıcı aktifleşir. Başarısızsa hata kayda geçer ve
     * taşıyıcı kapalı kalır — firma mailsiz kalmaz, platformunkine düşer.
     */
    public function test(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->findOrFail($company);

        $data = $request->validate([
            'to' => ['required', 'email', 'max:190'],
        ]);

        $setting = CompanyMailSetting::query()->where('company_id', $companyModel->id)->first();

        if (!$setting) {
            return back()->withErrors(['mail_setting' => 'Önce taşıyıcı bilgilerini kaydedin.']);
        }

        if (!$setting->isComplete()) {
            return back()->withErrors([
                'mail_setting' => $setting->driver === CompanyMailSetting::DRIVER_RESEND
                    ? 'API anahtarı eksik.'
                    : 'Sunucu adresi ve port zorunlu.',
            ]);
        }

        $snapshot = Brand::snapshot();

        try {
            // Testi AKTİFMİŞ GİBİ yapmalıyız: aktif olmayan kayıt marka
            // katmanınca uygulanmaz, o yüzden burada elle devreye alınıyor.
            $wasActive = $setting->is_active;
            $setting->forceFill(['is_active' => true])->saveQuietly();
            CompanyMailSetting::flushActiveIds();

            Brand::apply($companyModel);

            $fromName    = (string) config('mail.from.name');
            $fromAddress = (string) config('mail.from.address');

            Mail::raw(
                "Mail taşıyıcısı testi.\n\n"
                . "Şirket: {$companyModel->name}\n"
                . "Sürücü: {$setting->driver}\n"
                . "Gönderen: {$fromName} <{$fromAddress}>\n\n"
                . 'Bu mail ulaştıysa firmanın kendi taşıyıcısı çalışıyor.',
                function ($msg) use ($data, $companyModel) {
                    $msg->to($data['to'])->subject("[Test] Mail taşıyıcısı — {$companyModel->name}");
                }
            );

            $setting->forceFill([
                'is_active'       => true,
                'last_tested_at'  => now(),
                'last_test_error' => null,
            ])->save();

            CompanyMailSetting::flushActiveIds();

            return back()->with('status',
                "Test başarılı — taşıyıcı DEVREDE. Gönderen: {$fromName} <{$fromAddress}>"
            );
        } catch (\Throwable $e) {
            // Başarısız test taşıyıcıyı kapalı bırakır: yarım yapılandırma
            // ile firmayı mailsiz bırakmaktansa platformunkine düşmek iyi.
            $setting->forceFill([
                'is_active'       => false,
                'last_tested_at'  => now(),
                'last_test_error' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            CompanyMailSetting::flushActiveIds();

            return back()->withErrors([
                'mail_setting' => 'Test başarısız — taşıyıcı kapalı kaldı. ' . $e->getMessage(),
            ]);
        } finally {
            Brand::restore($snapshot);
        }
    }

    /** Taşıyıcıyı tamamen kaldır — firma platformun taşıyıcısına döner. */
    public function destroy(int $company): RedirectResponse
    {
        $companyModel = Company::query()->findOrFail($company);

        CompanyMailSetting::query()->where('company_id', $companyModel->id)->delete();
        CompanyMailSetting::flushActiveIds();

        return back()->with('status', 'Mail taşıyıcısı kaldırıldı — platformun taşıyıcısı kullanılacak.');
    }
}
