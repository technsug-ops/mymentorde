<?php

namespace App\Services;

use App\Models\StudentAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Öğrenci (StudentAssignment) listesi için filtre ve KPI mantığını kapsüller.
 *
 * ManagerPortalController::students() ve studentsExportCsv() ortak
 * query builder'ı bu servis üzerinden oluşturur; kod tekrarı ortadan kalkar.
 */
class StudentListService
{
    /**
     * Filtreleri uygulanmış Eloquent builder'ı döndürür.
     * Paginate veya get() çağrısı controller'da yapılır.
     *
     * @param array{q:string,senior:string,branch:string,risk:string,payment:string,archived:string} $filters
     */
    public function filteredQuery(int $cid, array $filters): Builder
    {
        $q       = trim((string) ($filters['q'] ?? ''));
        $senior  = trim((string) ($filters['senior'] ?? ''));
        $branch  = trim((string) ($filters['branch'] ?? ''));
        $risk    = trim((string) ($filters['risk'] ?? ''));
        $payment = trim((string) ($filters['payment'] ?? ''));
        $arch    = (string) ($filters['archived'] ?? '0');

        return StudentAssignment::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->when($q !== '', fn ($b) => $b->where(function ($b2) use ($q) {
                $like = '%' . $q . '%';
                $b2->where('student_id', 'like', $like)
                   ->orWhereRaw('lower(senior_email) like ?', [strtolower($like)]);
            }))
            ->when($senior !== '', fn ($b) => $b->whereRaw('lower(senior_email) = ?', [strtolower($senior)]))
            ->when($branch !== '', fn ($b) => $b->where('branch', $branch))
            ->when($risk !== '', fn ($b) => $b->where('risk_level', $risk))
            ->when($payment !== '', fn ($b) => $b->where('payment_status', $payment))
            ->when($arch === '1', fn ($b) => $b->where('is_archived', true))
            ->when($arch !== '1', fn ($b) => $b->where('is_archived', false));
    }

    /**
     * Global (filtre bağımsız) öğrenci KPI sayımları.
     *
     * @return array{active:int,archived:int,high_risk:int}
     */
    public function kpis(int $cid): array
    {
        $base = StudentAssignment::query()->when($cid > 0, fn ($b) => $b->where('company_id', $cid));

        return [
            'active'    => (clone $base)->where('is_archived', false)->count(),
            'archived'  => (clone $base)->where('is_archived', true)->count(),
            'high_risk' => (clone $base)->where('is_archived', false)->where('risk_level', 'high')->count(),
        ];
    }

    /**
     * Filtre dropdown seçeneklerini döndürür.
     *
     * @return array{seniorOptions:Collection,branchOptions:Collection}
     */
    public function filterOptions(int $cid): array
    {
        $build = function () use ($cid): array {
            $seniorOptions = StudentAssignment::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->distinct()->pluck('senior_email')->filter()->sort()->values();

            $branchOptions = StudentAssignment::query()
                ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
                ->distinct()->pluck('branch')->filter()->sort()->values();

            return compact('seniorOptions', 'branchOptions');
        };

        if ($cid <= 0) {
            return $build();
        }

        return Cache::remember("student_filter_opts_{$cid}", 30, $build);
    }
}
