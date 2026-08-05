<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Şirket bağlamı değiştirici — personelin "hangi firma adına çalışıyorum" seçimi.
 *
 * NEDEN GEREKLİ: MentorDE partner firmaların süreçlerini yürütüyor. Personel
 * MentorDE bağlamındayken bir partner öğrencisi için ticket ya da görev açarsa,
 * o kayıt MENTORDE'nin kutusuna yazılır ve partner firma kendi öğrencisinin
 * kaydını göremez. Bağlamı değiştirince yazma hedefi de partnere geçer.
 *
 * ── YETKİ ────────────────────────────────────────────────────────────────
 * Tek kaynak `User::visibleCompanyIds()`: kendi şirketi + pivot + (denetleyici
 * rolse) alt firmalar. Öğrenci, aday ve bayi rolleri o kümeye zaten girmez.
 *
 * API'deki `config.manage` kapısı burada KULLANILMIYOR: danışmanın da bağlam
 * değiştirmesi gerekiyor ve onun config yetkisi yok. Yetkiyi permission değil
 * görünür küme belirler — kişi zaten göremediği şirkete geçemez.
 */
class CompanyContextSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $targetId = (int) $data['company_id'];

        $allowed = $user->visibleCompanyIds();

        if (!in_array($targetId, $allowed, true)) {
            return back()->withErrors([
                'company_id' => 'Bu firmaya geçme yetkiniz yok.',
            ]);
        }

        $company = Company::query()
            ->where('id', $targetId)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return back()->withErrors([
                'company_id' => 'Firma bulunamadı ya da askıya alınmış.',
            ]);
        }

        $request->session()->put('current_company_id', (int) $company->id);

        return back()->with('status', ($company->brand_name ?: $company->name) . ' bağlamına geçildi.');
    }
}
