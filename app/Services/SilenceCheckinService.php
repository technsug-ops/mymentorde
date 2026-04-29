<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use App\Models\SystemEventLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sessizlik check-in akışı:
 *  - aday/öğrencinin timeline'ında N gün hareket yoksa "süreç aktif" touchpoint düşürür
 *  - kadans hiyerarşi: kişi override > şirket override > config default
 *  - dedup: last_silence_checkin_at, üst üste post atmaz
 *  - touchpoint = in-app notification + system_event_log entry (mail değil)
 */
class SilenceCheckinService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public const STAGE_APPLICATION = 'application'; // aday — yeni/temas/qualified
    public const STAGE_UNI_ASSIST  = 'uni_assist';
    public const STAGE_VISA        = 'visa';
    public const STAGE_GENERAL     = 'general';

    private const STAGE_LABELS = [
        self::STAGE_APPLICATION => 'Başvuru',
        self::STAGE_UNI_ASSIST  => 'Üniversite başvurusu',
        self::STAGE_VISA        => 'Vize süreci',
        self::STAGE_GENERAL     => 'Genel takip',
    ];

    private const NEXT_STEP_HINT = [
        self::STAGE_APPLICATION => 'Danışman görüşmesi planlanacak ve evrak listen netleşecek.',
        self::STAGE_UNI_ASSIST  => 'Üniversite başvurularının statüsünü takip ediyoruz.',
        self::STAGE_VISA        => 'Vize randevu/dosya onay süreci ilerliyor.',
        self::STAGE_GENERAL     => 'Süreç planın devam ediyor.',
    ];

    public static function stageLabel(string $stage): string
    {
        return self::STAGE_LABELS[$stage] ?? ucfirst(str_replace('_', ' ', $stage));
    }

    // ─── Stage tespiti ────────────────────────────────────────────────────────

    public function resolveGuestStage(GuestApplication $g): ?string
    {
        $status = strtolower(trim((string) ($g->lead_status ?? '')));
        if (in_array($status, ['converted', 'lost'], true)) return null;
        if (in_array($status, ['new', 'contacted', 'qualified'], true)) return self::STAGE_APPLICATION;
        // Bilinmeyen/boş → application kabul et (yeni başvuru gibi davran)
        return self::STAGE_APPLICATION;
    }

    public function resolveStudentStage(User $u): ?string
    {
        if ((string) ($u->role ?? '') !== 'student') return null;
        if (! $u->is_active) return null;

        // Vize başvurusu açıksa visa stage
        $hasActiveVisa = StudentVisaApplication::query()
            ->where('student_id', (string) ($u->student_id ?? $u->id))
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
            ->exists();
        if ($hasActiveVisa) return self::STAGE_VISA;

        // Aktif üniversite başvurusu varsa uni_assist
        $hasActiveUni = StudentUniversityApplication::query()
            ->where('student_id', (string) ($u->student_id ?? $u->id))
            ->whereNotIn('status', ['accepted', 'rejected', 'withdrawn', 'cancelled'])
            ->exists();
        if ($hasActiveUni) return self::STAGE_UNI_ASSIST;

        return self::STAGE_GENERAL;
    }

    // ─── Cadence çözünürlüğü ──────────────────────────────────────────────────

    public function effectiveCadenceDays(Model $entity, string $stage): int
    {
        // 1. Kişi bazında override
        $personal = (int) ($entity->silence_checkin_days_override ?? 0);
        if ($personal > 0) return $personal;

        // 2. Şirket bazında override
        $companyId = (int) ($entity->company_id ?? 0);
        if ($companyId > 0) {
            $company = Company::find($companyId);
            $overrides = is_array($company?->silence_checkin_overrides) ? $company->silence_checkin_overrides : [];
            $companyDays = (int) ($overrides[$stage] ?? 0);
            if ($companyDays > 0) return $companyDays;
        }

        // 3. Config default
        $defaults = (array) config('brand.silence_checkin_days', []);
        $configDays = (int) ($defaults[$stage] ?? 0);
        if ($configDays > 0) return $configDays;

        // Son çare
        return 7;
    }

    // ─── Sessizlik tetik ──────────────────────────────────────────────────────

    public function daysSinceLastActivity(Model $entity): int
    {
        $candidates = [
            $entity->updated_at ?? null,
            $entity->last_senior_action_at ?? null,
            $entity->last_silence_checkin_at ?? null,
        ];
        $latest = null;
        foreach ($candidates as $c) {
            if ($c instanceof Carbon) {
                if ($latest === null || $c->gt($latest)) $latest = $c;
            }
        }
        if ($latest === null) return 9999;
        return (int) $latest->diffInDays(now());
    }

    public function shouldPostCheckin(Model $entity, string $stage): array
    {
        if (! empty($entity->silence_checkin_paused_at)) {
            return ['post' => false, 'reason' => 'paused', 'cadence' => null, 'days_silent' => null];
        }
        $cadence = $this->effectiveCadenceDays($entity, $stage);
        $silent  = $this->daysSinceLastActivity($entity);

        // Dedup: aynı periyot içinde 2. defa post atmasın.
        // last_silence_checkin_at'tan bu yana cadence_days geçmediyse skip.
        if (! empty($entity->last_silence_checkin_at)) {
            $lastCheckin = $entity->last_silence_checkin_at instanceof Carbon
                ? $entity->last_silence_checkin_at
                : Carbon::parse($entity->last_silence_checkin_at);
            $sinceLastCheckin = (int) $lastCheckin->diffInDays(now());
            if ($sinceLastCheckin < $cadence) {
                return ['post' => false, 'reason' => 'recent_checkin', 'cadence' => $cadence, 'days_silent' => $silent];
            }
        }

        return [
            'post'        => $silent >= $cadence,
            'reason'      => $silent >= $cadence ? 'eligible' : 'fresh_activity',
            'cadence'     => $cadence,
            'days_silent' => $silent,
        ];
    }

    // ─── Touchpoint post ──────────────────────────────────────────────────────

    /**
     * @param  GuestApplication|User  $entity
     */
    public function postCheckin(Model $entity, string $stage, int $daysSilent, bool $manual = false): void
    {
        $isGuest = $entity instanceof GuestApplication;
        $stageLabel = self::stageLabel($stage);
        $nextStep   = self::NEXT_STEP_HINT[$stage] ?? '';
        $seniorName = $this->resolveSeniorName($entity);

        $body = sprintf(
            '📍 Süreciniz aktif olarak devam ediyor — durum: %s%s. Son işlem: %d gün önce. %s',
            $stageLabel,
            $seniorName ? ', danışman: ' . $seniorName : '',
            $daysSilent,
            $nextStep,
        );

        $studentId = $isGuest
            ? (trim((string) ($entity->converted_student_id ?? '')) !== ''
                ? (string) $entity->converted_student_id
                : 'GST-' . str_pad((string) $entity->id, 8, '0', STR_PAD_LEFT))
            : (string) ($entity->student_id ?? $entity->id);

        $userId = $isGuest
            ? ($entity->guest_user_id ? (int) $entity->guest_user_id : null)
            : (int) $entity->id;

        try {
            $this->notificationService->send([
                'channel'     => 'in_app',
                'category'    => 'process_update',
                'user_id'     => $userId,
                'student_id'  => $studentId,
                'company_id'  => (int) ($entity->company_id ?: 0),
                'subject'     => 'Süreciniz devam ediyor',
                'body'        => $body,
                'source_type' => $isGuest ? 'guest_application' : 'student',
                'source_id'   => (string) $entity->id,
            ]);
        } catch (\Throwable $e) {
            // Notif gönderilemezse de event log yine yazılsın
        }

        SystemEventLog::create([
            'event_type'  => 'silence_checkin_posted',
            'entity_type' => $isGuest ? 'guest_application' : 'student',
            'entity_id'   => (string) $entity->id,
            'company_id'  => (int) ($entity->company_id ?: 0),
            'message'     => $body,
            'meta'        => [
                'stage'        => $stage,
                'cadence_days' => $this->effectiveCadenceDays($entity, $stage),
                'days_silent'  => $daysSilent,
                'manual'       => $manual,
            ],
        ]);

        $entity->forceFill(['last_silence_checkin_at' => now()])->save();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveSeniorName(Model $entity): ?string
    {
        $email = (string) ($entity->assigned_senior_email ?? '');
        if ($email === '') return null;
        return User::where('email', $email)->value('name');
    }
}
