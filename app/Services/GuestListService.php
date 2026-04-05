<?php

namespace App\Services;

use App\Models\GuestApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Guest başvuru listesi için filtre ve KPI mantığını kapsüller.
 *
 * ManagerPortalController::guests() ve guestsExportCsv() ortak
 * query builder'ı bu servis üzerinden oluşturur; kod tekrarı ortadan kalkar.
 */
class GuestListService
{
    /**
     * Filtreleri uygulanmış Eloquent builder'ı döndürür.
     * Paginate veya get() çağrısı controller'da yapılır.
     *
     * @param array{q:string,status:string,senior:string,dealer:string,converted:string} $filters
     */
    public function filteredQuery(int $cid, array $filters): Builder
    {
        $q         = trim((string) ($filters['q'] ?? ''));
        $status    = trim((string) ($filters['status'] ?? ''));
        $senior    = trim((string) ($filters['senior'] ?? ''));
        $dealer    = trim((string) ($filters['dealer'] ?? ''));
        $converted = (string) ($filters['converted'] ?? '');

        $useFulltext = $q !== '' && config('database.default') !== 'sqlite' && strlen($q) >= 3;

        return GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->when($q !== '' && $useFulltext, fn ($b) => $b->whereRaw(
                'MATCH(first_name, last_name, email, phone) AGAINST(? IN BOOLEAN MODE)',
                [$q . '*']
            ))
            ->when($q !== '' && !$useFulltext, fn ($b) => $b->where(function ($b2) use ($q) {
                $like = '%' . $q . '%';
                $b2->where('first_name', 'like', $like)
                   ->orWhere('last_name', 'like', $like)
                   ->orWhere('email', 'like', $like)
                   ->orWhere('phone', 'like', $like)
                   ->orWhere('tracking_token', 'like', $like)
                   ->orWhere('converted_student_id', 'like', $like);
            }))
            ->when($status !== '', fn ($b) => $b->where('lead_status', $status))
            ->when($senior !== '', fn ($b) => $b->whereRaw('lower(assigned_senior_email) = ?', [strtolower($senior)]))
            ->when($dealer !== '', fn ($b) => $b->where('dealer_code', $dealer))
            ->when($converted === '1', fn ($b) => $b->where('converted_to_student', true))
            ->when($converted === '0', fn ($b) => $b->where('converted_to_student', false))
            ->where('is_archived', false);
    }

    /**
     * Filtreli base sorgudan KPI sayımlarını hesaplar.
     *
     * @return array{total:int,converted:int,unassigned:int,today:int}
     */
    public function kpis(Builder $base): array
    {
        return [
            'total'      => (clone $base)->count(),
            'converted'  => (clone $base)->where('converted_to_student', true)->count(),
            'unassigned' => (clone $base)->whereNull('assigned_senior_email')->where('converted_to_student', false)->count(),
            'today'      => (clone $base)->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Filtre dropdown seçeneklerini döndürür.
     *
     * @return array{seniorOptions:Collection,statusOptions:list<string>}
     */
    public function filterOptions(int $cid): array
    {
        $build = function () use ($cid): array {
            $seniorOptions = GuestApplication::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->whereNotNull('assigned_senior_email')
                ->distinct()->pluck('assigned_senior_email')->sort()->values();

            return [
                'seniorOptions' => $seniorOptions,
                'statusOptions' => ['new', 'contacted', 'qualified', 'converted', 'lost'],
            ];
        };

        if ($cid <= 0) {
            return $build();
        }

        return Cache::remember("guest_filter_opts_{$cid}", 30, $build);
    }
}
