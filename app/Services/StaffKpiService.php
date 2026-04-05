<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\MarketingTask;
use App\Models\StaffKpiTarget;
use App\Models\StudentAssignment;
use App\Models\TaskTimeEntry;
use Illuminate\Support\Collection;

class StaffKpiService
{
    /**
     * Tek kullanıcının gerçekleşen KPI değerleri.
     * period: 'YYYY-MM'
     */
    public function getActuals(int $userId, string $period): array
    {
        [$year, $month] = explode('-', $period);

        $tasksDone = MarketingTask::where('assigned_user_id', $userId)
            ->where('status', 'done')
            ->whereYear('completed_at', $year)
            ->whereMonth('completed_at', $month)
            ->count();

        $ticketsResolved = GuestTicket::where('assigned_user_id', $userId)
            ->whereNotNull('closed_at')
            ->whereYear('closed_at', $year)
            ->whereMonth('closed_at', $month)
            ->count();

        $minutesLogged = TaskTimeEntry::where('user_id', $userId)
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->sum('duration_minutes');

        return [
            'tasks_done'         => $tasksDone,
            'tickets_resolved'   => $ticketsResolved,
            'hours_logged'       => round($minutesLogged / 60, 1),
        ];
    }

    /**
     * Birden fazla kullanıcı için bulk aktüel — leaderboard için.
     * Döndürür: Collection keyed by user_id
     */
    public function getAllActuals(string $period, array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        [$year, $month] = explode('-', $period);

        // Tasks
        $tasks = MarketingTask::selectRaw('assigned_user_id, COUNT(*) as cnt')
            ->whereIn('assigned_user_id', $userIds)
            ->where('status', 'done')
            ->whereYear('completed_at', $year)
            ->whereMonth('completed_at', $month)
            ->groupBy('assigned_user_id')
            ->pluck('cnt', 'assigned_user_id');

        // Tickets
        $tickets = GuestTicket::selectRaw('assigned_user_id, COUNT(*) as cnt')
            ->whereIn('assigned_user_id', $userIds)
            ->whereNotNull('closed_at')
            ->whereYear('closed_at', $year)
            ->whereMonth('closed_at', $month)
            ->groupBy('assigned_user_id')
            ->pluck('cnt', 'assigned_user_id');

        // Hours
        $minutes = TaskTimeEntry::selectRaw('user_id, SUM(duration_minutes) as total')
            ->whereIn('user_id', $userIds)
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return collect($userIds)->mapWithKeys(fn ($uid) => [
            $uid => [
                'tasks_done'       => (int) ($tasks[$uid] ?? 0),
                'tickets_resolved' => (int) ($tickets[$uid] ?? 0),
                'hours_logged'     => round((float) ($minutes[$uid] ?? 0) / 60, 1),
            ],
        ]);
    }

    /**
     * Tek kullanıcının hedefleri.
     */
    public function getTargets(int $userId, string $period): ?StaffKpiTarget
    {
        return StaffKpiTarget::where('user_id', $userId)->where('period', $period)->first();
    }

    /**
     * Birden fazla kullanıcının hedefleri — leaderboard için.
     * Döndürür: Collection keyed by user_id
     */
    public function getAllTargets(string $period, array $userIds): Collection
    {
        return StaffKpiTarget::whereIn('user_id', $userIds)
            ->where('period', $period)
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Senior'lar için bulk aktüel — leaderboard için.
     * Metrikler: aktif öğrenci, aktif aday, dönem dönüşümü.
     * Döndürür: Collection keyed by senior email
     */
    public function getAllSeniorActuals(string $period, array $seniorEmails): Collection
    {
        if (empty($seniorEmails)) {
            return collect();
        }

        [$year, $month] = explode('-', $period);

        $activeStudents = StudentAssignment::selectRaw('senior_email, COUNT(*) as cnt')
            ->whereIn('senior_email', $seniorEmails)
            ->where('is_archived', false)
            ->groupBy('senior_email')
            ->pluck('cnt', 'senior_email');

        $activeGuests = GuestApplication::selectRaw('assigned_senior_email, COUNT(*) as cnt')
            ->whereIn('assigned_senior_email', $seniorEmails)
            ->whereNotIn('lead_status', ['converted', 'lost'])
            ->whereNull('deleted_at')
            ->groupBy('assigned_senior_email')
            ->pluck('cnt', 'assigned_senior_email');

        $conversions = GuestApplication::selectRaw('assigned_senior_email, COUNT(*) as cnt')
            ->whereIn('assigned_senior_email', $seniorEmails)
            ->where('lead_status', 'converted')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->whereNull('deleted_at')
            ->groupBy('assigned_senior_email')
            ->pluck('cnt', 'assigned_senior_email');

        return collect($seniorEmails)->mapWithKeys(fn ($email) => [
            $email => [
                'active_students' => (int) ($activeStudents[$email] ?? 0),
                'active_guests'   => (int) ($activeGuests[$email] ?? 0),
                'conversions'     => (int) ($conversions[$email] ?? 0),
            ],
        ]);
    }

    /**
     * Senior bileşik skor: öğrenci * 4 + aday * 2 + dönüşüm * 10, max 100.
     */
    public function calcSeniorScore(array $actuals): int
    {
        return min(100,
            ($actuals['active_students'] ?? 0) * 4 +
            ($actuals['active_guests']   ?? 0) * 2 +
            ($actuals['conversions']     ?? 0) * 10
        );
    }

    /**
     * Bileşik skor: hedefler varsa aktüel/hedef oranlarının ortalaması (0-100).
     * Hedef yoksa aktüel toplamına göre ham skor.
     */
    public function calcScore(array $actuals, ?StaffKpiTarget $target): int
    {
        if (!$target || ($target->target_tasks_done + $target->target_tickets_resolved + $target->target_hours_logged) == 0) {
            // Hedef yok — aktivite toplamı baz alınır
            return min(100, $actuals['tasks_done'] * 3 + $actuals['tickets_resolved'] * 5 + (int) $actuals['hours_logged']);
        }

        $ratios = [];
        if ($target->target_tasks_done > 0) {
            $ratios[] = min(100, $actuals['tasks_done'] / $target->target_tasks_done * 100);
        }
        if ($target->target_tickets_resolved > 0) {
            $ratios[] = min(100, $actuals['tickets_resolved'] / $target->target_tickets_resolved * 100);
        }
        if ($target->target_hours_logged > 0) {
            $ratios[] = min(100, $actuals['hours_logged'] / $target->target_hours_logged * 100);
        }

        return empty($ratios) ? 0 : (int) round(array_sum($ratios) / count($ratios));
    }
}
