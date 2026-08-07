<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyServiceExtra;
use App\Models\CompanyServicePackage;
use App\Models\GuestApplication;
use App\Support\ServiceCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Firmanın kendi hizmetlerini tanımlaması ve fiyatlaması.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Paketler ve fiyatlar koda gömülüydü; her firma aynı listeyi aynı fiyatla
 * satmak zorundaydı. Partner firmalar kendi hizmet paketlerini kendileri
 * kurabilsin diye açıldı — hem FİYAT hem İÇERİK.
 *
 * ── MİRAS ───────────────────────────────────────────────────────────────
 * Firma kendi kataloğunu tanımlamadıysa üst firmanınkini görür. "Kendi
 * kataloğumu oluştur" dediği anda gördüğü liste olduğu gibi kendisine
 * kopyalanır — boş bir ekranla baş başa kalmasın, düzenleyerek başlasın.
 *
 * Kısmi miras YOK (bkz. ServiceCatalog): kendi kataloğu varsa listenin
 * tamamı onundur.
 *
 * ── SİLME NEDEN KISITLI ─────────────────────────────────────────────────
 * Bir paketi seçmiş adaylar varken o paket satırı silinirse, o adayların
 * tutarı sessizce SIFIRA düşer: kayıtta yalnızca paket KODU duruyor, fiyat
 * katalogdan çözülüyor. Bu yüzden kullanımdaki paket silinemiyor, yalnızca
 * satıştan kaldırılıyor (pasif) — pasif paket listelerde görünmez ama
 * geçmiş kayıtlar çözülmeye devam eder.
 */
class ServiceCatalogController extends Controller
{
    private function companyId(): int
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        abort_if($cid <= 0, 404);

        return $cid;
    }

    public function index(): View
    {
        $companyId = $this->companyId();
        $hasOwn    = ServiceCatalog::hasOwnCatalog($companyId);

        return view('manager.services.index', [
            'company'       => Company::query()->withoutGlobalScope('company')->find($companyId),
            'hasOwn'        => $hasOwn,
            'inheritedFrom' => ServiceCatalog::inheritedFrom($companyId),
            // Miras hâlindeyken de gösteriyoruz: firma neyi düzenleyeceğini görsün.
            'packages'      => $hasOwn ? $this->ownPackages($companyId) : collect(),
            'extras'        => $hasOwn ? $this->ownExtras($companyId) : collect(),
            'preview'       => $hasOwn ? collect() : ServiceCatalog::packages($companyId),
            'previewExtras' => $hasOwn ? collect() : ServiceCatalog::extras($companyId),
            'categories'    => ServiceCatalog::categories(),
            'usedCodes'     => $this->codesInUse($companyId),
        ]);
    }

    /**
     * Miras alınan kataloğu kendine kopyala — düzenlemeye buradan başlanır.
     *
     * Pasif kayıtlar da kopyalanıyor: üst firmada satıştan kaldırılmış bir
     * paketi seçmiş eski adaylar bu firmada da olabilir; kopyalanmazsa
     * onların tutarı çözülemez hâle gelirdi.
     */
    public function fork(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();

        if (ServiceCatalog::hasOwnCatalog($companyId)) {
            return back()->withErrors(['catalog' => 'Bu firmanın zaten kendi kataloğu var.']);
        }

        $email = (string) ($request->user()?->email ?? '');

        foreach (ServiceCatalog::allPackages($companyId) as $row) {
            CompanyServicePackage::create($this->packageAttributes($row, $companyId, $email));
        }

        foreach (ServiceCatalog::allExtras($companyId) as $row) {
            CompanyServiceExtra::create($this->extraAttributes($row, $companyId, $email));
        }

        return redirect()
            ->route('manager.services.index')
            ->with('status', 'Katalog kopyalandı. Artık fiyatları ve içerikleri kendiniz düzenleyebilirsiniz.');
    }

    /**
     * Mirasa dön — kendi kataloğunu tamamen sil.
     *
     * Kullanımdaki bir kod üst firmanın kataloğunda yoksa geçmiş kayıtların
     * tutarı çözülemez hâle gelir; o yüzden önce kontrol ediliyor.
     */
    public function reset(): RedirectResponse
    {
        $companyId = $this->companyId();

        if (! ServiceCatalog::hasOwnCatalog($companyId)) {
            return back()->withErrors(['catalog' => 'Bu firma zaten üst firmanın kataloğunu kullanıyor.']);
        }

        $used = $this->codesInUse($companyId);

        // Kendi satırlarını yok sayıp mirasın ne olacağına bak.
        $inherited = ServiceCatalog::inheritedPackageCodes($companyId);
        $orphans   = array_values(array_diff($used, $inherited));

        if ($orphans !== []) {
            return back()->withErrors(['catalog' => sprintf(
                'Mirasa dönülemez: %s kodlu paket(ler)i seçmiş adaylarınız var ve bu kodlar üst firmanın kataloğunda yok. '
                . 'Dönülürse o kayıtların tutarı çözülemez.',
                implode(', ', $orphans)
            )]);
        }

        CompanyServicePackage::withoutGlobalScope('company')->where('company_id', $companyId)->delete();
        CompanyServiceExtra::withoutGlobalScope('company')->where('company_id', $companyId)->delete();

        return redirect()
            ->route('manager.services.index')
            ->with('status', 'Kendi kataloğunuz silindi. Artık üst firmanın paket ve fiyatları geçerli.');
    }

    // ── Paketler ────────────────────────────────────────────────────────────

    public function storePackage(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $this->requireOwnCatalog($companyId);

        $data = $this->validatePackage($request, $companyId, null);

        CompanyServicePackage::create(array_merge($data, [
            'company_id' => $companyId,
            'updated_by' => (string) ($request->user()?->email ?? ''),
        ]));

        return back()->with('status', 'Paket eklendi.');
    }

    public function updatePackage(Request $request, int $id): RedirectResponse
    {
        $companyId = $this->companyId();
        $package   = $this->ownPackage($companyId, $id);

        $data = $this->validatePackage($request, $companyId, $package->id);

        $package->fill(array_merge($data, [
            'updated_by' => (string) ($request->user()?->email ?? ''),
        ]))->save();

        return back()->with('status', $package->title . ' güncellendi.');
    }

    public function destroyPackage(int $id): RedirectResponse
    {
        $companyId = $this->companyId();
        $package   = $this->ownPackage($companyId, $id);

        if (in_array($package->code, $this->codesInUse($companyId), true)) {
            $package->forceFill(['is_active' => false])->save();

            return back()->with('status',
                $package->title . ' satıştan kaldırıldı. Bu paketi seçmiş adaylarınız olduğu için '
                . 'kayıt silinmedi — silinseydi onların tutarı sıfırlanırdı.'
            );
        }

        $package->delete();

        return back()->with('status', 'Paket silindi.');
    }

    // ── Ek hizmetler ────────────────────────────────────────────────────────

    public function storeExtra(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $this->requireOwnCatalog($companyId);

        $data = $this->validateExtra($request, $companyId, null);

        CompanyServiceExtra::create(array_merge($data, [
            'company_id' => $companyId,
            'updated_by' => (string) ($request->user()?->email ?? ''),
        ]));

        return back()->with('status', 'Ek hizmet eklendi.');
    }

    public function updateExtra(Request $request, int $id): RedirectResponse
    {
        $companyId = $this->companyId();
        $extra     = $this->ownExtra($companyId, $id);

        $data = $this->validateExtra($request, $companyId, $extra->id);

        $extra->fill(array_merge($data, [
            'updated_by' => (string) ($request->user()?->email ?? ''),
        ]))->save();

        return back()->with('status', $extra->title . ' güncellendi.');
    }

    public function destroyExtra(int $id): RedirectResponse
    {
        $companyId = $this->companyId();
        $extra     = $this->ownExtra($companyId, $id);

        if (in_array($extra->code, $this->extraCodesInUse($companyId), true)) {
            $extra->forceFill(['is_active' => false])->save();

            return back()->with('status',
                $extra->title . ' satıştan kaldırıldı. Bu hizmeti seçmiş adaylarınız olduğu için kayıt silinmedi.'
            );
        }

        $extra->delete();

        return back()->with('status', 'Ek hizmet silindi.');
    }

    // ── İç işleyiş ──────────────────────────────────────────────────────────

    private function requireOwnCatalog(int $companyId): void
    {
        abort_unless(
            ServiceCatalog::hasOwnCatalog($companyId),
            403,
            'Önce "Kendi kataloğumu oluştur" adımını tamamlayın.'
        );
    }

    private function ownPackage(int $companyId, int $id): CompanyServicePackage
    {
        return CompanyServicePackage::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function ownExtra(int $companyId, int $id): CompanyServiceExtra
    {
        return CompanyServiceExtra::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function ownPackages(int $companyId)
    {
        return CompanyServicePackage::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    private function ownExtras(int $companyId)
    {
        return CompanyServiceExtra::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->orderBy('category')->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /** Bu firmanın adaylarının seçtiği paket kodları. @return list<string> */
    private function codesInUse(int $companyId): array
    {
        return GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('selected_package_code')
            ->where('selected_package_code', '!=', '')
            ->distinct()
            ->pluck('selected_package_code')
            ->map(fn ($c): string => (string) $c)
            ->all();
    }

    /** @return list<string> */
    private function extraCodesInUse(int $companyId): array
    {
        return GuestApplication::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('selected_extra_services')
            ->pluck('selected_extra_services')
            ->flatMap(fn ($v): array => is_array($v) ? $v : [])
            ->map(fn ($x): string => (string) (is_array($x) ? ($x['code'] ?? '') : $x))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<string,mixed> */
    private function validatePackage(Request $request, int $companyId, ?int $ignoreId): array
    {
        $data = $request->validate([
            'code'                => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'title'               => ['required', 'string', 'max:160'],
            'price_amount'        => ['required', 'numeric', 'min:0', 'max:999999'],
            'currency'            => ['nullable', 'string', 'max:8'],
            'includes'            => ['nullable', 'string', 'max:500'],
            'features'            => ['nullable', 'string', 'max:4000'],
            'included_categories' => ['nullable', 'array'],
            'included_categories.*' => ['string', 'max:64'],
            'included_extras'     => ['nullable', 'array'],
            'included_extras.*'   => ['string', 'max:64'],
            'max_universities'    => ['nullable', 'integer', 'min:0', 'max:999'],
            'support_level'       => ['nullable', 'string', 'max:64'],
            'validity_months'     => ['nullable', 'integer', 'min:0', 'max:120'],
            'sort_order'          => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'code.regex' => 'Kod yalnızca küçük harf, rakam ve alt çizgi içerebilir (örn. pkg_start).',
        ]);

        $this->assertCodeIsFree(CompanyServicePackage::class, $companyId, $data['code'], $ignoreId);

        return [
            'code'                => $data['code'],
            'title'               => $data['title'],
            'price_amount'        => (float) $data['price_amount'],
            'currency'            => $data['currency'] ?: 'EUR',
            // Gösterim fiyatı her kayıtta sayıdan üretiliyor: iki alanın
            // birbirinden kopması ("2.000 EUR" yazarken 3000 satması) en
            // kolay yapılan hata.
            'price'               => number_format((float) $data['price_amount'], 0, ',', '.') . ' ' . ($data['currency'] ?: 'EUR'),
            'includes'            => $data['includes'] ?? null,
            'features'            => $this->linesToArray($data['features'] ?? null),
            'included_categories' => array_values($data['included_categories'] ?? []),
            'included_extras'     => array_values($data['included_extras'] ?? []),
            'max_universities'    => $data['max_universities'] ?? null,
            'includes_visa'       => $request->boolean('includes_visa'),
            'includes_housing'    => $request->boolean('includes_housing'),
            'support_level'       => $data['support_level'] ?? null,
            'validity_months'     => $data['validity_months'] ?? null,
            'is_active'           => $request->boolean('is_active'),
            'sort_order'          => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /** @return array<string,mixed> */
    private function validateExtra(Request $request, int $companyId, ?int $ignoreId): array
    {
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'category'     => ['nullable', 'string', 'max:64'],
            'title'        => ['required', 'string', 'max:160'],
            'price_amount' => ['required', 'numeric', 'min:0', 'max:999999'],
            'currency'     => ['nullable', 'string', 'max:8'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'code.regex' => 'Kod yalnızca küçük harf, rakam ve alt çizgi içerebilir (örn. ext_vize).',
        ]);

        $this->assertCodeIsFree(CompanyServiceExtra::class, $companyId, $data['code'], $ignoreId);

        return [
            'code'         => $data['code'],
            'category'     => $data['category'] ?? null,
            'title'        => $data['title'],
            'price_amount' => (float) $data['price_amount'],
            'currency'     => $data['currency'] ?: 'EUR',
            'price'        => number_format((float) $data['price_amount'], 0, ',', '.') . ' ' . ($data['currency'] ?: 'EUR'),
            'description'  => $data['description'] ?? null,
            'is_active'    => $request->boolean('is_active'),
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /**
     * Kod firma içinde tekil olmalı — aynı kod iki satırda olsaydı hangisinin
     * fiyatının geçerli olduğu belirsizleşirdi.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    private function assertCodeIsFree(string $model, int $companyId, string $code, ?int $ignoreId): void
    {
        $exists = $model::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($b) => $b->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            abort(422, 'Bu kod zaten kullanılıyor: ' . $code);
        }
    }

    /** Satır satır girilen özellikleri diziye çevir. @return list<string> */
    private function linesToArray(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn ($line): string => trim(Str::of($line)->trim()->toString()))
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string,mixed> */
    private function packageAttributes(array $row, int $companyId, string $email): array
    {
        return [
            'company_id'          => $companyId,
            'code'                => (string) ($row['code'] ?? ''),
            'title'               => (string) ($row['title'] ?? ''),
            'price'               => (string) ($row['price'] ?? ''),
            'price_amount'        => (float) ($row['price_amount'] ?? 0),
            'currency'            => (string) ($row['currency'] ?? 'EUR'),
            'includes'            => $row['includes'] ?? null,
            'features'            => (array) ($row['features'] ?? []),
            'included_categories' => (array) ($row['included_categories'] ?? []),
            'included_extras'     => (array) ($row['included_extras'] ?? []),
            'max_universities'    => $row['max_universities'] ?? null,
            'includes_visa'       => (bool) ($row['includes_visa'] ?? false),
            'includes_housing'    => (bool) ($row['includes_housing'] ?? false),
            'support_level'       => $row['support_level'] ?? null,
            'validity_months'     => $row['validity_months'] ?? null,
            'is_active'           => (bool) ($row['is_active'] ?? true),
            'sort_order'          => (int) ($row['sort_order'] ?? 0),
            'updated_by'          => $email,
        ];
    }

    /** @return array<string,mixed> */
    private function extraAttributes(array $row, int $companyId, string $email): array
    {
        return [
            'company_id'   => $companyId,
            'code'         => (string) ($row['code'] ?? ''),
            'category'     => $row['category'] ?? null,
            'title'        => (string) ($row['title'] ?? ''),
            'price'        => (string) ($row['price'] ?? ''),
            'price_amount' => (float) ($row['price_amount'] ?? 0),
            'currency'     => (string) ($row['currency'] ?? 'EUR'),
            'description'  => $row['description'] ?? null,
            'is_active'    => (bool) ($row['is_active'] ?? true),
            'sort_order'   => (int) ($row['sort_order'] ?? 0),
            'updated_by'   => $email,
        ];
    }
}
