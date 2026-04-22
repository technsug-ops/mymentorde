<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\SeniorAvailabilityException;
use App\Models\SeniorAvailabilityPattern;
use App\Models\SeniorBookingSetting;
use App\Models\StudentAppointment;
use App\Services\Integrations\IntegrationFactory;
use Illuminate\Http\Request;

class SeniorAppointmentController extends Controller
{
    use SeniorPortalTrait;

    public function appointments(Request $request)
    {
        $user   = $request->user();
        $email  = $this->seniorEmail($request);
        $prefs  = $this->seniorPortalPreferences($request);
        $q      = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        // Tab seçimi: appointments (default) / availability / settings
        $tab = strtolower(trim((string) $request->query('tab', 'appointments')));
        if (!in_array($tab, ['appointments', 'availability', 'settings'], true)) {
            $tab = 'appointments';
        }

        $appointments = StudentAppointment::query()
            ->with('publicBooking')
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('student_id', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%")
                        ->orWhere('channel', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($w) => $w->where('status', $status))
            ->latest('scheduled_at')
            ->limit(200)
            ->get();

        // Booking ayarları + müsaitlik verisi — availability/settings tab'ları için.
        // Module toggle kapalıysa null dönecek ve blade'de availability/settings tab'ları
        // gösterilmeyecek.
        $bookingModuleEnabled = \App\Support\ModuleAccess::enabled('booking');
        $bookingSettings = null;
        $availabilityPatterns = collect();
        $availabilityExceptions = collect();
        $calendarGrid = [];
        $calendarMonth = null;
        $calendarPrevUrl = null;
        $calendarNextUrl = null;
        $calendarTitle = '';
        if ($bookingModuleEnabled && $user) {
            $bookingSettings = $this->ensureBookingSettings($user);
            $availabilityPatterns = SeniorAvailabilityPattern::query()
                ->where('senior_user_id', $user->id)
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();
            // Tüm exception'ları çek (geçmiş dahil) — takvim görünürlüğü için
            $allExceptions = SeniorAvailabilityException::query()
                ->where('senior_user_id', $user->id)
                ->get()
                ->keyBy(fn ($e) => \Carbon\Carbon::parse($e->date)->toDateString());
            $availabilityExceptions = $allExceptions
                ->filter(fn ($e) => \Carbon\Carbon::parse($e->date)->toDateString() >= now()->toDateString())
                ->sortBy('date')
                ->values();

            // Takvim ayı: ?cal=YYYY-MM query param, varsayılan bu ay
            $calParam = (string) $request->query('cal', '');
            try {
                $calendarMonth = \Carbon\CarbonImmutable::createFromFormat('Y-m', $calParam ?: now()->format('Y-m'))->startOfMonth();
            } catch (\Throwable) {
                $calendarMonth = \Carbon\CarbonImmutable::now()->startOfMonth();
            }

            $calendarTitle = $calendarMonth->translatedFormat('F Y');
            $tabQuery = ['tab' => 'availability'];
            $calendarPrevUrl = route('senior.appointments', $tabQuery + ['cal' => $calendarMonth->subMonth()->format('Y-m')]);
            $calendarNextUrl = route('senior.appointments', $tabQuery + ['cal' => $calendarMonth->addMonth()->format('Y-m')]);

            // Grid: ayın ilk gününün haftasından başla, son gününün haftasına kadar (6 hafta)
            $gridStart = $calendarMonth->startOfWeek(\Carbon\CarbonInterface::MONDAY);
            $gridEnd   = $calendarMonth->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);

            // Patterns → weekday set
            $patternWeekdays = $availabilityPatterns
                ->where('is_active', true)
                ->pluck('weekday')
                ->map(fn ($w) => (int) $w)
                ->unique()
                ->values()
                ->all();

            // Her günün randevu sayısı (student_appointments, senior_email ile)
            $dayAppointmentCounts = StudentAppointment::query()
                ->whereRaw('lower(senior_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->whereBetween('scheduled_at', [$gridStart->startOfDay(), $gridEnd->endOfDay()])
                ->get(['scheduled_at'])
                ->groupBy(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at)->toDateString())
                ->map(fn ($rows) => $rows->count())
                ->all();

            for ($d = $gridStart; $d->lessThanOrEqualTo($gridEnd); $d = $d->addDay()) {
                $dateStr = $d->toDateString();
                $weekday = ((int) $d->dayOfWeekIso) - 1; // 1..7 → 0..6
                $exception = $allExceptions->get($dateStr);
                $calendarGrid[] = [
                    'date'            => $dateStr,
                    'day'             => $d->day,
                    'weekday'         => $weekday,
                    'is_current_month'=> $d->month === $calendarMonth->month,
                    'is_today'        => $d->isSameDay(\Carbon\CarbonImmutable::now()),
                    'is_past'         => $d->lt(\Carbon\CarbonImmutable::now()->startOfDay()),
                    'has_pattern'     => in_array($weekday, $patternWeekdays, true),
                    'exception'       => $exception,  // null | SeniorAvailabilityException
                    'appointment_count' => (int) ($dayAppointmentCounts[$dateStr] ?? 0),
                ];
            }
        }

        return view('senior.appointments', [
            'appointments'           => $appointments,
            'portalPrefs'            => $prefs,
            'weeklySchedule'         => $this->normalizeWeeklySchedule((array) data_get($prefs, 'profile.weekly_schedule', [])),
            'filters'                => compact('q', 'status'),
            'sidebarStats'           => $this->sidebarStats($request),
            'activeTab'              => $tab,
            'bookingModuleEnabled'   => $bookingModuleEnabled,
            'bookingSettings'        => $bookingSettings,
            'availabilityPatterns'   => $availabilityPatterns,
            'availabilityExceptions' => $availabilityExceptions,
            'weekdayLabels'          => SeniorAvailabilityPattern::WEEKDAY_LABELS_TR,
            'supportedTimezones'     => [
                'Europe/Berlin', 'Europe/Istanbul', 'Europe/London', 'Europe/Paris',
                'Europe/Madrid', 'Europe/Amsterdam', 'Europe/Zurich', 'Europe/Vienna',
                'America/New_York', 'America/Los_Angeles', 'Asia/Dubai', 'Asia/Tokyo',
            ],
            'bookingPublicUrl'       => ($bookingSettings && $bookingSettings->is_public && $bookingSettings->public_slug)
                ? route('booking.public.show', ['slug' => $bookingSettings->public_slug])
                : null,
            'calendarGrid'           => $calendarGrid,
            'calendarTitle'          => $calendarTitle,
            'calendarPrevUrl'        => $calendarPrevUrl,
            'calendarNextUrl'        => $calendarNextUrl,
        ]);
    }

    /**
     * Senior'ın booking ayarları yoksa varsayılanlarla oluştur (tab açılışında idempotent).
     */
    private function ensureBookingSettings(\App\Models\User $user): SeniorBookingSetting
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

    public function appointmentConfirm(Request $request, StudentAppointment $appointment): \Illuminate\Http\RedirectResponse
    {
        $seniorEmail = $this->seniorEmail($request);

        if (strtolower((string) $appointment->senior_email) !== $seniorEmail) {
            abort(403);
        }

        $data = $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:180'],
            'meeting_url'      => ['nullable', 'url', 'max:400'],
        ]);

        $appointment->update(array_merge($data, ['status' => 'confirmed']));

        try {
            $factory     = app(IntegrationFactory::class);
            $adapter     = $factory->getCalendarService();
            $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at']);
            $duration    = (int) ($data['duration_minutes'] ?? $appointment->duration_minutes ?? 60);
            $endAt       = $scheduledAt->copy()->addMinutes($duration);
            $studentEmail = (string) ($appointment->student_email ?? '');
            $attendees    = $studentEmail !== '' ? [$studentEmail] : [];

            $eventId = $adapter->createEvent([
                'title'       => (string) $appointment->title,
                'description' => (string) ($appointment->note ?? ''),
                'start_time'  => $scheduledAt->toRfc3339String(),
                'end_time'    => $endAt->toRfc3339String(),
                'attendees'   => $attendees,
                'add_meet'    => $appointment->channel === 'online',
            ]);

            if ($eventId !== '') {
                $cfg      = \App\Models\IntegrationConfig::where('category', 'calendar')->first();
                $provider = (string) ($cfg?->active_provider ?? 'calendar');
                $appointment->update([
                    'external_event_id' => $eventId,
                    'calendar_provider' => $provider,
                ]);
            }
        } catch (\Throwable) {
            // Entegrasyon yoksa randevu yine de onaylanır
        }

        return back()->with('status', 'Randevu onaylandı' . ($appointment->external_event_id ? ' ve takvime eklendi.' : '.'));
    }

    /**
     * AJAX: Verilen tarih/süre ile senior'un başka bir randevusu çakışıyor mu?
     * Booking modülü public_bookings + mevcut student_appointments ikisini de kontrol eder.
     */
    public function checkCollision(Request $request): \Illuminate\Http\JsonResponse
    {
        $email = $this->seniorEmail($request);
        $data  = $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'except_id'        => ['nullable', 'integer'],
        ]);

        $duration  = (int) ($data['duration_minutes'] ?? 30);
        $start     = \Carbon\CarbonImmutable::parse($data['scheduled_at']);
        $end       = $start->copy()->addMinutes($duration);
        $exceptId  = (int) ($data['except_id'] ?? 0);

        $conflicts = [];

        // 1. Mevcut student_appointments — senior'un aynı aralıkta başka aktif randevusu
        $rows = StudentAppointment::query()
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->whereNotIn('status', ['cancelled', 'canceled', 'done', 'completed'])
            ->when($exceptId > 0, fn ($w) => $w->where('id', '!=', $exceptId))
            ->where('scheduled_at', '<', $end->toDateTimeString())
            ->where('scheduled_at', '>=', $start->copy()->subHours(4)->toDateTimeString())
            ->limit(10)
            ->get(['id', 'title', 'scheduled_at', 'duration_minutes', 'status', 'student_id']);

        foreach ($rows as $r) {
            $rStart = \Carbon\CarbonImmutable::parse($r->scheduled_at);
            $rEnd   = $rStart->copy()->addMinutes((int) ($r->duration_minutes ?? 30));
            // Overlap check: start < rEnd AND end > rStart
            if ($start->lessThan($rEnd) && $end->greaterThan($rStart)) {
                $conflicts[] = [
                    'id'           => $r->id,
                    'title'        => $r->title,
                    'scheduled_at' => $rStart->format('d.m.Y H:i'),
                    'duration'     => (int) ($r->duration_minutes ?? 30),
                    'student_id'   => $r->student_id,
                ];
            }
        }

        return response()->json([
            'ok'        => true,
            'collision' => !empty($conflicts),
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Onaylanmış randevuyu düzenle (title, scheduled_at, süre, kanal, not).
     * Observer'ın saved hook'u Google Calendar'a otomatik sync yapar.
     */
    public function appointmentUpdate(Request $request, StudentAppointment $appointment): \Illuminate\Http\RedirectResponse
    {
        $seniorEmail = $this->seniorEmail($request);
        if (strtolower((string) $appointment->senior_email) !== $seniorEmail) {
            abort(403);
        }

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:190'],
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:180'],
            'channel'          => ['nullable', 'string', 'in:online,phone,in_person,office'],
            'note'             => ['nullable', 'string', 'max:1000'],
            'meeting_url'      => ['nullable', 'url', 'max:400'],
        ]);

        $appointment->update($data);

        return back()->with('status', 'Randevu güncellendi ve takvim senkronize edildi.');
    }

    /**
     * Randevuyu iptal et (status = cancelled).
     * Observer'ın saved hook'u Google Calendar'dan event'i siler.
     */
    public function appointmentCancel(Request $request, StudentAppointment $appointment): \Illuminate\Http\RedirectResponse
    {
        $seniorEmail = $this->seniorEmail($request);
        if (strtolower((string) $appointment->senior_email) !== $seniorEmail) {
            abort(403);
        }

        $validCategories = [
            'student_no_show',
            'student_request',
            'reschedule',
            'senior_unavailable',
            'duplicate',
            'not_needed',
            'technical',
            'other',
        ];

        $data = $request->validate([
            'cancel_category' => ['required', 'string', 'in:' . implode(',', $validCategories)],
            'cancel_reason'   => ['nullable', 'string', 'max:500'],
        ]);

        // 'Diğer' seçildiyse açıklama zorunlu
        if ($data['cancel_category'] === 'other' && empty(trim($data['cancel_reason'] ?? ''))) {
            return back()
                ->withErrors(['cancel_reason' => 'Diğer seçildiğinde açıklama zorunludur.'])
                ->withInput();
        }

        $appointment->update([
            'status'          => 'cancelled',
            'cancelled_at'    => now(),
            'cancel_category' => $data['cancel_category'],
            'cancel_reason'   => $data['cancel_reason'] ?: null,
        ]);

        return back()->with('status', 'Randevu iptal edildi' . ($appointment->google_event_id ? ' ve takvimden kaldırıldı.' : '.'));
    }
}
