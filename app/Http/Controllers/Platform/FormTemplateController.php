<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestRegistrationField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Form şablonu — tek merkezden yönetim ve sapma denetimi.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Form tanımı merkezde (company_id = 0) duruyor ve tüm firmalar onu
 * kullanıyor. Ama bir firma kendi satırlarını edinirse merkezden kopar:
 * merkezî değişiklik ona ULAŞMAZ ve bu hiçbir yerde görünmez.
 *
 * Sapma İKİ YÖNLÜ sorundur:
 *   • merkezde olup firmada olmayan alan → firma yeni alanı hiç sormaz
 *   • firmada olup merkezde olmayan alan → merkezde karşılığı olmayan veri
 *
 * Bu ekran ikisini de gösterir ve firmayı merkeze döndürmeyi sağlar.
 * Konsol erişimi olmadığı için (KAS'ta SSH yok) panelde duruyor.
 */
class FormTemplateController extends Controller
{
    public function index(): View
    {
        $central = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', 0)
            ->get(['field_key', 'label', 'section_key', 'is_active']);

        $centralKeys = $central->pluck('field_key')->map(fn ($k) => (string) $k);

        $companyIds = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', '>', 0)
            ->distinct()
            ->pluck('company_id');

        $diverged = $companyIds->map(function ($companyId) use ($centralKeys) {
            $own = GuestRegistrationField::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->get(['field_key', 'label']);

            $ownKeys = $own->pluck('field_key')->map(fn ($k) => (string) $k);

            return [
                'company'      => Company::query()->withoutGlobalScope('company')->find($companyId),
                'company_id'   => (int) $companyId,
                'total'        => $own->count(),
                // Merkezde var, firmada YOK → firma yeni alanı hiç sormuyor.
                'missing'      => $centralKeys->diff($ownKeys)->values(),
                // Firmada var, merkezde YOK → merkezde karşılığı olmayan veri.
                'extra'        => $ownKeys->diff($centralKeys)->values(),
            ];
        })->values();

        return view('platform.form-template.index', [
            'centralCount' => $central->count(),
            'diverged'     => $diverged,
        ]);
    }

    /**
     * Firmayı merkezî şablona döndür — kendi satırlarını siler.
     *
     * ⚠ GERİ ALINAMAZ. Firmanın özelleştirmesi varsa kaybolur. Bu yüzden
     * ekranda ne kaybedileceği (fazladan alanlar) önce gösteriliyor.
     */
    public function reset(int $company): RedirectResponse
    {
        $target = Company::query()->withoutGlobalScope('company')->findOrFail($company);

        $deleted = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $target->id)
            ->delete();

        \App\Models\PlatformAuditLog::record('platform.form_template.reset', [
            'target_type' => 'company',
            'target_id'   => $target->id,
            'company'     => $target->name,
            'deleted'     => $deleted,
        ]);

        return back()->with('status',
            $target->name . ' merkezî şablona döndürüldü — ' . $deleted . ' satır silindi.'
        );
    }
}
