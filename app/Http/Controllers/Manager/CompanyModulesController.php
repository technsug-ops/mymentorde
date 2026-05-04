<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\ModuleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manager — Company × Module toggle yonetimi.
 *
 * companies.enabled_modules JSON kolonunu yonetir; her bir SaaS musteri
 * (company) icin hangi modullerin acik oldugunu gruplandiriyor (core/gold/premium).
 */
class CompanyModulesController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active', 'enabled_modules']);

        // Her company icin enabled_modules array'ine ceviri (cast yoksa string olabilir)
        $companies = $companies->map(function (Company $c): Company {
            $raw = $c->enabled_modules;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $c->enabled_modules = is_array($decoded) ? $decoded : [];
            } elseif (! is_array($raw)) {
                $c->enabled_modules = [];
            }
            return $c;
        });

        return view('manager.companies.modules', [
            'companies'    => $companies,
            'moduleMeta'   => ModuleAccess::MODULE_META,
            'moduleGroups' => ModuleAccess::moduleGroups(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $allModules  = array_keys(ModuleAccess::MODULE_META);
        $submitted   = (array) $request->input('modules', []);
        // Whitelist: sadece bilinen modul kodlari kabul + 'core' her zaman dahil
        $clean = array_values(array_unique(array_filter(array_map(
            fn ($m) => strtolower(trim((string) $m)),
            $submitted
        ), fn ($m) => in_array($m, $allModules, true))));
        if (! in_array('core', $clean, true)) {
            $clean[] = 'core';
        }

        // D11: doc_request aylik quota — null/'' = sinirsiz
        $quotaInput = $request->input('doc_request_monthly_limit');
        $quotaValue = null;
        if ($quotaInput !== null && trim((string) $quotaInput) !== '') {
            $q = (int) $quotaInput;
            $quotaValue = ($q > 0) ? min($q, 10000) : null;
        }

        $company->forceFill([
            'enabled_modules'           => $clean,
            'doc_request_monthly_limit' => $quotaValue,
        ])->save();
        ModuleAccess::flushCache((int) $company->id);

        return back()->with('success', "✓ {$company->name} modülleri güncellendi (" . count($clean) . " aktif)");
    }
}
