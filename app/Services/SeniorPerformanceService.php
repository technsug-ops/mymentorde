<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\SeniorPerformanceSnapshot;
use App\Models\StudentAssignment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentRevenue;
use App\Models\StudentUniversityApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeniorPerformanceService
{
    /**
     * Verilen ay için tüm senior'ların performans snapshotlarını kaydet/güncelle.
     *
     * @param string|null $period 'YYYY-MM' formatında — null ise mevcut ay
     */
    public function snapshotMonth(?string $period = null): int
    {
        $period = $period ?? now()->format('Y-m');
        $start  = Carbon::parse($period . '-01')->startOfMonth();
        $end    = $start->copy()->endOfMonth();

        // Bulk load: N senior × 8 sorgu yerine 6 sorgu toplam
        $assignments = StudentAssignment::whereNotNull('senior_email')->get();

        // senior_email → {student_ids, company_id, active_count}
        $byEmail = [];
        foreach ($assignments as $a) {
            $email = strtolower((string) $a->senior_email);
            if (!isset($byEmail[$email])) {
                $byEmail[$email] = ['company_id' => $a->company_id, 'student_ids' => [], 'active_count' => 0];
            }
            $byEmail[$email]['student_ids'][] = $a->student_id;
            if (!$a->is_archived) {
                $byEmail[$email]['active_count']++;
            }
        }

        if (empty($byEmail)) {
            return 0;
        }

        $allStudentIds = collect($byEmail)->flatMap(fn ($r) => $r['student_ids'])->filter()->unique()->values()->all();

        // Bulk: GuestApplication
        $guestsByStudentId = GuestApplication::whereIn('converted_student_id', $allStudentIds)
            ->get(['converted_student_id', 'converted_to_student', 'converted_at', 'created_at', 'contract_status']);

        // Bulk: StudentUniversityApplication
        $uniApps = StudentUniversityApplication::whereIn('student_id', $allStudentIds)
            ->whereBetween('result_at', [$start, $end])
            ->get(['student_id', 'status']);

        // Bulk: StudentInstitutionDocument
        $visaDocs = StudentInstitutionDocument::whereIn('student_id', $allStudentIds)
            ->where('document_type_code', 'VIS-ERTEIL')
            ->whereNotIn('status', ['expected', 'archived'])
            ->whereBetween('updated_at', [$start, $end])
            ->get(['student_id']);

        // Bulk: StudentRevenue
        $revenues = StudentRevenue::whereIn('student_id', $allStudentIds)
            ->whereBetween('created_at', [$start, $end])
            ->get(['student_id', 'amount_paid']);

        $count = 0;
        foreach ($byEmail as $seniorEmail => $data) {
            $studentIds = array_filter(array_unique($data['student_ids']));
            if (empty($studentIds)) {
                continue;
            }
            $studentSet = array_flip($studentIds);

            $converted   = $guestsByStudentId->filter(fn ($g) => isset($studentSet[$g->converted_student_id]) && $g->converted_to_student);
            $convertedCount = $converted->count();

            // AVG process days (in-memory)
            $processable = $converted->filter(fn ($g) => $g->converted_at && $g->created_at);
            $avgDays = $processable->isEmpty() ? null
                : round($processable->avg(fn ($g) => Carbon::parse($g->created_at)->diffInDays(Carbon::parse($g->converted_at))), 1);

            $uniAccepted = $uniApps->filter(fn ($u) => isset($studentSet[$u->student_id]) && in_array($u->status, ['accepted', 'conditional_accepted'], true))->count();
            $uniRejected = $uniApps->filter(fn ($u) => isset($studentSet[$u->student_id]) && $u->status === 'rejected')->count();
            $visaCount   = $visaDocs->filter(fn ($v) => isset($studentSet[$v->student_id]))->count();
            $revenue     = $revenues->filter(fn ($r) => isset($studentSet[$r->student_id]))->sum('amount_paid');

            SeniorPerformanceSnapshot::updateOrCreate(
                ['senior_email' => $seniorEmail, 'period' => $period],
                [
                    'company_id'                => $data['company_id'],
                    'student_count'             => count($studentIds),
                    'active_count'              => $data['active_count'],
                    'converted_count'           => $convertedCount,
                    'university_accepted_count' => $uniAccepted,
                    'university_rejected_count' => $uniRejected,
                    'visa_approved_count'       => $visaCount,
                    'avg_process_days'          => $avgDays,
                    'revenue_generated'         => (float) $revenue,
                    'snapshotted_at'            => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Tek senior için anlık performans verilerini döndür (snapshot kaydetmez).
     * Senior performans sayfası için kullanılır.
     */
    public function getMyStats(string $seniorEmail): array
    {
        $studentIds = StudentAssignment::whereRaw('lower(senior_email) = ?', [strtolower($seniorEmail)])
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentIds)) {
            return $this->emptyStats();
        }

        // Üniversite kabul/ret
        $uniAccepted = StudentUniversityApplication::whereIn('student_id', $studentIds)
            ->whereIn('status', ['accepted', 'conditional_accepted'])
            ->count();
        $uniRejected = StudentUniversityApplication::whereIn('student_id', $studentIds)
            ->where('status', 'rejected')
            ->count();
        $uniTotal = StudentUniversityApplication::whereIn('student_id', $studentIds)->count();

        // Vize onaylanan
        $visaApproved = StudentInstitutionDocument::whereIn('student_id', $studentIds)
            ->where('document_type_code', 'VIS-ERTEIL')
            ->whereNotIn('status', ['expected', 'archived'])
            ->count();

        // Süreç süresi (bu senior'ın dönüştürdüğü öğrenciler)
        $avgDays = GuestApplication::whereIn('converted_student_id', $studentIds)
            ->whereNotNull('converted_at')
            ->selectRaw($this->dateDiffExpr('converted_at', 'created_at') . ' as avg_days')
            ->value('avg_days');

        // Sistem geneli ortalama (karşılaştırma için)
        $systemAvgDays = GuestApplication::whereNotNull('converted_at')
            ->where('converted_to_student', true)
            ->selectRaw($this->dateDiffExpr('converted_at', 'created_at') . ' as avg_days')
            ->value('avg_days');

        // Sözleşme imzalayanlar (funnel adımı)
        $contractSigned = GuestApplication::whereIn('converted_student_id', $studentIds)
            ->whereIn('contract_status', ['signed_uploaded', 'approved'])
            ->count();

        return [
            'uni_accepted'      => (int) $uniAccepted,
            'uni_rejected'      => (int) $uniRejected,
            'uni_total'         => (int) $uniTotal,
            'uni_acceptance_rate' => ($uniAccepted + $uniRejected) > 0
                ? round($uniAccepted / ($uniAccepted + $uniRejected) * 100, 1)
                : 0.0,
            'visa_approved'     => (int) $visaApproved,
            'avg_process_days'  => $avgDays ? (int) round((float) $avgDays) : null,
            'system_avg_days'   => $systemAvgDays ? (int) round((float) $systemAvgDays) : null,
            'contract_signed'   => (int) $contractSigned,
        ];
    }

    /**
     * Manager/Marketing için senior leaderboard (son N ay snapshot verisi).
     */
    public function getLeaderboard(?int $companyId = null, int $months = 3): Collection
    {
        $periods = collect(range(0, $months - 1))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'))
            ->all();

        $query = SeniorPerformanceSnapshot::whereIn('period', $periods);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()
            ->groupBy('senior_email')
            ->map(function (Collection $rows, string $email): array {
                return [
                    'senior_email'            => $email,
                    'student_count'           => (int) $rows->sum('student_count'),
                    'converted_count'         => (int) $rows->sum('converted_count'),
                    'university_accepted'     => (int) $rows->sum('university_accepted_count'),
                    'university_rejected'     => (int) $rows->sum('university_rejected_count'),
                    'visa_approved'           => (int) $rows->sum('visa_approved_count'),
                    'revenue_generated'       => (float) $rows->sum('revenue_generated'),
                    'avg_process_days'        => $rows->avg('avg_process_days')
                        ? round((float) $rows->avg('avg_process_days'), 1)
                        : null,
                ];
            })
            ->values();
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function snapshotSenior(string $seniorEmail, string $period, Carbon $start, Carbon $end): void
    {
        $studentIds = StudentAssignment::whereRaw('lower(senior_email) = ?', [strtolower($seniorEmail)])
            ->pluck('student_id')->filter()->unique()->values()->all();

        if (empty($studentIds)) return;

        $companyId = StudentAssignment::whereRaw('lower(senior_email) = ?', [strtolower($seniorEmail)])
            ->value('company_id');

        $activeCount    = StudentAssignment::whereRaw('lower(senior_email) = ?', [strtolower($seniorEmail)])
            ->where('is_archived', false)->count();
        $convertedCount = GuestApplication::whereIn('converted_student_id', $studentIds)
            ->where('converted_to_student', true)->count();

        $uniAccepted = StudentUniversityApplication::whereIn('student_id', $studentIds)
            ->whereIn('status', ['accepted', 'conditional_accepted'])
            ->whereBetween('result_at', [$start, $end])->count();

        $uniRejected = StudentUniversityApplication::whereIn('student_id', $studentIds)
            ->where('status', 'rejected')
            ->whereBetween('result_at', [$start, $end])->count();

        $visaApproved = StudentInstitutionDocument::whereIn('student_id', $studentIds)
            ->where('document_type_code', 'VIS-ERTEIL')
            ->whereNotIn('status', ['expected', 'archived'])
            ->whereBetween('updated_at', [$start, $end])->count();

        $avgDays = GuestApplication::whereIn('converted_student_id', $studentIds)
            ->whereNotNull('converted_at')
            ->selectRaw($this->dateDiffExpr('converted_at', 'created_at') . ' as avg_days')
            ->value('avg_days');

        $revenue = StudentRevenue::whereIn('student_id', $studentIds)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount_paid');

        SeniorPerformanceSnapshot::updateOrCreate(
            ['senior_email' => $seniorEmail, 'period' => $period],
            [
                'company_id'                => $companyId,
                'student_count'             => count($studentIds),
                'active_count'              => $activeCount,
                'converted_count'           => $convertedCount,
                'university_accepted_count' => $uniAccepted,
                'university_rejected_count' => $uniRejected,
                'visa_approved_count'       => $visaApproved,
                'avg_process_days'          => $avgDays ? round((float) $avgDays, 1) : null,
                'revenue_generated'         => (float) $revenue,
                'snapshotted_at'            => now(),
            ]
        );
    }

    /**
     * SQLite-uyumlu tarih farkı ifadesi (MySQL'de DATEDIFF, SQLite'ta julianday farkı).
     */
    private function dateDiffExpr(string $col1, string $col2): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "AVG(CAST((julianday({$col1}) - julianday({$col2})) AS INTEGER))";
        }
        return "AVG(DATEDIFF({$col1}, {$col2}))";
    }

    private function emptyStats(): array
    {
        return [
            'uni_accepted' => 0, 'uni_rejected' => 0, 'uni_total' => 0,
            'uni_acceptance_rate' => 0.0, 'visa_approved' => 0,
            'avg_process_days' => null, 'system_avg_days' => null, 'contract_signed' => 0,
        ];
    }
}
