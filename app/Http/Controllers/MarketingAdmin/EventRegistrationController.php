<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EventRegistration;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class EventRegistrationController extends Controller
{
    public function index(Request $request, string $id)
    {
        $event = MarketingEvent::query()->findOrFail($id);
        $filters = [
            'status' => (string) $request->query('status', 'all'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $query = EventRegistration::query()
            ->where('event_id', $event->id)
            ->orderByDesc('registered_at');

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('email', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('mentorde_id', 'like', "%{$q}%");
            });
        }

        $rows = $query->paginate(25)->withQueryString();

        return view('marketing-admin.events.registrations', [
            'pageTitle' => 'Etkinlik Kayitlari',
            'title' => 'Etkinlik #'.$id.' katilimcilari',
            'event' => $event,
            'rows' => $rows,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, string $id, string $regId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
            'survey_completed' => ['nullable'],
            'survey_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'survey_feedback' => ['nullable', 'string'],
        ]);

        $event = MarketingEvent::query()->findOrFail($id);
        $row = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('id', $regId)
            ->firstOrFail();

        $status = (string) $data['status'];
        $payload = [
            'status' => $status,
        ];
        if ($status === 'attended') {
            $payload['attended_at'] = now();
            $payload['cancelled_at'] = null;
            $payload['cancellation_reason'] = null;
        } elseif ($status === 'cancelled') {
            $payload['cancelled_at'] = now();
            $payload['cancellation_reason'] = $data['cancellation_reason'] ?? 'cancelled by admin';
        } else {
            if ($status !== 'cancelled') {
                $payload['cancelled_at'] = null;
                $payload['cancellation_reason'] = null;
            }
            if ($status !== 'attended') {
                $payload['attended_at'] = null;
            }
        }

        if ($request->has('survey_completed')) {
            $payload['survey_completed'] = $request->boolean('survey_completed');
        }
        if ($request->has('survey_score')) {
            $payload['survey_score'] = $data['survey_score'];
        }
        if ($request->has('survey_feedback')) {
            $payload['survey_feedback'] = $data['survey_feedback'];
        }

        $row->update($payload);
        $this->refreshEventMetrics($event->fresh());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'event_id' => $id,
                'registration_id' => $regId,
                'status' => $status,
            ], Response::HTTP_OK);
        }

        return redirect('/mktg-admin/events/'.$id.'/registrations')->with('status', 'Kayit durumu guncellendi.');
    }

    private function statusOptions(): array
    {
        return ['registered', 'confirmed', 'waitlist', 'attended', 'no_show', 'cancelled'];
    }

    private function refreshEventMetrics(MarketingEvent $event): void
    {
        $total = (int) EventRegistration::query()->where('event_id', $event->id)->count();
        $attended = (int) EventRegistration::query()->where('event_id', $event->id)->where('status', 'attended')->count();
        $surveyScore = (float) (EventRegistration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('survey_score')
            ->avg('survey_score') ?: 0);
        $rate = $total > 0 ? round(($attended / $total) * 100, 2) : 0.0;

        $event->forceFill([
            'current_registrations' => $total,
            'metric_total_registrations' => $total,
            'metric_total_attendees' => $attended,
            'metric_attendance_rate' => $rate,
            'metric_satisfaction_score' => $surveyScore > 0 ? $surveyScore : null,
        ])->save();
    }
}
