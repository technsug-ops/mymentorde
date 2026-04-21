<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\StudentAppointment;
use App\Services\Integrations\IntegrationFactory;
use Illuminate\Http\Request;

class SeniorAppointmentController extends Controller
{
    use SeniorPortalTrait;

    public function appointments(Request $request)
    {
        $email  = $this->seniorEmail($request);
        $prefs  = $this->seniorPortalPreferences($request);
        $q      = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $appointments = StudentAppointment::query()
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

        return view('senior.appointments', [
            'appointments'   => $appointments,
            'portalPrefs'    => $prefs,
            'weeklySchedule' => $this->normalizeWeeklySchedule((array) data_get($prefs, 'profile.weekly_schedule', [])),
            'filters'        => compact('q', 'status'),
            'sidebarStats'   => $this->sidebarStats($request),
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
