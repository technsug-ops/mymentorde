<?php

namespace App\Services\AiLabs;

use App\Models\AiLabsContentDraft;
use App\Models\GuestAiConversation;
use App\Models\KnowledgeSource;
use App\Models\SeniorAiConversation;
use App\Models\StaffAiConversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AI Labs analytics — conversation + content draft + kaynak metrikleri.
 *
 * Gemini 2.5 Flash fiyatlandırma (2026):
 *   Input:  $0.30 / 1M token
 *   Output: $2.50 / 1M token
 */
class AnalyticsService
{
    private const GEMINI_INPUT_COST_USD = 0.30 / 1_000_000;  // per token
    private const GEMINI_OUTPUT_COST_USD = 2.50 / 1_000_000;
    private const USD_TO_EUR = 0.92; // yaklaşık kur

    // Anlamsız kelimeler (top konu çıkarımı için)
    private const STOPWORDS = [
        've','veya','bir','ile','için','ama','nasıl','ne','neden','gibi','bu','şu','o',
        'da','de','ki','mi','mu','mı','mü','her','çok','daha','hangi','olur','olmak','olan',
        'eğer','sonra','önce','fakat','yani','şey','nedir','var','yok','iyi','sadece',
        'bence','göre','bence','peki','kadar','tüm','aynı','yeni','yine','sadece',
        'nasil','nedir','misin','misiniz','lütfen','tşk','tşkler','teşekkürler','selam',
        'merhaba','evet','hayır','tamam','haber','bilgi','ilgili','hakkında','eder',
    ];

    /**
     * Bu ay tüm metrikler — tek çağrı.
     *
     * @return array<string, mixed>
     */
    public function monthly(int $companyId): array
    {
        $monthStart = now()->startOfMonth();

        return [
            'period_label'         => $monthStart->translatedFormat('F Y'),
            'conversations'        => $this->conversationMetrics($companyId, $monthStart),
            'response_modes'       => $this->responseModeDistribution($companyId, $monthStart),
            'top_topics'           => $this->topTopics($companyId, $monthStart, 10),
            'faq_candidates'       => $this->faqCandidates($companyId, 60, 2, 20),
            'hot_leads'            => $this->hotLeads($companyId, 30, 15),
            'topic_categories'     => $this->topicCategories($companyId, $monthStart),
            'conversion_intents'   => $this->conversionVsLostIntents($companyId, 180),
            'unused_sources'       => $this->unusedSources($companyId, 30),
            'top_cited_sources'    => $this->topCitedSources($companyId, 10),
            'content_drafts'       => $this->contentDraftMetrics($companyId, $monthStart),
            'daily_trend'          => $this->dailyTrend($companyId, 30),
            'feedback'             => $this->feedbackMetrics($companyId, $monthStart),
            'problem_answers'      => $this->problemAnswers($companyId, 10),
            'alerts'               => $this->alerts($companyId, $monthStart),
        ];
    }

    // ── HOT LEADS — AI kullanan adaylar, öncelik sırasıyla ────────────

    /**
     * AI kullanan guest_application'lar — soru sayısı, son aktivite, kategoriler.
     * Manager'a "bu adamla hemen ilgilen" sinyali verir.
     *
     * Sıralama: (soru_sayısı × 2 + lead_score × 0.5 + son aktivite bonus)
     */
    public function hotLeads(int $companyId, int $daysBack = 30, int $limit = 15): array
    {
        $since = now()->subDays($daysBack);

        // guest_application_id → stats
        $rows = GuestAiConversation::query()
            ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->selectRaw('
                guest_applications.id as lead_id,
                guest_applications.first_name,
                guest_applications.last_name,
                guest_applications.email,
                guest_applications.phone,
                guest_applications.lead_score,
                guest_applications.lead_score_tier,
                guest_applications.converted_to_student,
                guest_applications.assigned_senior_email,
                guest_applications.created_at as lead_created_at,
                COUNT(guest_ai_conversations.id) as question_count,
                MAX(guest_ai_conversations.created_at) as last_question_at
            ')
            ->groupBy(
                'guest_applications.id', 'guest_applications.first_name', 'guest_applications.last_name',
                'guest_applications.email', 'guest_applications.phone', 'guest_applications.lead_score',
                'guest_applications.lead_score_tier', 'guest_applications.converted_to_student',
                'guest_applications.assigned_senior_email', 'guest_applications.created_at'
            )
            ->get();

        // Her lead için topic dağılımını bul
        $leadIds = $rows->pluck('lead_id')->all();
        $questions = $leadIds
            ? GuestAiConversation::whereIn('guest_application_id', $leadIds)
                ->where('created_at', '>=', $since)
                ->get(['guest_application_id', 'question'])
                ->groupBy('guest_application_id')
            : collect();

        $out = [];
        foreach ($rows as $r) {
            $qs = $questions->get($r->lead_id, collect())->pluck('question')->all();
            $topics = $this->categorizeQuestions($qs);
            $hoursSinceLast = $r->last_question_at
                ? max(1, now()->diffInHours($r->last_question_at, false) * -1)
                : 9999;
            $recencyBonus = $hoursSinceLast <= 24 ? 20 : ($hoursSinceLast <= 72 ? 10 : 0);
            $hotness = (int) (
                $r->question_count * 2
                + (int) ($r->lead_score ?? 0) * 0.5
                + $recencyBonus
                + ($r->lead_score_tier === 'hot' ? 25 : 0)
                + ($r->lead_score_tier === 'sales_ready' ? 40 : 0)
            );

            $out[] = [
                'lead_id'         => (int) $r->lead_id,
                'full_name'       => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '—',
                'email'           => $r->email,
                'phone'           => $r->phone,
                'lead_score'      => (int) ($r->lead_score ?? 0),
                'tier'            => $r->lead_score_tier ?? 'cold',
                'converted'       => (bool) $r->converted_to_student,
                'assigned_senior' => $r->assigned_senior_email,
                'question_count'  => (int) $r->question_count,
                'last_question_at'=> $r->last_question_at,
                'top_topics'      => array_slice($topics, 0, 3, true),
                'hotness'         => $hotness,
            ];
        }

        usort($out, fn ($a, $b) => $b['hotness'] <=> $a['hotness']);
        return array_slice($out, 0, $limit);
    }

    // ── TOPIC CATEGORIZATION — keyword matching ───────────────────────

    /**
     * Taksonomi — sorudaki kelimelere göre soru tipini sınıflandır.
     * Basit keyword matching, gerçek NLP değil ama domain-specific iyi çalışır.
     */
    private const TOPIC_CATEGORIES = [
        'vize'        => ['vize', 'visum', 'visa', 'schengen', 'randevu vize', 'vize başvuru', 'konsoloslu'],
        'üniversite'  => ['üniversite', 'university', 'uni ', 'universität', 'tu ', 'lmu', 'rwth', 'başvuru üniversite', 'kayıt', 'hochschule', 'bewerbung'],
        'barınma'     => ['ev', 'konut', 'wohnung', 'yurt', 'wohnheim', 'kira', 'oda', 'zimmer', 'studentenwohnheim'],
        'dil'         => ['almanca', 'dil', 'deutsch', 'b1', 'b2', 'c1', 'c2', 'a1', 'a2', 'sprachkurs', 'kurs', 'testdaf', 'dsh', 'telc'],
        'maliyet'     => ['fiyat', 'maliyet', 'ücret', 'kosten', 'harç', 'burs', 'stipendium', 'euro', 'maaş', 'para', 'kaç para', 'ne kadar'],
        'sigorta'     => ['sigorta', 'versicherung', 'sağlık sigorta', 'krankenversicherung', 'tk', 'aok'],
        'banka'       => ['banka', 'sperrkonto', 'bloke hesap', 'kontoeröffnung', 'deutsche bank'],
        'iş'          => ['iş', 'çalışma', 'part time', 'minijob', 'werkstudent', 'praktikum', 'staj', 'maaş'],
        'blokhesap'   => ['sperrkonto', 'blok hesap', 'bloke hesap', 'expatrio', 'fintiba', 'coracle'],
        'ulaşım'      => ['semester ticket', 'deutschlandticket', 'ticket', 'metro', 's-bahn', 'u-bahn'],
    ];

    /**
     * Questions listesini kategorilere göre sayar.
     *
     * @return array<string,int>  kategori → adet
     */
    public function categorizeQuestions(array $questions): array
    {
        $out = [];
        foreach ($questions as $q) {
            $q = mb_strtolower((string) $q, 'UTF-8');
            foreach (self::TOPIC_CATEGORIES as $cat => $keywords) {
                foreach ($keywords as $kw) {
                    if (mb_strpos($q, $kw) !== false) {
                        $out[$cat] = ($out[$cat] ?? 0) + 1;
                        continue 2; // bir soru = bir kategori
                    }
                }
            }
        }
        arsort($out);
        return $out;
    }

    /**
     * Kategori dağılımı — tüm sorular için.
     */
    public function topicCategories(int $companyId, Carbon $since): array
    {
        $questions = $this->collectAllQuestions($companyId, $since);
        return $this->categorizeQuestions($questions);
    }

    // ── CONVERSION vs LOST INTENT ─────────────────────────────────────

    /**
     * Converted (müşteri olmuş) vs Not-converted lead'lerin soru kategorileri.
     * Hangi intent conversion'a götürüyor?
     *
     * @return array{converted:array, not_converted:array, insight:array}
     */
    public function conversionVsLostIntents(int $companyId, int $daysBack = 180): array
    {
        $since = now()->subDays($daysBack);

        $convertedIds = \App\Models\GuestApplication::where('company_id', $companyId)
            ->where('converted_to_student', true)
            ->where('created_at', '>=', $since)
            ->pluck('id')->all();
        $lostIds = \App\Models\GuestApplication::where('company_id', $companyId)
            ->where('converted_to_student', false)
            ->where('created_at', '>=', $since)
            ->whereNotNull('first_name')
            ->pluck('id')->all();

        $convertedQs = !empty($convertedIds)
            ? GuestAiConversation::whereIn('guest_application_id', $convertedIds)->pluck('question')->all()
            : [];
        $lostQs = !empty($lostIds)
            ? GuestAiConversation::whereIn('guest_application_id', $lostIds)->pluck('question')->all()
            : [];

        $convCat = $this->categorizeQuestions($convertedQs);
        $lostCat = $this->categorizeQuestions($lostQs);

        $convTotal = max(1, array_sum($convCat));
        $lostTotal = max(1, array_sum($lostCat));

        // Her kategori için "conversion signal strength" — converted'de daha yoğunsa pozitif sinyal
        $insight = [];
        $allCats = array_unique(array_merge(array_keys($convCat), array_keys($lostCat)));
        foreach ($allCats as $cat) {
            $convPct = 100 * ($convCat[$cat] ?? 0) / $convTotal;
            $lostPct = 100 * ($lostCat[$cat] ?? 0) / $lostTotal;
            $insight[$cat] = [
                'converted_pct'   => round($convPct, 1),
                'not_converted_pct' => round($lostPct, 1),
                'signal'          => round($convPct - $lostPct, 1), // pozitifse conversion sinyali
            ];
        }
        uasort($insight, fn ($a, $b) => $b['signal'] <=> $a['signal']);

        return [
            'period_days'       => $daysBack,
            'converted_count'   => count($convertedIds),
            'not_converted_count' => count($lostIds),
            'converted'         => $convCat,
            'not_converted'     => $lostCat,
            'insight'           => $insight,
        ];
    }

    /**
     * FAQ adayları — benzer sorular (ilk 6 kelime ile gruplandırılmış),
     * minimum N kez sorulmuş. En yüksek değerli harvest output.
     *
     * Top topics kelime-frekansı verir, bu CÜMLE-seviyesi benzerlik verir.
     *
     * @return array<int, array{intent_key:string, sample_question:string, count:int, last_asked:\DateTimeInterface|string|null, roles:array<string,int>}>
     */
    public function faqCandidates(int $companyId, int $daysBack = 60, int $minOccurrence = 2, int $limit = 20): array
    {
        $since = now()->subDays($daysBack);

        // Her soruyu intent_key ile etiketle + role'ü bilelim ki role dağılımı gösterelim
        $rows = [];

        $guestRows = GuestAiConversation::query()
            ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->select('guest_ai_conversations.question', 'guest_ai_conversations.role', 'guest_ai_conversations.created_at')
            ->get();
        $seniorRows = SeniorAiConversation::query()
            ->join('users', 'senior_ai_conversations.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('senior_ai_conversations.created_at', '>=', $since)
            ->select('senior_ai_conversations.question', DB::raw("'senior' as role"), 'senior_ai_conversations.created_at')
            ->get();
        $staffRows = StaffAiConversation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->select('question', 'role', 'created_at')
            ->get();

        foreach ([$guestRows, $seniorRows, $staffRows] as $set) {
            foreach ($set as $r) {
                if (empty($r->question)) continue;
                $rows[] = [
                    'key'        => $this->faqIntentKey($r->question),
                    'question'   => $r->question,
                    'role'       => $r->role ?? 'unknown',
                    'created_at' => $r->created_at,
                ];
            }
        }

        // Grupla
        $grouped = [];
        foreach ($rows as $row) {
            $k = $row['key'];
            if ($k === '') continue;
            if (!isset($grouped[$k])) {
                $grouped[$k] = [
                    'intent_key'      => $k,
                    'sample_question' => $row['question'],
                    'count'           => 0,
                    'last_asked'      => null,
                    'roles'           => [],
                ];
            }
            $grouped[$k]['count']++;
            $grouped[$k]['roles'][$row['role']] = ($grouped[$k]['roles'][$row['role']] ?? 0) + 1;
            if (!$grouped[$k]['last_asked'] || $row['created_at'] > $grouped[$k]['last_asked']) {
                $grouped[$k]['last_asked'] = $row['created_at'];
            }
        }

        $candidates = array_filter($grouped, fn ($g) => $g['count'] >= $minOccurrence);
        usort($candidates, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($candidates, 0, $limit);
    }

    /**
     * FAQ için intent key — faqCandidates'in gruplamasında kullanılır.
     * topTopics'in kelime-frekansından ayrılır: cümle-benzerliği için
     * ilk 6 kelimelik normalize edilmiş string döner.
     */
    private function faqIntentKey(string $question): string
    {
        $q = mb_strtolower(trim((string) $question), 'UTF-8');
        $q = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q);
        $q = preg_replace('/\s+/u', ' ', $q);
        $q = trim($q);
        if ($q === '') return '';
        $words = array_slice(explode(' ', $q), 0, 6);
        return implode(' ', $words);
    }

    // ── Conversation metrikleri ─────────────────────────────────────

    public function conversationMetrics(int $companyId, Carbon $since): array
    {
        // GuestAiConversation — guest_application_id üzerinden company dolaylı
        $guestRows = GuestAiConversation::query()
            ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->selectRaw("guest_ai_conversations.role, COUNT(*) as cnt, SUM(tokens_input) as tin, SUM(tokens_output) as tout")
            ->groupBy('guest_ai_conversations.role')
            ->get();

        // Senior
        $seniorRow = SeniorAiConversation::query()
            ->join('users', 'senior_ai_conversations.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('senior_ai_conversations.created_at', '>=', $since)
            ->selectRaw("COUNT(*) as cnt, SUM(tokens_input) as tin, SUM(tokens_output) as tout")
            ->first();

        // Staff
        $staffRows = StaffAiConversation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->selectRaw("role, COUNT(*) as cnt, SUM(tokens_input) as tin, SUM(tokens_output) as tout")
            ->groupBy('role')
            ->get();

        $byRole = [
            'guest'       => ['count' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
            'student'     => ['count' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
            'senior'      => ['count' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
            'manager'     => ['count' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
            'admin_staff' => ['count' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
        ];

        foreach ($guestRows as $r) {
            $role = $r->role ?: 'guest';
            if (!isset($byRole[$role])) continue;
            $byRole[$role]['count'] += (int) $r->cnt;
            $byRole[$role]['tokens_in'] += (int) $r->tin;
            $byRole[$role]['tokens_out'] += (int) $r->tout;
        }
        if ($seniorRow) {
            $byRole['senior']['count'] = (int) $seniorRow->cnt;
            $byRole['senior']['tokens_in'] = (int) $seniorRow->tin;
            $byRole['senior']['tokens_out'] = (int) $seniorRow->tout;
        }
        foreach ($staffRows as $r) {
            $role = $r->role ?: 'manager';
            if (!isset($byRole[$role])) continue;
            $byRole[$role]['count'] = (int) $r->cnt;
            $byRole[$role]['tokens_in'] = (int) $r->tin;
            $byRole[$role]['tokens_out'] = (int) $r->tout;
        }

        $totalCount = array_sum(array_column($byRole, 'count'));
        $totalIn    = array_sum(array_column($byRole, 'tokens_in'));
        $totalOut   = array_sum(array_column($byRole, 'tokens_out'));

        $costUsd = $totalIn * self::GEMINI_INPUT_COST_USD + $totalOut * self::GEMINI_OUTPUT_COST_USD;
        $costEur = $costUsd * self::USD_TO_EUR;

        return [
            'total_count'   => $totalCount,
            'total_tokens'  => $totalIn + $totalOut,
            'tokens_in'     => $totalIn,
            'tokens_out'    => $totalOut,
            'cost_eur'      => round($costEur, 3),
            'by_role'       => $byRole,
        ];
    }

    // ── Response mode dağılım (source / external / refused) ─────────

    public function responseModeDistribution(int $companyId, Carbon $since): array
    {
        $modes = ['source' => 0, 'external' => 0, 'refused' => 0, 'unknown' => 0];

        // Guest + student
        $guestRows = GuestAiConversation::query()
            ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->selectRaw('response_mode, COUNT(*) as cnt')
            ->groupBy('response_mode')
            ->get();

        foreach ($guestRows as $r) {
            $key = $r->response_mode ?: 'unknown';
            $modes[$key] = ($modes[$key] ?? 0) + (int) $r->cnt;
        }

        // Senior
        $seniorRows = SeniorAiConversation::query()
            ->join('users', 'senior_ai_conversations.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('senior_ai_conversations.created_at', '>=', $since)
            ->selectRaw('response_mode, COUNT(*) as cnt')
            ->groupBy('response_mode')
            ->get();
        foreach ($seniorRows as $r) {
            $key = $r->response_mode ?: 'unknown';
            $modes[$key] = ($modes[$key] ?? 0) + (int) $r->cnt;
        }

        // Staff
        $staffRows = StaffAiConversation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->selectRaw('response_mode, COUNT(*) as cnt')
            ->groupBy('response_mode')
            ->get();
        foreach ($staffRows as $r) {
            $key = $r->response_mode ?: 'unknown';
            $modes[$key] = ($modes[$key] ?? 0) + (int) $r->cnt;
        }

        $total = array_sum($modes);
        $pct = [];
        foreach ($modes as $k => $v) {
            $pct[$k] = $total > 0 ? round(($v / $total) * 100, 1) : 0;
        }

        return [
            'counts'     => $modes,
            'percent'    => $pct,
            'total'      => $total,
        ];
    }

    // ── Top konular — soru metinlerinden kelime frekansı ───────────

    public function topTopics(int $companyId, Carbon $since, int $limit = 10): array
    {
        $allQuestions = $this->collectAllQuestions($companyId, $since);

        $freq = [];
        foreach ($allQuestions as $q) {
            $words = $this->tokenize($q);
            foreach ($words as $w) {
                $freq[$w] = ($freq[$w] ?? 0) + 1;
            }
        }

        arsort($freq);
        $top = array_slice($freq, 0, $limit, true);

        return array_map(
            fn ($count, $word) => ['word' => $word, 'count' => $count],
            array_values($top),
            array_keys($top)
        );
    }

    /**
     * @return array<int,string>
     */
    private function collectAllQuestions(int $companyId, Carbon $since): array
    {
        $all = [];

        $guestQs = GuestAiConversation::query()
            ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.company_id', $companyId)
            ->where('guest_ai_conversations.created_at', '>=', $since)
            ->pluck('guest_ai_conversations.question');
        $seniorQs = SeniorAiConversation::query()
            ->join('users', 'senior_ai_conversations.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->where('senior_ai_conversations.created_at', '>=', $since)
            ->pluck('senior_ai_conversations.question');
        $staffQs = StaffAiConversation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->pluck('question');

        foreach ([$guestQs, $seniorQs, $staffQs] as $set) {
            foreach ($set as $q) {
                if (!empty($q)) $all[] = (string) $q;
            }
        }

        return $all;
    }

    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        // Noktalama temizle
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w, 'UTF-8') < 4) continue; // kısa kelimeleri at
            if (in_array($w, self::STOPWORDS, true)) continue;
            if (preg_match('/^\d+$/', $w)) continue; // sadece sayı
            $out[] = $w;
        }
        return $out;
    }

    // ── Kaynaklar ───────────────────────────────────────────────────

    public function unusedSources(int $companyId, int $dayThreshold = 30): array
    {
        $cutoff = now()->subDays($dayThreshold);

        return KnowledgeSource::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_used_at')->orWhere('last_used_at', '<', $cutoff);
            })
            ->orderBy('citation_count', 'asc')
            ->orderBy('created_at', 'asc')
            ->take(20)
            ->get(['id', 'title', 'type', 'citation_count', 'last_used_at', 'created_at'])
            ->toArray();
    }

    public function topCitedSources(int $companyId, int $limit = 10): array
    {
        return KnowledgeSource::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('citation_count', '>', 0)
            ->orderByDesc('citation_count')
            ->take($limit)
            ->get(['id', 'title', 'type', 'citation_count', 'last_used_at'])
            ->toArray();
    }

    // ── Content draft metrikleri ───────────────────────────────────

    public function contentDraftMetrics(int $companyId, Carbon $since): array
    {
        $rows = AiLabsContentDraft::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->selectRaw('template_code, status, COUNT(*) as cnt, SUM(tokens_input + tokens_output) as toks')
            ->groupBy('template_code', 'status')
            ->get();

        $byTemplate = [];
        foreach ($rows as $r) {
            $tpl = $r->template_code;
            if (!isset($byTemplate[$tpl])) {
                $byTemplate[$tpl] = ['count' => 0, 'tokens' => 0, 'by_status' => []];
            }
            $byTemplate[$tpl]['count'] += (int) $r->cnt;
            $byTemplate[$tpl]['tokens'] += (int) $r->toks;
            $byTemplate[$tpl]['by_status'][$r->status] = (int) $r->cnt;
        }

        return [
            'by_template' => $byTemplate,
            'total'       => array_sum(array_column($byTemplate, 'count')),
        ];
    }

    // ── Daily trend (son 30 gün) ────────────────────────────────────

    public function dailyTrend(int $companyId, int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        $byDay = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $byDay[$d] = 0;
        }

        // Tüm conversation tabloları
        $queries = [
            GuestAiConversation::query()
                ->join('guest_applications', 'guest_ai_conversations.guest_application_id', '=', 'guest_applications.id')
                ->where('guest_applications.company_id', $companyId)
                ->where('guest_ai_conversations.created_at', '>=', $since)
                ->selectRaw("DATE(guest_ai_conversations.created_at) as d, COUNT(*) as cnt")
                ->groupBy('d'),
            SeniorAiConversation::query()
                ->join('users', 'senior_ai_conversations.user_id', '=', 'users.id')
                ->where('users.company_id', $companyId)
                ->where('senior_ai_conversations.created_at', '>=', $since)
                ->selectRaw("DATE(senior_ai_conversations.created_at) as d, COUNT(*) as cnt")
                ->groupBy('d'),
            StaffAiConversation::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE(created_at) as d, COUNT(*) as cnt")
                ->groupBy('d'),
        ];

        foreach ($queries as $q) {
            foreach ($q->get() as $r) {
                $d = (string) $r->d;
                if (isset($byDay[$d])) {
                    $byDay[$d] += (int) $r->cnt;
                }
            }
        }

        return $byDay;
    }

    // ── Feedback (👍/👎) metrikleri ─────────────────────────────────

    public function feedbackMetrics(int $companyId, Carbon $since): array
    {
        $rows = \App\Models\AiLabsFeedback::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating')
            ->toArray();

        $good = (int) ($rows['good'] ?? 0);
        $bad  = (int) ($rows['bad']  ?? 0);
        $total = $good + $bad;

        return [
            'good'          => $good,
            'bad'           => $bad,
            'total'         => $total,
            'satisfaction'  => $total > 0 ? round(($good / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Son N tane 👎 işaretli yanıt — manager kalite kontrol için görür.
     */
    public function problemAnswers(int $companyId, int $limit = 10): array
    {
        $feedback = \App\Models\AiLabsFeedback::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('rating', 'bad')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'conversation_type', 'conversation_id', 'role', 'created_at', 'reason']);

        $out = [];
        foreach ($feedback as $fb) {
            $conv = $this->fetchConversation((string) $fb->conversation_type, (int) $fb->conversation_id);
            if (!$conv) continue;

            $out[] = [
                'id'          => (int) $fb->id,
                'conv_type'   => (string) $fb->conversation_type,
                'question'    => \Illuminate\Support\Str::limit((string) ($conv['question'] ?? ''), 120),
                'answer'      => \Illuminate\Support\Str::limit((string) ($conv['answer'] ?? ''), 200),
                'role'        => $fb->role,
                'reason'      => (string) ($fb->reason ?? ''),
                'created_at'  => $fb->created_at?->format('d.m.Y H:i'),
            ];
        }
        return $out;
    }

    private function fetchConversation(string $type, int $id): ?array
    {
        $model = match ($type) {
            'guest'  => \App\Models\GuestAiConversation::class,
            'senior' => \App\Models\SeniorAiConversation::class,
            'staff'  => \App\Models\StaffAiConversation::class,
            default  => null,
        };
        if (!$model) return null;

        $row = $model::query()->withoutGlobalScopes()->where('id', $id)->first(['question', 'answer']);
        return $row ? ['question' => $row->question, 'answer' => $row->answer] : null;
    }

    // ── Uyarılar ────────────────────────────────────────────────────

    public function alerts(int $companyId, Carbon $since): array
    {
        $alerts = [];

        $modes = $this->responseModeDistribution($companyId, $since);
        if ($modes['total'] >= 10) {
            if (($modes['percent']['external'] ?? 0) > 30) {
                $alerts[] = [
                    'level'   => 'warning',
                    'icon'    => '⚠️',
                    'title'   => 'Havuz-dışı yanıt oranı yüksek',
                    'message' => "Bu ay soruların %{$modes['percent']['external']}'si havuz-dışı (🟡 external) yanıtlandı. Yeni kaynak eklemeyi düşünün.",
                ];
            }
            if (($modes['percent']['refused'] ?? 0) > 20) {
                $alerts[] = [
                    'level'   => 'info',
                    'icon'    => '⚪',
                    'title'   => 'Kapsam-dışı soru oranı yüksek',
                    'message' => "Soruların %{$modes['percent']['refused']}'si AI tarafından reddedildi. Kullanıcılar yanlış konularda soru sorabilir.",
                ];
            }
        }

        // Kullanılmayan kaynak uyarısı
        $unused = $this->unusedSources($companyId, 30);
        if (count($unused) >= 3) {
            $alerts[] = [
                'level'   => 'info',
                'icon'    => '📦',
                'title'   => 'Kullanılmayan kaynaklar var',
                'message' => count($unused) . ' aktif kaynak son 30 gündür hiç citation almadı. Gözden geçirin veya pasifleştirin.',
            ];
        }

        // API key kontrolü
        $kbEnabled = KnowledgeSource::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();
        if ($kbEnabled === 0) {
            $alerts[] = [
                'level'   => 'danger',
                'icon'    => '❌',
                'title'   => 'Hiç aktif kaynak yok',
                'message' => 'Bilgi havuzunda aktif kaynak yok — AI sadece genel bilgisinden yanıtlar.',
            ];
        }

        return $alerts;
    }
}
