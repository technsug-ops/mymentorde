<?php

namespace App\Services\Analytics;

use App\Models\AuditTrail;
use App\Models\DmMessage;
use App\Models\Document;
use App\Models\GuestAiConversation;
use App\Models\GuestApplication;
use App\Models\StudentAppointment;
use App\Models\StudentPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Kullanıcı aktivite istihbaratı — guest (aday) + student platformda ne yapıyor?
 *
 * Veri kaynakları:
 *   - users.last_activity_at + presence_status (UpdateUserPresence middleware)
 *   - sessions.last_activity (logged-in user'lar için)
 *   - lead_source_data.funnel_*_at (guest funnel timestamps)
 *   - audit_trails (her aksiyon)
 *   - guest_ai_conversations + senior/staff
 *   - student_appointments, documents, dm_messages
 *
 * Engagement tier'ları (hem guest hem student için):
 *   - active:   son 7 günde aktivite
 *   - at_risk:  7-30 gün
 *   - dormant:  30+ gün veya hiç
 */
class UserActivityService
{
    public const TIER_ACTIVE   = 'active';
    public const TIER_AT_RISK  = 'at_risk';
    public const TIER_DORMANT  = 'dormant';

    /**
     * Genel KPI — dashboard top stats.
     */
    public function overviewStats(int $companyId): array
    {
        $now7  = now()->subDays(7);
        $now30 = now()->subDays(30);

        // Öğrenciler (logged-in)
        $studentsActive7 = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->where('last_activity_at', '>=', $now7)
            ->count();
        $studentsActive30 = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->where('last_activity_at', '>=', $now30)
            ->count();
        $studentsTotal = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->count();

        // Guest'ler (login yok — funnel + AI + senior action üzerinden)
        $guestsActive7  = $this->guestsActiveSince($companyId, $now7);
        $guestsActive30 = $this->guestsActiveSince($companyId, $now30);
        $guestsTotal    = GuestApplication::where('company_id', $companyId)->count();

        return [
            'students' => [
                'total'     => $studentsTotal,
                'active_7'  => $studentsActive7,
                'active_30' => $studentsActive30,
                'dormant'   => max(0, $studentsTotal - $studentsActive30),
                'active_pct'=> $studentsTotal > 0 ? round(100 * $studentsActive7 / $studentsTotal, 1) : 0,
            ],
            'guests' => [
                'total'     => $guestsTotal,
                'active_7'  => $guestsActive7,
                'active_30' => $guestsActive30,
                'dormant'   => max(0, $guestsTotal - $guestsActive30),
                'active_pct'=> $guestsTotal > 0 ? round(100 * $guestsActive7 / $guestsTotal, 1) : 0,
            ],
        ];
    }

    /**
     * Engagement tier dağılımı — pasta grafik için.
     */
    public function engagementTiers(int $companyId): array
    {
        $now7  = now()->subDays(7);
        $now30 = now()->subDays(30);

        // Öğrenciler
        $students = [
            'active'  => User::where('company_id', $companyId)->where('role', 'student')->where('last_activity_at', '>=', $now7)->count(),
            'at_risk' => User::where('company_id', $companyId)->where('role', 'student')->whereBetween('last_activity_at', [$now30, $now7])->count(),
            'dormant' => User::where('company_id', $companyId)->where('role', 'student')->where(function ($q) use ($now30) {
                $q->where('last_activity_at', '<', $now30)->orWhereNull('last_activity_at');
            })->count(),
        ];

        // Guest'ler — özel logic (her tier için ayrı, converted dahil)
        $allGuests = GuestApplication::where('company_id', $companyId)
            ->get(['id', 'last_senior_action_at']);
        $guests = ['active' => 0, 'at_risk' => 0, 'dormant' => 0];

        foreach ($allGuests as $g) {
            $tier = $this->guestEngagementTier($g->id, $g->last_senior_action_at);
            $guests[$tier] = ($guests[$tier] ?? 0) + 1;
        }

        return [
            'students' => $students,
            'guests'   => $guests,
        ];
    }

    /**
     * Top aktif kullanıcılar — son N günde en çok etkileşim yapan.
     * Hem student hem guest dahil, istenen sort ile.
     *
     * @param string $sort 'activity' | 'lead_score' | 'last_activity' | 'name' | 'questions'
     */
    public function topActiveUsers(int $companyId, int $daysBack = 30, int $limit = 20, string $sort = 'activity'): array
    {
        $since = now()->subDays($daysBack);

        $studentRows = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->where('last_activity_at', '>=', $since)
            ->orderByDesc('last_activity_at')
            ->limit($limit * 3)
            ->get(['id', 'name', 'email', 'presence_status', 'last_activity_at', 'created_at'])
            ->map(function ($u) use ($since) {
                return [
                    'type'             => 'student',
                    'id'               => $u->id,
                    'name'             => $u->name,
                    'email'            => $u->email,
                    'presence'         => $u->presence_status,
                    'tier'             => null,
                    'lead_score'       => null,
                    'last_activity_at' => $u->last_activity_at,
                    'questions'        => 0,
                    'activity_score'   => $this->userActivityScore($u->id, $since),
                ];
            });

        $guestRows = GuestApplication::where('company_id', $companyId)
            ->where(function ($q) use ($since) {
                $q->where('last_senior_action_at', '>=', $since)
                  ->orWhereIn('id', GuestAiConversation::where('created_at', '>=', $since)->pluck('guest_application_id')->all());
            })
            ->limit($limit * 3)
            ->get(['id', 'first_name', 'last_name', 'email', 'lead_score', 'lead_score_tier', 'last_senior_action_at'])
            ->map(function ($g) use ($since) {
                $qCount = GuestAiConversation::where('guest_application_id', $g->id)
                    ->where('created_at', '>=', $since)
                    ->count();
                return [
                    'type'             => 'guest',
                    'id'               => $g->id,
                    'name'             => trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? '')) ?: '—',
                    'email'            => $g->email,
                    'presence'         => null,
                    'tier'             => $g->lead_score_tier,
                    'lead_score'       => (int) ($g->lead_score ?? 0),
                    'last_activity_at' => $g->last_senior_action_at,
                    'questions'        => $qCount,
                    'activity_score'   => $this->guestActivityScore($g->id, $since) + (int) ($g->lead_score ?? 0) * 0.3,
                ];
            });

        $all = $studentRows->concat($guestRows);

        $sorted = match ($sort) {
            'lead_score'     => $all->sortByDesc(fn ($u) => $u['lead_score'] ?? 0),
            'last_activity'  => $all->sortByDesc(fn ($u) => $u['last_activity_at']),
            'name'           => $all->sortBy('name', SORT_REGULAR | SORT_FLAG_CASE | SORT_NATURAL),
            'questions'      => $all->sortByDesc('questions'),
            default          => $all->sortByDesc('activity_score'),
        };

        return $sorted->values()->take($limit)->all();
    }

    /**
     * Alarm: high-value guest dormant.
     */
    public function dormantAlerts(int $companyId, int $minScore = 40, int $dormantDays = 14, int $limit = 20): array
    {
        $dormantCutoff = now()->subDays($dormantDays);

        $guests = GuestApplication::where('company_id', $companyId)
            ->where('converted_to_student', false)
            ->where('lead_score', '>=', $minScore)
            ->where(function ($q) use ($dormantCutoff) {
                $q->where('last_senior_action_at', '<', $dormantCutoff)
                  ->orWhereNull('last_senior_action_at');
            })
            ->orderByDesc('lead_score')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'lead_score', 'lead_score_tier', 'assigned_senior_email', 'last_senior_action_at', 'created_at']);

        $daysSince = fn ($d) => $d ? (int) Carbon::parse($d)->diffInDays(now()) : null;

        return $guests->map(fn ($g) => [
            'id'                 => $g->id,
            'name'               => trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? '')) ?: '—',
            'email'              => $g->email,
            'phone'              => $g->phone,
            'lead_score'         => $g->lead_score,
            'tier'               => $g->lead_score_tier,
            'assigned_senior'    => $g->assigned_senior_email,
            'days_since_action'  => $daysSince($g->last_senior_action_at ?? $g->created_at),
            'last_action_at'     => $g->last_senior_action_at,
        ])->all();
    }

    /**
     * Alarm: dormant öğrenciler — son N günde aktivite yok + ödeme bekliyor VEYA
     * yaklaşan randevu yok. Senior/finans bölümüne öncelikli liste.
     */
    public function studentDormantAlerts(int $companyId, int $dormantDays = 14, int $limit = 20): array
    {
        $cutoff = now()->subDays($dormantDays);

        $students = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<', $cutoff)
                  ->orWhereNull('last_activity_at');
            })
            ->get(['id', 'name', 'email', 'presence_status', 'last_activity_at', 'created_at']);

        $out = [];
        foreach ($students as $u) {
            // Ek sinyal: overdue payment veya paid_at boş pending payment
            $overduePaymentCount = StudentPayment::where('student_id', $u->id)
                ->whereIn('status', ['overdue', 'pending'])
                ->count();

            // Yaklaşan randevu var mı?
            $upcomingAppt = StudentAppointment::where('student_id', $u->id)
                ->where('scheduled_at', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->exists();

            // Son 7 gündeki audit aksiyon sayısı (gerçekten dormant mı?)
            $recentActions = AuditTrail::where('user_id', $u->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            // Risk skoru: overdue payment + yaklaşan randevu yok + 0 son aksiyon
            $risk = 0;
            if ($overduePaymentCount > 0) $risk += 40;
            if (!$upcomingAppt) $risk += 20;
            if ($recentActions === 0) $risk += 20;
            if ($u->last_activity_at === null) $risk += 20;

            if ($risk < 40) continue; // sadece gerçekten risk altında olanlar

            $lastActivity = $u->last_activity_at;
            $daysSince = $lastActivity ? (int) Carbon::parse($lastActivity)->diffInDays(now()) : null;

            $out[] = [
                'id'                => $u->id,
                'name'              => $u->name,
                'email'             => $u->email,
                'presence'          => $u->presence_status,
                'last_activity_at'  => $lastActivity,
                'days_since'        => $daysSince,
                'overdue_payments'  => $overduePaymentCount,
                'has_upcoming_appt' => $upcomingAppt,
                'risk_score'        => $risk,
            ];
        }

        usort($out, fn ($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        return array_slice($out, 0, $limit);
    }

    /**
     * Kampanya etki ölçümü — bir tarihte yayınlanan içerik/duyuru sonrası
     * platform aktivitesi ne kadar değişti?
     *
     * Örnek: "15 Nisan'da blog post yayınlandık, sonraki 7 gün vs önceki 7 gün".
     *
     * @param Carbon|string $eventDate Yayın tarihi
     * @param int $windowDays Karşılaştırma penceresi (örn: 7)
     */
    public function campaignImpact(int $companyId, $eventDate, int $windowDays = 7): array
    {
        $eventAt = Carbon::parse($eventDate);
        $beforeStart = $eventAt->copy()->subDays($windowDays);
        $afterEnd    = $eventAt->copy()->addDays($windowDays);

        // Öncesi: [beforeStart, eventAt)
        $beforeLeads = GuestApplication::where('company_id', $companyId)
            ->whereBetween('created_at', [$beforeStart, $eventAt])
            ->count();
        $beforeAiQs = GuestAiConversation::join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->whereBetween('guest_ai_conversations.created_at', [$beforeStart, $eventAt])
            ->count();
        $beforeBookings = \DB::table('public_bookings')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$beforeStart, $eventAt])
            ->count();
        $beforeStudentActivity = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->whereBetween('last_activity_at', [$beforeStart, $eventAt])
            ->count();

        // Sonrası: (eventAt, afterEnd]
        $afterLeads = GuestApplication::where('company_id', $companyId)
            ->whereBetween('created_at', [$eventAt, $afterEnd])
            ->count();
        $afterAiQs = GuestAiConversation::join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->whereBetween('guest_ai_conversations.created_at', [$eventAt, $afterEnd])
            ->count();
        $afterBookings = \DB::table('public_bookings')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$eventAt, $afterEnd])
            ->count();
        $afterStudentActivity = User::where('company_id', $companyId)
            ->where('role', 'student')
            ->whereBetween('last_activity_at', [$eventAt, $afterEnd])
            ->count();

        $deltaPct = fn ($a, $b) => $b > 0 ? round(100 * ($a - $b) / $b, 1) : ($a > 0 ? 100 : 0);

        return [
            'event_at'       => $eventAt->toIso8601String(),
            'window_days'    => $windowDays,
            'period_before'  => $beforeStart->toDateString() . ' → ' . $eventAt->toDateString(),
            'period_after'   => $eventAt->toDateString() . ' → ' . $afterEnd->toDateString(),
            'metrics' => [
                'new_leads' => [
                    'before' => $beforeLeads,
                    'after'  => $afterLeads,
                    'delta_pct' => $deltaPct($afterLeads, $beforeLeads),
                ],
                'ai_queries' => [
                    'before' => $beforeAiQs,
                    'after'  => $afterAiQs,
                    'delta_pct' => $deltaPct($afterAiQs, $beforeAiQs),
                ],
                'bookings' => [
                    'before' => $beforeBookings,
                    'after'  => $afterBookings,
                    'delta_pct' => $deltaPct($afterBookings, $beforeBookings),
                ],
                'student_active' => [
                    'before' => $beforeStudentActivity,
                    'after'  => $afterStudentActivity,
                    'delta_pct' => $deltaPct($afterStudentActivity, $beforeStudentActivity),
                ],
            ],
        ];
    }

    /**
     * Günlük aktivite trendi — sparkline için.
     */
    public function dailyTrend(int $companyId, int $daysBack = 30): array
    {
        $since = now()->subDays($daysBack)->startOfDay();

        $studentActivity = AuditTrail::where('audit_trails.company_id', $companyId)
            ->where('audit_trails.created_at', '>=', $since)
            ->join('users', 'audit_trails.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->selectRaw('DATE(audit_trails.created_at) as d, COUNT(DISTINCT audit_trails.user_id) as cnt')
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $guestActivity = GuestAiConversation::join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->selectRaw('DATE(guest_ai_conversations.created_at) as d, COUNT(DISTINCT guest_ai_conversations.guest_application_id) as cnt')
            ->groupBy('d')
            ->pluck('cnt', 'd')
            ->toArray();

        $out = [];
        $cursor = $since->copy();
        while ($cursor->lte(now()->endOfDay())) {
            $key = $cursor->toDateString();
            $out[] = [
                'date'    => $key,
                'student' => (int) ($studentActivity[$key] ?? 0),
                'guest'   => (int) ($guestActivity[$key] ?? 0),
                'total'   => (int) (($studentActivity[$key] ?? 0) + ($guestActivity[$key] ?? 0)),
            ];
            $cursor = $cursor->addDay();
        }

        return $out;
    }

    /**
     * Bir guest_application için detay aktivite timeline.
     * Öğrenci versiyonu ayrı metod.
     */
    public function guestTimeline(int $guestId): array
    {
        $timeline = collect();

        $guest = GuestApplication::find($guestId);
        if (!$guest) return [];

        // 1. Lead oluştu
        $timeline->push([
            'at'    => $guest->created_at,
            'type'  => 'lead_created',
            'icon'  => '🆕',
            'title' => 'Kayıt oluşturuldu',
            'meta'  => "Source: " . ($guest->source ?? '—'),
        ]);

        // 2. Funnel timestamps
        $fsd = \DB::table('lead_source_data')->where('guest_id', $guestId)->first();
        if ($fsd) {
            foreach ([
                'funnel_form_completed_at'     => ['🟢', 'Form tamamlandı'],
                'funnel_documents_uploaded_at' => ['📄', 'Belgeler yüklendi'],
                'funnel_package_selected_at'   => ['🎯', 'Paket seçildi'],
                'funnel_contract_signed_at'    => ['✍️', 'Sözleşme imzalandı'],
                'funnel_converted_at'          => ['✅', 'Müşteriye dönüştü'],
            ] as $field => [$icon, $title]) {
                if (!empty($fsd->{$field})) {
                    $timeline->push([
                        'at'    => $fsd->{$field},
                        'type'  => $field,
                        'icon'  => $icon,
                        'title' => $title,
                        'meta'  => null,
                    ]);
                }
            }
        }

        // 3. AI soruları
        $aiQs = GuestAiConversation::where('guest_application_id', $guestId)
            ->orderBy('created_at')
            ->get(['id', 'question', 'created_at']);
        foreach ($aiQs as $q) {
            $timeline->push([
                'at'    => $q->created_at,
                'type'  => 'ai_question',
                'icon'  => '🤖',
                'title' => 'AI\'ya soru sordu',
                'meta'  => \Illuminate\Support\Str::limit($q->question, 100),
            ]);
        }

        // 4. Senior aksiyonları
        if ($guest->last_senior_action_at) {
            $timeline->push([
                'at'    => $guest->last_senior_action_at,
                'type'  => 'senior_action',
                'icon'  => '👥',
                'title' => 'Senior aksiyon aldı',
                'meta'  => $guest->assigned_senior_email ?? null,
            ]);
        }

        // 5. Audit trail (bu user'ın son 30 aksiyonu)
        $audits = AuditTrail::where('entity_type', 'App\Models\GuestApplication')
            ->where('entity_id', $guestId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['action', 'created_at', 'new_values']);
        foreach ($audits as $a) {
            if (in_array($a->action, ['create'], true)) continue; // lead_created zaten var
            $timeline->push([
                'at'    => $a->created_at,
                'type'  => 'audit_' . $a->action,
                'icon'  => '📝',
                'title' => 'Kayıt ' . $a->action,
                'meta'  => null,
            ]);
        }

        return $timeline->sortByDesc('at')->values()->all();
    }

    // ── Internals ────────────────────────────────────────────────────

    private function guestsActiveSince(int $companyId, Carbon $since): int
    {
        // Guest "aktif" = son_senior_aksiyon VEYA AI soru VEYA yeni kayıt
        $byAction = GuestApplication::where('company_id', $companyId)
            ->where('last_senior_action_at', '>=', $since)
            ->pluck('id');
        $byAi = GuestAiConversation::join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->pluck('guest_applications.id');
        $byCreate = GuestApplication::where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->pluck('id');

        return $byAction->concat($byAi)->concat($byCreate)->unique()->count();
    }

    private function guestEngagementTier(int $guestId, $lastSeniorAction): string
    {
        $now7  = now()->subDays(7);
        $now30 = now()->subDays(30);

        $lastAi = GuestAiConversation::where('guest_application_id', $guestId)->max('created_at');
        $lastAction = max(
            $lastSeniorAction ? Carbon::parse($lastSeniorAction) : null,
            $lastAi ? Carbon::parse($lastAi) : null
        );

        if (!$lastAction) return self::TIER_DORMANT;
        if ($lastAction->gte($now7)) return self::TIER_ACTIVE;
        if ($lastAction->gte($now30)) return self::TIER_AT_RISK;
        return self::TIER_DORMANT;
    }

    private function userActivityScore(int $userId, Carbon $since): int
    {
        // Basit score: audit trail sayısı + appointment sayısı
        $audit = AuditTrail::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->count();
        $appt = StudentAppointment::where('student_id', $userId)
            ->where('scheduled_at', '>=', $since)
            ->count();
        $msgs = DmMessage::where('sender_user_id', $userId)
            ->where('created_at', '>=', $since)
            ->count();

        return (int) ($audit + $appt * 3 + $msgs);
    }

    private function guestActivityScore(int $guestId, Carbon $since): int
    {
        $ai = GuestAiConversation::where('guest_application_id', $guestId)
            ->where('created_at', '>=', $since)
            ->count();
        $audit = AuditTrail::where('entity_type', 'App\Models\GuestApplication')
            ->where('entity_id', $guestId)
            ->where('created_at', '>=', $since)
            ->count();

        return (int) ($ai * 2 + $audit);
    }
}
