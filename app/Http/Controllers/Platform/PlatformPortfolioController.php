<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\LeadTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * KONSOLİDE PORTFÖY — platform sahibinin "hepsini tek yerde" görünümü.
 *
 * ⚠ KİŞİSEL VERİ YOK. BİLEREK.
 *
 * DGmarkt yazılım servisi sağlıyor; müşterilerinin öğrencileri için VERİ
 * SORUMLUSU değil. Ad, e-posta, telefon gibi kişisel veriyi platform
 * konsolunda listelemek KVKK/GDPR açısından savunulamaz — servis sağlayıcının
 * müşterinin müşterisini tanımasını gerektiren bir iş gerekçesi yok.
 *
 * Bu ekranlar SAYI gösterir: şirket başına aday/öğrenci adedi, durum dağılımı,
 * son 30 günün hareketi. İş hacmini görmek için yeterli, kişiyi tanımak için değil.
 *
 * Kişi düzeyindeki işler operasyonu YÜRÜTEN şirkete (MentorDE) aittir; onun
 * personeli hiyerarşi sayesinde partner adaylarını kendi ekranlarında görür.
 */
class PlatformPortfolioController extends Controller
{
    /** Tüm şirketlerin aday SAYILARI — kişi listesi değil. */
    public function leads(Request $request): View
    {
        return view('platform.portfolio.leads', [
            'companies'    => $this->companyRows(),
            'statusTotals' => $this->leadStatusTotals(),
            'statusLabels' => $this->leadStatusOptions(),
            'grandTotal'   => array_sum($this->leadTotalsByCompany()),
            'recent30'     => $this->recentLeadCount(),
        ]);
    }

    /** Tüm şirketlerin öğrenci SAYILARI — kişi listesi değil. */
    public function students(Request $request): View
    {
        return view('platform.portfolio.students', [
            'companies'  => $this->companyRows(),
            'grandTotal' => array_sum($this->studentTotalsByCompany()),
        ]);
    }

    /**
     * Adayı başka bir firmaya devret.
     *
     * Kişisel veri göstermez, ID ile çalışır. Firma başvuru linkini
     * kullandıramadığında kayıt B2C havuzuna düşer; operasyon ekibi adayı
     * kendi ekranından bulup buraya ID'siyle gönderir.
     *
     * Bağlı tüm kayıtlar birlikte taşınır — bkz. LeadTransferService.
     */
    public function transferLead(Request $request, LeadTransferService $transfers, int $application): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ], [
            'company_id.required' => 'Hedef firma seçilmedi.',
            'company_id.exists'   => 'Hedef firma bulunamadı.',
        ]);

        $lead = GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('id', $application)
            ->firstOrFail();

        $target = Company::query()->findOrFail((int) $validated['company_id']);

        try {
            $result = $transfers->transfer($lead, $target);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['company_id' => $e->getMessage()]);
        }

        \App\Models\PlatformAuditLog::record('platform.lead.transferred', [
            'target_type'   => 'guest_application',
            'target_id'     => $lead->id,
            'company_from'  => $result['company_from'],
            'company_to'    => $result['company_to'],
            'tables'        => $result['tables'],
            // Aday adı/e-postası audit'e YAZILMAZ — tenant kişisel verisi.
        ]);

        $message = 'Aday #' . $lead->id . ' → ' . ($target->brand_name ?: $target->name) . ' devredildi.';

        if ($result['senior_cleared']) {
            $message .= ' Eski danışman ataması kaldırıldı.';
        }

        return back()->with('status', $message);
    }

    /**
     * Şirket başına özet satırlar — yalnızca sayı.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function companyRows()
    {
        $leadTotals = $this->leadTotalsByCompany();
        $studentTotals = $this->studentTotalsByCompany();

        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'brand_name', 'code', 'is_active', 'parent_company_id'])
            ->map(fn (Company $c): array => [
                'id'       => (int) $c->id,
                'name'     => (string) ($c->brand_name ?: $c->name),
                'code'     => (string) $c->code,
                'active'   => (bool) $c->is_active,
                'parent'   => $c->parent_company_id ? (int) $c->parent_company_id : null,
                'leads'    => (int) ($leadTotals[$c->id] ?? 0),
                'students' => (int) ($studentTotals[$c->id] ?? 0),
            ]);
    }

    /** @return array<int,int> company_id => aday sayısı */
    private function leadTotalsByCompany(): array
    {
        return Cache::remember('platform:portfolio:lead_totals', 120, fn (): array => GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where(function ($q): void {
                $q->whereNull('converted_to_student')->orWhere('converted_to_student', false);
            })
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id')
            ->map(fn ($v): int => (int) $v)
            ->all());
    }

    /** @return array<int,int> company_id => öğrenci sayısı */
    private function studentTotalsByCompany(): array
    {
        return Cache::remember('platform:portfolio:student_totals', 120, fn (): array => User::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('role', User::ROLE_STUDENT)
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id')
            ->map(fn ($v): int => (int) $v)
            ->all());
    }

    /** @return array<string,int> durum => adet (şirketten bağımsız toplam) */
    private function leadStatusTotals(): array
    {
        return Cache::remember('platform:portfolio:lead_status_totals', 120, fn (): array => GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where(function ($q): void {
                $q->whereNull('converted_to_student')->orWhere('converted_to_student', false);
            })
            ->selectRaw('lead_status, count(*) as total')
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->map(fn ($v): int => (int) $v)
            ->all());
    }

    private function recentLeadCount(): int
    {
        return (int) Cache::remember('platform:portfolio:lead_recent30', 120, fn (): int => GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->count());
    }

    /** @return array<string,string> */
    private function leadStatusOptions(): array
    {
        return [
            'new' => 'Yeni',
            'contacted' => 'İletişime geçildi',
            'qualified' => 'Nitelikli',
            'proposal' => 'Teklif',
            'contract_signed' => 'Sözleşme imzalandı',
            'won' => 'Kazanıldı',
            'lost' => 'Kaybedildi',
        ];
    }
}
