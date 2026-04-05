<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrAttendance;
use Illuminate\Http\Request;

class HrAttendanceController extends Controller
{
    // Staff: bugünkü devam durumunu göster + giriş/çıkış
    public function myToday(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();

        $attendance = HrAttendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['company_id' => $user->company_id, 'status' => 'present']
        );

        $recentDays = HrAttendance::where('user_id', $user->id)
            ->where('work_date', '>=', now()->subDays(7)->toDateString())
            ->orderByDesc('work_date')
            ->get();

        return view('hr.my.attendance', compact('attendance', 'recentDays'));
    }

    // POST: Giriş yap
    public function checkIn(Request $request): \Illuminate\Http\JsonResponse
    {
        $user           = $request->user();
        $today          = now()->toDateString();
        $lateThreshold  = 9; // 09:00'dan sonra geç

        $att = HrAttendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['company_id' => $user->company_id]
        );

        if ($att->check_in_at) {
            return response()->json(['error' => 'Zaten giriş yapıldı'], 422);
        }

        $status = now()->hour >= $lateThreshold ? 'late' : 'present';
        $att->update(['check_in_at' => now(), 'status' => $status]);

        return response()->json([
            'ok'          => true,
            'check_in_at' => $att->check_in_at->format('H:i'),
            'status'      => $status,
        ]);
    }

    // POST: Çıkış yap
    public function checkOut(Request $request): \Illuminate\Http\JsonResponse
    {
        $user           = $request->user();
        $today          = now()->toDateString();

        $att = HrAttendance::where('user_id', $user->id)->where('work_date', $today)->first();

        if (!$att || !$att->check_in_at) {
            return response()->json(['error' => 'Önce giriş yapılmalı'], 422);
        }
        if ($att->check_out_at) {
            return response()->json(['error' => 'Zaten çıkış yapıldı'], 422);
        }

        $earlyThreshold = 17; // 17:00'dan önce erken çıkış
        $status         = $att->status;
        if (now()->hour < $earlyThreshold) {
            $status = 'early_leave';
        }

        $minutes = (int) now()->diffInMinutes($att->check_in_at);
        $att->update([
            'check_out_at' => now(),
            'work_minutes' => $minutes,
            'status'       => $status,
        ]);

        return response()->json([
            'ok'           => true,
            'check_out_at' => $att->check_out_at->format('H:i'),
            'work_minutes' => $minutes,
        ]);
    }

    // Manager: aylık devam raporu
    public function managerReport(Request $request)
    {
        $cid    = (int) ($request->user()?->company_id ?? 0);
        $month  = $request->query('month', now()->format('Y-m'));
        $isSqlite = config('database.default') === 'sqlite';

        $records = HrAttendance::with('user:id,name,email')
            ->where('company_id', $cid)
            ->when(
                $isSqlite,
                fn($q) => $q->whereRaw("strftime('%Y-%m', work_date) = ?", [$month]),
                fn($q) => $q->whereRaw("DATE_FORMAT(work_date, '%Y-%m') = ?", [$month])
            )
            ->orderBy('work_date')
            ->orderBy('user_id')
            ->get();

        $byUser = $records->groupBy('user_id');

        $stats = [
            'total_days'  => $records->count(),
            'late_count'  => $records->where('status', 'late')->count(),
            'absent_count'=> $records->where('status', 'absent')->count(),
            'avg_minutes' => $records->where('work_minutes', '>', 0)->avg('work_minutes') ?? 0,
        ];

        return view('manager.hr.attendance', compact('byUser', 'stats', 'month'));
    }
}
