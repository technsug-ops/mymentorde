<?php

namespace App\Http\Controllers\Senior\Concerns;

use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\StudentAccommodation;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Models\StudentVisaApplication;
use App\Models\UserPortalPreference;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SeniorPortalTrait — Senior portal sub-controller'larının paylaştığı ortak metodlar.
 *
 * Bu trait'i kullanan her controller doğrudan:
 *   $this->seniorEmail($request)
 *   $this->assignedStudentIds($request)
 *   $this->sidebarStats($request)
 * gibi çağrıları yapabilir.
 */
trait SeniorPortalTrait
{
    // ── Sabitler (PHP 8.2+) ──────────────────────────────────────────────────

    protected const PORTAL_PREF_KEY = 'senior';

    protected const GUEST_PIPELINE_STAGES = [
        'new'             => 'Yeni',
        'contacted'       => 'İletişime Geçildi',
        'docs_pending'    => 'Evrak Bekliyor',
        'in_progress'     => 'İşlemde',
        'evaluating'      => 'Değerlendiriliyor',
        'contract_signed' => 'Sözleşme İmzalandı',
        'converted'       => 'Dönüştürüldü',
        'lost'            => 'Kaybedildi',
    ];

    protected const PIPELINE_STEPS = [
        'application_prep'  => 'Başvuru Hazırlık',
        'uni_assist'        => 'Uni Assist',
        'visa_application'  => 'Vize Başvurusu',
        'language_course'   => 'Dil Kursu',
        'residence'         => 'İkamet',
        'official_services' => 'Resmi Evraklar',
        'completed'         => 'Tamamlandı',
    ];

    // ── Temel yardımcılar ────────────────────────────────────────────────────

    protected function seniorEmail(Request $request): string
    {
        return strtolower((string) ($request->user()?->email ?? ''));
    }

    protected function assignedStudentIds(Request $request): Collection
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        return StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();
    }

    protected function sidebarStats(Request $request): array
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $userId    = (int) optional($request->user())->id;
        $today     = now()->toDateString();

        $todayTasks = (int) MarketingTask::query()
            ->where('assigned_user_id', $userId)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereDate('due_date', $today)
            ->count();

        $cached = Cache::remember("senior_sidebar_{$email}_{$companyId}", 60, function () use ($email, $companyId, $today): array {
            $base = StudentAssignment::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->whereRaw('lower(senior_email) = ?', [$email]);

            $agg = (clone $base)
                ->selectRaw(
                    'SUM(CASE WHEN is_archived=0 THEN 1 ELSE 0 END) as active_students,' .
                    'GROUP_CONCAT(student_id) as student_ids_csv'
                )
                ->first();

            $activeStudents = (int) ($agg->active_students ?? 0);
            $studentIds = collect(
                $agg?->student_ids_csv ? explode(',', (string) $agg->student_ids_csv) : []
            )->filter()->unique()->values()->all();

            $pendingGuests = empty($studentIds) ? 0 : (int) GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds)
                ->where('converted_to_student', false)
                ->count();

            $todayAppointments = (int) StudentAppointment::query()
                ->whereRaw('lower(senior_email) = ?', [$email])
                ->whereDate('scheduled_at', $today)
                ->count();

            return [
                'active_students'    => $activeStudents,
                'pending_guests'     => $pendingGuests,
                'today_appointments' => $todayAppointments,
            ];
        });

        return array_merge($cached, ['today_tasks' => $todayTasks]);
    }

    protected function calculateStudentProgress(
        string $studentId,
        ?GuestApplication $guest,
        $documents,
        $outcomes,
        $uniApps,
        ?StudentVisaApplication $visa = null,
        ?StudentAccommodation $accommodation = null
    ): array {
        $profileDone  = $guest && !empty($guest->first_name) && !empty($guest->email);
        $docsDone     = $documents->filter(fn ($d) => $d->status === 'approved')->count() >= 3;
        $contractDone = $guest && in_array($guest->contract_status ?? '', ['signed', 'signed_uploaded', 'approved']);
        $uniDone      = $uniApps->where('status', 'submitted')->count() >= 1;
        $outcomeDone  = $outcomes->where('outcome_type', 'admission')->count() >= 1;
        $visaDone     = $visa && $visa->status === 'approved';
        $housingDone  = $accommodation && $accommodation->booking_status === 'confirmed';

        $steps = [
            ['key' => 'profile',   'label' => 'Profil Tamamlandı',    'done' => $profileDone],
            ['key' => 'docs',      'label' => 'Belgeler Onaylandı',   'done' => $docsDone],
            ['key' => 'contract',  'label' => 'Sözleşme İmzalandı',   'done' => $contractDone],
            ['key' => 'uni_apply', 'label' => 'Üniversite Başvurusu', 'done' => $uniDone],
            ['key' => 'outcome',   'label' => 'Kabul Alındı',         'done' => $outcomeDone],
            ['key' => 'visa',      'label' => 'Vize Onaylandı',       'done' => $visaDone],
            ['key' => 'housing',   'label' => 'Konut Hazır',          'done' => $housingDone],
        ];
        $doneCount = collect($steps)->where('done', true)->count();
        return ['steps' => $steps, 'percent' => (int) ($doneCount / count($steps) * 100)];
    }

    // ── Portal tercihleri ────────────────────────────────────────────────────

    protected function seniorPortalPreferences(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        if (!SchemaCache::hasTable('user_portal_preferences')) {
            return [
                'profile' => [
                    'phone' => (string) $request->session()->get('senior_profile.phone', ''),
                    'title' => '', 'bio' => '', 'expertise' => '', 'languages' => '',
                    'working_hours_weekdays' => '', 'working_hours_weekend' => '',
                    'weekly_schedule' => $this->defaultWeeklySchedule(),
                    'appointment_note' => '',
                ],
                'settings' => [
                    'notify_email' => (bool) $request->session()->get('senior_settings.notify_email', true),
                    'notify_ticket' => (bool) $request->session()->get('senior_settings.notify_ticket', true),
                    'notify_appointment' => (bool) $request->session()->get('senior_settings.notify_appointment', true),
                    'notify_dm' => (bool) $request->session()->get('senior_settings.notify_dm', true),
                    'appointment_auto_confirm' => false,
                    'appointment_buffer_minutes' => 15,
                    'appointment_slot_minutes' => 30,
                ],
            ];
        }

        $row = UserPortalPreference::query()
            ->where('user_id', (int) $user->id)
            ->where('portal_key', self::PORTAL_PREF_KEY)
            ->first();

        $prefs = is_array($row?->preferences_json) ? $row->preferences_json : [];

        return array_replace_recursive([
            'profile' => [
                'phone' => '', 'title' => '', 'bio' => '', 'expertise' => '', 'languages' => '',
                'working_hours_weekdays' => '', 'working_hours_weekend' => '',
                'weekly_schedule' => $this->defaultWeeklySchedule(),
                'appointment_note' => '',
            ],
            'settings' => [
                'notify_email' => true, 'notify_ticket' => true, 'notify_appointment' => true,
                'notify_dm' => true, 'appointment_auto_confirm' => false,
                'appointment_buffer_minutes' => 15, 'appointment_slot_minutes' => 30,
            ],
        ], $prefs);
    }

    protected function saveSeniorPortalPreferences(Request $request, array $prefs): void
    {
        $user = $request->user();
        if (!$user) {
            return;
        }

        if (!SchemaCache::hasTable('user_portal_preferences')) {
            $request->session()->put('senior_profile.phone', (string) data_get($prefs, 'profile.phone', ''));
            $request->session()->put('senior_settings', (array) data_get($prefs, 'settings', []));
            return;
        }

        UserPortalPreference::query()->updateOrCreate(
            ['user_id' => (int) $user->id, 'portal_key' => self::PORTAL_PREF_KEY],
            ['preferences_json' => $prefs]
        );
    }

    protected function defaultWeeklySchedule(): array
    {
        return [
            'monday'    => ['enabled' => true,  'start' => '09:00', 'end' => '18:00', 'note' => ''],
            'tuesday'   => ['enabled' => true,  'start' => '09:00', 'end' => '18:00', 'note' => ''],
            'wednesday' => ['enabled' => true,  'start' => '09:00', 'end' => '18:00', 'note' => ''],
            'thursday'  => ['enabled' => true,  'start' => '09:00', 'end' => '18:00', 'note' => ''],
            'friday'    => ['enabled' => true,  'start' => '09:00', 'end' => '18:00', 'note' => ''],
            'saturday'  => ['enabled' => false, 'start' => '10:00', 'end' => '14:00', 'note' => ''],
            'sunday'    => ['enabled' => false, 'start' => '10:00', 'end' => '14:00', 'note' => ''],
        ];
    }

    protected function normalizeWeeklySchedule(array $input): array
    {
        $defaults   = $this->defaultWeeklySchedule();
        $normalized = [];

        foreach ($defaults as $day => $def) {
            $src     = (array) ($input[$day] ?? []);
            $enabled = in_array(($src['enabled'] ?? $def['enabled']), [true, 1, '1', 'on', 'yes'], true);
            $start   = (string) ($src['start'] ?? $def['start']);
            $end     = (string) ($src['end'] ?? $def['end']);
            $note    = trim((string) ($src['note'] ?? ''));

            $normalized[$day] = [
                'enabled' => $enabled,
                'start'   => preg_match('/^\d{2}:\d{2}$/', $start) ? $start : (string) $def['start'],
                'end'     => preg_match('/^\d{2}:\d{2}$/', $end)   ? $end   : (string) $def['end'],
                'note'    => mb_substr($note, 0, 80),
            ];
        }

        return $normalized;
    }

    protected function scheduleSummary(array $schedule, array $days): string
    {
        $parts = [];
        foreach ($days as $day) {
            $row = (array) ($schedule[$day] ?? []);
            if (!(bool) ($row['enabled'] ?? false)) {
                continue;
            }
            $start = (string) ($row['start'] ?? '');
            $end   = (string) ($row['end'] ?? '');
            if ($start !== '' && $end !== '') {
                $parts[] = $this->dayShortLabel($day) . ' ' . $start . '-' . $end;
            }
        }
        return implode(' | ', $parts);
    }

    protected function dayShortLabel(string $day): string
    {
        return [
            'monday' => 'Pzt', 'tuesday' => 'Sal', 'wednesday' => 'Car',
            'thursday' => 'Per', 'friday' => 'Cum', 'saturday' => 'Cmt', 'sunday' => 'Paz',
        ][$day] ?? $day;
    }
}
