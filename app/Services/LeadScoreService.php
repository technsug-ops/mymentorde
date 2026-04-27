<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\InternalNote;
use App\Models\LeadScoreLog;
use App\Models\LeadScoringRule;
use App\Models\MarketingTask;
use App\Models\ProcessOutcome;
use App\Models\StudentAppointment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Birleştirilmiş Lead Score servisi (v4.1).
 *
 * **CORE servisi** — `marketing_admin` modülü kapalı şirketlerde dahi çalışır.
 * Guest pipeline (form submit → senior assignment → conversion) bu servise
 * bağımlıdır; modül gate'lenmez. Marketing Admin paneli yalnızca scoring
 * KURALLARINI yönetir; skoring HESABI Core'da yaşar.
 *
 * - recalculate() / recalculateForStudent() — senior aksiyon bazlı sabit faktörler
 * - addScore() / applyDecay() / getScoreBreakdown() — kural bazlı artımlı puanlama
 * - onTierChanged() — tier değişiminde otomatik görev + bildirim
 *
 * Tier eşikleri (LeadScoringService, SalesPipelineController::scoreAnalysis ile senkronize):
 *   champion:    100+
 *   sales_ready: 80-99
 *   hot:         50-79
 *   warm:        20-49
 *   cold:        0-19
 */
class LeadScoreService
{
    // Tier eşikleri config/lead_scoring.php'den okunur (fallback: hardcode)
    private function tiers(): array
    {
        return config('lead_scoring.tiers', [
            'champion' => 100, 'sales_ready' => 80, 'hot' => 50, 'warm' => 20, 'cold' => 0,
        ]);
    }

    private function tierLabels(): array
    {
        return config('lead_scoring.tier_labels', [
            'cold' => 'Cold', 'warm' => 'Warm', 'hot' => 'Hot',
            'sales_ready' => 'Sales Ready', 'champion' => 'Champion',
        ]);
    }

    private function factor(string $key): int
    {
        return (int) config("lead_scoring.factors.{$key}.points", 0);
    }

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ── Senior Aksiyon Bazlı Yeniden Hesaplama ───────────────────────────────

    /**
     * Converted student ID'si üzerinden GuestApplication bul ve score'u güncelle.
     */
    public function recalculateForStudent(string $studentId): void
    {
        $app = GuestApplication::where('converted_student_id', $studentId)->first();
        if ($app) {
            $this->recalculate($app);
        }
    }

    /**
     * GuestApplication kaydı için score'u hesapla ve güncelle.
     */
    public function recalculate(GuestApplication $app): void
    {
        $score     = 0;
        $studentId = $app->converted_student_id;

        // ── Form & Temel Adımlar ────────────────────────────────────────────
        if ($app->registration_form_submitted_at) {
            $score += $this->factor('registration_form_submitted');
        }

        if ($app->assigned_senior_email) {
            $score += $this->factor('senior_assigned');
        }

        // UTM veya dealer kanalından geldi
        if ($app->utm_source || $app->dealer_code || $app->tracking_link_code) {
            $score += $this->factor('utm_or_dealer_source');
        }

        // ── Sözleşme Durumu ─────────────────────────────────────────────────
        $contractRequested = config('lead_scoring.contract_status_requested', ['requested', 'pending_manager']);
        $contractSigned    = config('lead_scoring.contract_status_signed', ['signed_uploaded', 'approved']);
        if (in_array($app->contract_status, $contractRequested, true)) {
            $score += $this->factor('contract_requested');
        } elseif (in_array($app->contract_status, $contractSigned, true)) {
            $score += $this->factor('contract_signed');
        }

        // ── Risk Cezası ─────────────────────────────────────────────────────
        $riskLevels = config('lead_scoring.risk_penalty_levels', ['high', 'critical']);
        if (in_array($app->risk_level, $riskLevels, true)) {
            $score += $this->factor('high_risk_penalty'); // negatif değer
        }

        // ── Senior Aksiyonları (yalnızca convert edilmiş öğrenciler için) ──
        if ($studentId) {
            // ProcessOutcome kararları
            $outcomes = ProcessOutcome::where('student_id', $studentId)->get(['outcome_type']);
            foreach ($outcomes as $outcome) {
                if ($outcome->outcome_type === 'acceptance') {
                    $score += $this->factor('outcome_acceptance');
                } elseif ($outcome->outcome_type === 'rejection') {
                    $score += $this->factor('outcome_rejection'); // negatif değer
                } elseif ($outcome->outcome_type === 'conditional_acceptance') {
                    $score += $this->factor('outcome_conditional');
                }
            }

            // Üniversite başvurusu sonucu
            if (StudentUniversityApplication::where('student_id', $studentId)
                ->whereIn('status', ['accepted', 'conditional_accepted'])->exists()) {
                $score += $this->factor('university_accepted');
            }

            // Vize onayı belgesi (VIS-ERTEIL — Visum erteilt)
            if (StudentInstitutionDocument::where('student_id', $studentId)
                ->where('document_type_code', 'VIS-ERTEIL')
                ->whereNotIn('status', ['expected', 'archived'])->exists()) {
                $score += $this->factor('visa_approved');
            }

            // Son 14 gün senior aktivitesi (not)
            $noteDays = (int) config('lead_scoring.factors.recent_note.days', 14);
            if (InternalNote::where('student_id', $studentId)
                ->where('created_at', '>=', now()->subDays($noteDays))->exists()) {
                $score += $this->factor('recent_note');
            }

            // Son 30 gün randevu
            $apptDays = (int) config('lead_scoring.factors.recent_appointment.days', 30);
            if (StudentAppointment::where('student_id', $studentId)
                ->where('created_at', '>=', now()->subDays($apptDays))->exists()) {
                $score += $this->factor('recent_appointment');
            }
        }

        $oldTier = $app->lead_score_tier ?? 'cold';
        $score   = max(0, min(100, $score));
        $newTier = $this->resolveTier($score);

        $app->withoutTimestamps(function () use ($app, $score, $newTier): void {
            $app->update([
                'lead_score'            => $score,
                'lead_score_tier'       => $newTier,
                'last_senior_action_at' => now(),
            ]);
        });

        if ($oldTier !== $newTier) {
            $this->onTierChanged($app, $oldTier, $newTier);
        }
    }

    /**
     * Tüm GuestApplication kayıtlarını toplu yeniden hesapla (artisan komutu).
     */
    public function recalculateAll(?int $limit = null): int
    {
        $query = GuestApplication::query()->where('is_archived', false);
        if ($limit) {
            $query->limit($limit);
        }

        $count = 0;
        foreach ($query->cursor() as $app) {
            $this->recalculate($app);
            $count++;
        }

        return $count;
    }

    // ── Kural Bazlı Artımlı Puanlama ────────────────────────────────────────

    /**
     * Belirli bir action_code için lead_scoring_rules tablosundan kural okuyup puan ekle.
     * One-time ve daily-max kontrolleri yapılır.
     */
    public function addScore(int|string $guestId, string $actionCode, array $metadata = []): bool
    {
        $rule = LeadScoringRule::where('action_code', $actionCode)->where('is_active', true)->first();
        if (! $rule) {
            return false;
        }

        $guest = GuestApplication::find($guestId);
        if (! $guest) {
            return false;
        }

        // One-time kontrol
        if ($rule->is_one_time) {
            if (LeadScoreLog::where('guest_application_id', $guestId)
                ->where('action_code', $actionCode)->exists()) {
                return false;
            }
        }

        // Günlük maksimum kontrol
        if ($rule->max_per_day) {
            $todayCount = LeadScoreLog::where('guest_application_id', $guestId)
                ->where('action_code', $actionCode)
                ->whereDate('created_at', today())
                ->count();
            if ($todayCount >= $rule->max_per_day) {
                return false;
            }
        }

        $scoreBefore = (int) $guest->lead_score;
        $tierBefore  = $guest->lead_score_tier ?? 'cold';
        $newScore    = max(0, $scoreBefore + $rule->points);
        $newTier     = $this->resolveTier($newScore);

        DB::transaction(function () use ($guest, $actionCode, $rule, $scoreBefore, $newScore, $newTier, $tierBefore, $metadata): void {
            $guest->forceFill([
                'lead_score'            => $newScore,
                'lead_score_tier'       => $newTier,
                'lead_score_updated_at' => now(),
            ])->save();

            LeadScoreLog::create([
                'guest_application_id' => $guest->id,
                'action_code'          => $actionCode,
                'points'               => $rule->points,
                'score_before'         => $scoreBefore,
                'score_after'          => $newScore,
                'tier_before'          => $tierBefore,
                'tier_after'           => $newTier,
                'metadata'             => $metadata ?: null,
                'created_at'           => now(),
            ]);
        });

        if ($tierBefore !== $newTier) {
            $this->onTierChanged($guest, $tierBefore, $newTier);
        }

        return true;
    }

    /**
     * İnaktivite bazlı skor düşümü (decay).
     * 7+ gün: decay_7_14d, 15+ gün: decay_15_30d, 30+ gün: decay_30plus
     */
    public function applyDecay(): int
    {
        $processed = 0;
        $now       = now();

        GuestApplication::query()
            ->whereNotIn('contract_status', ['approved', 'cancelled'])
            ->where('lead_score', '>', 0)
            ->chunk(200, function ($guests) use ($now, &$processed): void {
                foreach ($guests as $guest) {
                    $lastActivity = LeadScoreLog::where('guest_application_id', $guest->id)
                        ->where('action_code', 'NOT LIKE', 'decay_%')
                        ->latest('created_at')
                        ->value('created_at');

                    if (! $lastActivity) {
                        continue;
                    }

                    $daysSince = (int) $now->diffInDays($lastActivity);
                    if ($daysSince < 7) {
                        continue;
                    }

                    $actionCode = match (true) {
                        $daysSince >= 30 => 'decay_30plus',
                        $daysSince >= 15 => 'decay_15_30d',
                        default          => 'decay_7_14d',
                    };

                    $this->addScore($guest->id, $actionCode, ['days_since_activity' => $daysSince]);
                    $processed++;
                }
            });

        return $processed;
    }

    /**
     * Guest'in behavioral/demographic/decay bazlı puan dağılımını döner.
     */
    public function getScoreBreakdown(int|string $guestId): array
    {
        $logs  = LeadScoreLog::where('guest_application_id', $guestId)->orderBy('created_at')->get();
        $rules = LeadScoringRule::pluck('category', 'action_code');

        $breakdown = ['behavioral' => 0, 'demographic' => 0, 'decay' => 0];
        foreach ($logs as $log) {
            $cat = $rules[$log->action_code] ?? 'behavioral';
            $breakdown[$cat] += $log->points;
        }

        return $breakdown;
    }

    public function getTierLabel(string $tier): string
    {
        return $this->tierLabels()[$tier] ?? $tier;
    }

    // ── Private Yardımcılar ──────────────────────────────────────────────────

    private function resolveTier(int $score): string
    {
        foreach ($this->tiers() as $tier => $threshold) {
            if ($score >= $threshold) {
                return $tier;
            }
        }
        return 'cold';
    }

    /**
     * Tier değişiminde: otomatik görev oluştur + sales_ready/champion için manager bildirimi.
     */
    private function onTierChanged(GuestApplication $guest, string $from, string $to): void
    {
        $taskTitle = match ($to) {
            'hot'         => "Hızlı iletişim kur — {$guest->first_name} {$guest->last_name}",
            'sales_ready' => "Öncelikli görüşme planla — {$guest->first_name} {$guest->last_name}",
            'champion'    => "VIP aday — {$guest->first_name} {$guest->last_name}",
            default       => null,
        };

        if ($taskTitle) {
            $priority = in_array($to, ['sales_ready', 'champion'], true) ? 'urgent' : 'high';
            MarketingTask::create([
                'title'       => $taskTitle,
                'department'  => 'marketing',
                'priority'    => $priority,
                'status'      => 'todo',
                'source_type' => 'lead_scoring_tier_change',
                'source_id'   => (string) $guest->id,
                'company_id'  => $guest->company_id,
            ]);
        }

        // sales_ready+ → manager bildirimi
        if (in_array($to, ['sales_ready', 'champion'], true)) {
            $companyId = (int) ($guest->company_id ?? 0);
            $managers  = User::query()
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->pluck('id');

            foreach ($managers as $managerId) {
                $this->notificationService->send([
                    'channel'      => 'in_app',
                    'category'     => 'lead_score_tier_change',
                    'user_id'      => (int) $managerId,
                    'company_id'   => $companyId ?: null,
                    'subject'      => "Lead Puanı Yüksek: {$guest->first_name} {$guest->last_name}",
                    'body'         => "Tier: {$this->getTierLabel($to)} (Skor: {$guest->lead_score})",
                    'source_type'  => 'lead_scoring_tier_change',
                    'source_id'    => (string) $guest->id,
                    'triggered_by' => 'system',
                ]);
            }
        }
    }
}
