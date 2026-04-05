<?php

namespace App\Http\Controllers;

use App\Models\UserAvailabilitySchedule;
use App\Models\UserAwayPeriod;
use App\Services\PresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserAvailabilityController extends Controller
{
    // ── Çalışma Saatleri ─────────────────────────────────────────────────────

    public function scheduleIndex(Request $request)
    {
        $user      = $request->user();
        $schedules = UserAvailabilitySchedule::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        $presence = PresenceService::getPresence($user);

        return view('availability.index', compact('schedules', 'presence'));
    }

    public function scheduleSave(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'schedules'             => ['nullable', 'array'],
            'schedules.*.day'       => ['required', 'integer', 'between:0,6'],
            'schedules.*.start'     => ['required', 'date_format:H:i'],
            'schedules.*.end'       => ['required', 'date_format:H:i', 'after:schedules.*.start'],
            'schedules.*.active'    => ['nullable', 'boolean'],
            'timezone'              => ['nullable', 'string', 'max:60', 'timezone'],
        ]);

        $tz = $validated['timezone'] ?? 'Europe/Berlin';

        // Mevcut schedule'ları sil, yeniden ekle
        UserAvailabilitySchedule::where('user_id', $user->id)->delete();

        foreach ($validated['schedules'] ?? [] as $row) {
            UserAvailabilitySchedule::create([
                'user_id'    => $user->id,
                'day_of_week'=> (int) $row['day'],
                'start_time' => $row['start'] . ':00',
                'end_time'   => $row['end'] . ':00',
                'timezone'   => $tz,
                'is_active'  => !empty($row['active']),
            ]);
        }

        Cache::forget("presence_{$user->id}");

        return back()->with('status', 'Çalışma saatleri kaydedildi.');
    }

    // ── Away Periods ──────────────────────────────────────────────────────────

    public function awayStore(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'away_from'           => ['required', 'date', 'after_or_equal:today'],
            'away_until'          => ['required', 'date', 'after:away_from'],
            'away_message'        => ['nullable', 'string', 'max:300'],
            'auto_reply_enabled'  => ['nullable', 'boolean'],
            'auto_reply_message'  => ['nullable', 'string', 'max:500'],
            'timezone'            => ['nullable', 'string', 'max:60', 'timezone'],
        ]);

        UserAwayPeriod::create([
            'user_id'             => $user->id,
            'away_from'           => $validated['away_from'],
            'away_until'          => $validated['away_until'],
            'away_message'        => $validated['away_message'] ?? null,
            'auto_reply_enabled'  => (bool) ($validated['auto_reply_enabled'] ?? true),
            'auto_reply_message'  => $validated['auto_reply_message'] ?? null,
            'timezone'            => $validated['timezone'] ?? 'Europe/Berlin',
        ]);

        Cache::forget("presence_{$user->id}");

        return back()->with('status', 'Dışarıda olma dönemi kaydedildi.');
    }

    public function awayDelete(Request $request, UserAwayPeriod $awayPeriod)
    {
        $user = $request->user();
        abort_if((int) $awayPeriod->user_id !== (int) $user->id, 403);

        $awayPeriod->delete();

        Cache::forget("presence_{$user->id}");

        return back()->with('status', 'Dönem silindi.');
    }

    // ── Presence API (AJAX) ───────────────────────────────────────────────────

    /**
     * Bir veya birden fazla kullanıcının presence durumunu döndürür.
     * GET /api/presence?ids=1,2,3
     */
    public function presenceApi(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(50)
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json([]);
        }

        return response()->json(PresenceService::getBulkPresence($ids));
    }
}
