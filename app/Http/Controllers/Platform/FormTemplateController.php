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
    public function index(\App\Services\GuestRegistrationFieldSchemaService $schema): View
    {
        // Merkez = operasyonu yürüten ANA FİRMA. `company_id = 0` yalnızca
        // fabrika şablonu; ana firma kendi tanımını yaptıysa merkez odur.
        $master = Company::query()
            ->withoutGlobalScope('company')
            ->get()
            ->first(fn (Company $c) => \App\Support\Brand::isPrimary($c));

        $masterCount = $master
            ? GuestRegistrationField::query()->withoutGlobalScope('company')
                ->where('company_id', $master->id)->where('is_active', true)->count()
            : 0;

        $factoryCount = GuestRegistrationField::query()->withoutGlobalScope('company')
            ->where('company_id', 0)->where('is_active', true)->count();

        $companyIds = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', '>', 0)
            ->when($master, fn ($q) => $q->where('company_id', '!=', $master->id))
            ->distinct()
            ->pluck('company_id');

        // ⚠ "Kendi satırların var" TEK BAŞINA sapma değil. Sapma, miras
        // alacağın tanımdan FARKLI olmak. Ana firma zaten merkez olduğu için
        // listeye hiç girmiyor.
        $diverged = $companyIds->map(function ($companyId) use ($schema) {
            $own = GuestRegistrationField::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->get(['field_key']);

            $ownKeys = $own->pluck('field_key')->map(fn ($k) => (string) $k);
            $inherited = $schema->inheritedRowsFor((int) $companyId)
                ->pluck('field_key')->map(fn ($k) => (string) $k);

            return [
                'company'    => Company::query()->withoutGlobalScope('company')->find($companyId),
                'company_id' => (int) $companyId,
                'total'      => $own->count(),
                // Merkezde var, firmada YOK → firma o alanı hiç sormuyor.
                'missing'    => $inherited->diff($ownKeys)->values(),
                // Firmada var, merkezde YOK → karşılığı olmayan veri.
                'extra'      => $ownKeys->diff($inherited)->values(),
            ];
        })
        // Farkı olmayanı gösterme: gürültü yapar, gerçek sapmayı gizler.
        ->filter(fn ($row) => $row['missing']->isNotEmpty() || $row['extra']->isNotEmpty())
        ->values();

        return view('platform.form-template.index', [
            'master'       => $master,
            'masterCount'  => $masterCount,
            'factoryCount' => $factoryCount,
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
