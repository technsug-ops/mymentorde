<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * KONSOLİDE PORTFÖY — platform sahibinin "hepsini tek yerde" görünümü (Faz 5).
 *
 * Multi-tenant izolasyonu her firmayı kendi verisine hapseder; bu doğru ve
 * gereklidir. Ama platform sahibi (MentorDE) B2C öğrencilerini ve tüm partner
 * firmaların adaylarını TEK listede görmek ister — iş modelinin özü bu.
 *
 * Burada global scope bilinçli olarak atlanır (`withoutGlobalScope('company')`)
 * ve her satırda hangi şirkete ait olduğu AÇIKÇA gösterilir. Bu sayfalar
 * yalnızca `platform.owner` middleware'i arkasında; firma kullanıcıları
 * buraya asla erişemez.
 *
 * ⚠ Bu, tenant izolasyonunun bir istisnası değil — izolasyonun ÜSTÜNDE duran
 * bir yetki katmanı. Firma kullanıcısının sorguları hâlâ kendi şirketiyle sınırlı.
 */
class PlatformPortfolioController extends Controller
{
    private const PER_PAGE = 40;

    /** Tüm şirketlerin adayları (henüz öğrenciye dönüşmemiş). */
    public function leads(Request $request): View
    {
        $filters = $this->filters($request);

        $query = GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where(function ($q): void {
                $q->whereNull('converted_to_student')->orWhere('converted_to_student', false);
            });

        $this->applyCompanyFilter($query, $filters['company']);
        $this->applySearch($query, $filters['q'], ['first_name', 'last_name', 'email', 'phone']);

        if ($filters['status'] !== '') {
            $query->where('lead_status', $filters['status']);
        }

        $rows = $query->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('platform.portfolio.leads', [
            'rows' => $rows,
            'companies' => $this->companyOptions(),
            'companyNames' => $this->companyNames(),
            'filters' => $filters,
            'statusOptions' => $this->leadStatusOptions(),
            'totals' => $this->leadTotalsByCompany(),
        ]);
    }

    /** Tüm şirketlerin öğrencileri (dönüşmüş kayıtlar). */
    public function students(Request $request): View
    {
        $filters = $this->filters($request);

        $query = User::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('role', User::ROLE_STUDENT);

        $this->applyCompanyFilter($query, $filters['company']);
        $this->applySearch($query, $filters['q'], ['name', 'email', 'student_id']);

        $rows = $query->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('platform.portfolio.students', [
            'rows' => $rows,
            'companies' => $this->companyOptions(),
            'companyNames' => $this->companyNames(),
            'filters' => $filters,
            'totals' => $this->studentTotalsByCompany(),
        ]);
    }

    /** @return array{company:int,q:string,status:string} */
    private function filters(Request $request): array
    {
        return [
            'company' => (int) $request->query('company', 0),
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];
    }

    private function applyCompanyFilter($query, int $companyId): void
    {
        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }
    }

    /** @param list<string> $columns */
    private function applySearch($query, string $term, array $columns): void
    {
        if ($term === '') {
            return;
        }

        $query->where(function ($q) use ($term, $columns): void {
            foreach ($columns as $i => $column) {
                $i === 0
                    ? $q->where($column, 'like', '%' . $term . '%')
                    : $q->orWhere($column, 'like', '%' . $term . '%');
            }
        });
    }

    /** @return \Illuminate\Support\Collection<int,Company> */
    private function companyOptions()
    {
        return Company::query()->orderBy('name')->get(['id', 'name', 'code', 'brand_name']);
    }

    /** @return array<int,string> id => görünen ad */
    private function companyNames(): array
    {
        return $this->companyOptions()
            ->mapWithKeys(fn (Company $c): array => [
                (int) $c->id => (string) ($c->brand_name ?: $c->name),
            ])
            ->all();
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

    /** @return array<string,string> */
    private function leadStatusOptions(): array
    {
        return [
            'new' => 'Yeni',
            'contacted' => 'İletişime geçildi',
            'qualified' => 'Nitelikli',
            'proposal' => 'Teklif',
            'won' => 'Kazanıldı',
            'lost' => 'Kaybedildi',
        ];
    }
}
