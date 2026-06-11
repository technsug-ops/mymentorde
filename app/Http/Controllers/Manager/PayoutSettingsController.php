<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PayoutSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Marketplace Phase 6 — Şirket Ödeme Ayarları (1 row per company).
 */
class PayoutSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? app('current_company_id') ?? 0);

        $row = PayoutSetting::query()
            ->where('company_id', $companyId)
            ->first();

        return view('manager.payout-settings.index', [
            'settings'   => $row,
            'companyId'  => $companyId,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? app('current_company_id') ?? 0);

        $data = $request->validate([
            'payout_day'         => ['required', 'integer', 'min:1', 'max:28'],
            'payout_minimum_eur' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'allow_on_demand'    => ['nullable'],
            'currency'           => ['required', 'string', 'size:3'],
            'notification_email' => ['nullable', 'email', 'max:200'],
        ]);

        $data['allow_on_demand'] = $request->boolean('allow_on_demand');
        $data['currency']        = strtoupper($data['currency']);

        PayoutSetting::query()->updateOrCreate(
            ['company_id' => $companyId],
            $data
        );

        PayoutSetting::forgetCacheFor($companyId);

        return redirect()->route('manager.payout-settings.index')
            ->with('success', 'Ödeme ayarları güncellendi.');
    }
}
