<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\SeniorAvailabilityException;
use App\Models\SeniorAvailabilityPattern;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Senior booking ayarları — haftalık pattern, istisna, genel ayar.
 * Route: /senior/booking-settings (auth + senior.role + module:booking)
 */
class SeniorAvailabilityController extends Controller
{
    private const SUPPORTED_TIMEZONES = [
        'Europe/Berlin', 'Europe/Istanbul', 'Europe/London', 'Europe/Paris',
        'Europe/Madrid', 'Europe/Amsterdam', 'Europe/Zurich', 'Europe/Vienna',
        'America/New_York', 'America/Los_Angeles', 'Asia/Dubai', 'Asia/Tokyo',
    ];

    public function index(Request $request): View
    {
        $user     = $request->user();
        $settings = $this->ensureSettings($user);

        $patterns = SeniorAvailabilityPattern::query()
            ->where('senior_user_id', $user->id)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $exceptions = SeniorAvailabilityException::query()
            ->where('senior_user_id', $user->id)
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->get();

        return view('booking.senior.settings', [
            'settings'           => $settings,
            'patterns'           => $patterns,
            'exceptions'         => $exceptions,
            'weekdayLabels'      => SeniorAvailabilityPattern::WEEKDAY_LABELS_TR,
            'supportedTimezones' => self::SUPPORTED_TIMEZONES,
            'publicUrl'          => $settings->is_public && $settings->public_slug
                ? route('booking.public.show', ['slug' => $settings->public_slug])
                : null,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'slot_duration'    => 'required|integer|min:15|max:240',
            'buffer_minutes'   => 'required|integer|min:0|max:120',
            'min_notice_hours' => 'required|integer|min:0|max:168',
            'max_future_days'  => 'required|integer|min:7|max:365',
            'timezone'         => 'required|string|in:' . implode(',', self::SUPPORTED_TIMEZONES),
            'is_public'        => 'nullable|boolean',
            'display_name'     => 'nullable|string|max:120',
            'welcome_message'  => 'nullable|string|max:2000',
            'is_active'        => 'nullable|boolean',
        ]);

        $settings = $this->ensureSettings($user);

        $updates = [
            'slot_duration'    => (int) $data['slot_duration'],
            'buffer_minutes'   => (int) $data['buffer_minutes'],
            'min_notice_hours' => (int) $data['min_notice_hours'],
            'max_future_days'  => (int) $data['max_future_days'],
            'timezone'         => $data['timezone'],
            'is_public'        => (bool) ($data['is_public'] ?? false),
            'display_name'     => $data['display_name'] ?? null,
            'welcome_message'  => $data['welcome_message'] ?? null,
            'is_active'        => (bool) ($data['is_active'] ?? true),
        ];

        // Public hale geliyorsa slug garanti et
        if ($updates['is_public'] && empty($settings->public_slug)) {
            $updates['public_slug'] = $this->generateUniqueSlug($user);
        }

        $settings->update($updates);
        $this->flushSlotCache($user->id);

        return back()->with('status', 'Ayarlar güncellendi.');
    }

    public function storePattern(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'weekday'    => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        SeniorAvailabilityPattern::create([
            'senior_user_id' => $user->id,
            'weekday'        => (int) $data['weekday'],
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'is_active'      => true,
        ]);

        $this->flushSlotCache($user->id);
        return back()->with('status', 'Müsaitlik aralığı eklendi.');
    }

    public function destroyPattern(Request $request, int $pattern): RedirectResponse
    {
        $user = $request->user();
        SeniorAvailabilityPattern::where('id', $pattern)
            ->where('senior_user_id', $user->id)
            ->delete();
        $this->flushSlotCache($user->id);
        return back()->with('status', 'Müsaitlik aralığı silindi.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'date'                => 'required|date|after_or_equal:today',
            'is_blocked'          => 'required|in:0,1',
            'override_start_time' => 'nullable|required_if:is_blocked,0|date_format:H:i',
            'override_end_time'   => 'nullable|required_if:is_blocked,0|date_format:H:i|after:override_start_time',
            'reason'              => 'nullable|string|max:255',
        ]);

        $isBlocked = (bool) $data['is_blocked'];

        SeniorAvailabilityException::updateOrCreate(
            [
                'senior_user_id' => $user->id,
                'date'           => $data['date'],
            ],
            [
                'is_blocked'          => $isBlocked,
                'override_start_time' => $isBlocked ? null : ($data['override_start_time'] ?? null),
                'override_end_time'   => $isBlocked ? null : ($data['override_end_time'] ?? null),
                'reason'              => $data['reason'] ?? null,
            ]
        );

        $this->flushSlotCache($user->id);
        return back()->with('status', 'İstisna kaydedildi.');
    }

    public function destroyException(Request $request, int $exception): RedirectResponse
    {
        $user = $request->user();
        SeniorAvailabilityException::where('id', $exception)
            ->where('senior_user_id', $user->id)
            ->delete();
        $this->flushSlotCache($user->id);
        return back()->with('status', 'İstisna silindi.');
    }

    private function ensureSettings(User $user): SeniorBookingSetting
    {
        $existing = SeniorBookingSetting::query()
            ->where('senior_user_id', $user->id)
            ->first();
        if ($existing) {
            return $existing;
        }
        return SeniorBookingSetting::create([
            'senior_user_id'   => $user->id,
            'slot_duration'    => 30,
            'buffer_minutes'   => 5,
            'min_notice_hours' => 6,
            'max_future_days'  => 90,
            'timezone'         => 'Europe/Berlin',
            'is_public'        => false,
            'display_name'     => trim(($user->name ?? '') . ' — Randevu'),
            'is_active'        => true,
        ]);
    }

    private function generateUniqueSlug(User $user): string
    {
        $base = Str::slug($user->name ?: 'senior');
        if ($base === '') {
            $base = 'senior';
        }
        $slug = $base;
        $i = 1;
        while (SeniorBookingSetting::query()->where('public_slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    private function flushSlotCache(int $seniorUserId): void
    {
        // Hızlı çözüm: ilgili cache key prefix'leri Cache sürücüsü destekliyorsa flush.
        // Laravel default file driver pattern delete desteklemez → key'i günlük olarak çürüt.
        Cache::forget("booking:slots:{$seniorUserId}");
        // NOT: Günlük cache key'leri (..:date) 5 dk TTL ile kendi kendine temizlenir.
    }
}
