<?php

namespace App\Services\AiLabs;

use App\Models\AiLabsSettings;
use App\Models\GuestAiConversation;
use App\Models\SeniorAiConversation;
use App\Models\StaffAiConversation;

/**
 * AI Labs — tüm roller için tek asistan giriş noktası.
 *
 * Her rol için uygun conversation tablosunu seçer, daily limit kontrolü yapar,
 * ResponseRouter'a delegasyon + sonucu kaydeder.
 *
 * Davranış:
 *   - Limit aşımı → ['ok' => false, 'limit_exceeded' => true, 'remaining' => 0]
 *   - Başarılı yanıt → ['ok' => true, 'answer' => ..., 'mode' => 'source|external|refused',
 *                       'sources_meta' => [...], 'remaining' => N]
 */
class AiLabsAssistantService
{
    public function __construct(private ResponseRouter $router) {}

    /**
     * @param array<string,mixed> $context  Rol-özel ek bağlam (docs_uploaded, progress, vs.)
     *
     * @return array{
     *   ok:bool,
     *   answer?:string,
     *   mode?:string,
     *   sources_meta?:array,
     *   remaining?:int,
     *   limit_exceeded?:bool,
     *   error?:string
     * }
     */
    public function ask(int $companyId, string $role, int $userId, ?int $guestApplicationId, string $question, array $context = []): array
    {
        // Limit kontrolü
        $dailyUsed = $this->dailyCount($role, $userId, $guestApplicationId);
        $limit = $this->dailyLimit($companyId, $role);

        if ($limit > 0 && $dailyUsed >= $limit) {
            return [
                'ok'             => false,
                'limit_exceeded' => true,
                'remaining'      => 0,
                'error'          => "Günlük limit aşıldı ({$limit} soru/gün)",
            ];
        }

        // Son 5 mesajı history olarak çıkar — konuşma sürekliliği
        $history = $this->recentHistory($role, $userId, $guestApplicationId, 5);

        // Router'a sor (context = kullanıcı profili — kişiselleştirme için)
        $result = $this->router->ask($companyId, $role, $question, $context, $userId, $history);

        if (!($result['ok'] ?? false)) {
            return ['ok' => false, 'error' => $result['error'] ?? 'unknown'];
        }

        // Conversation kaydet ve id'yi al (feedback için)
        $conversation = $this->saveConversation($companyId, $role, $userId, $guestApplicationId, $question, $result, $context);

        return [
            'ok'                 => true,
            'answer'             => $result['content'] ?? '',
            'mode'               => $result['mode'] ?? 'external',
            'sources_meta'       => $result['sources_meta'] ?? [],
            'remaining'          => $limit > 0 ? max(0, $limit - $dailyUsed - 1) : 999,
            'tokens_input'       => $result['tokens_input'] ?? 0,
            'tokens_output'      => $result['tokens_output'] ?? 0,
            'conversation_id'    => $conversation?->id ?? null,
            'conversation_type'  => $this->conversationTypeFor($role),
        ];
    }

    private function conversationTypeFor(string $role): string
    {
        return match ($role) {
            'guest', 'student' => 'guest',
            'senior'           => 'senior',
            default            => 'staff',
        };
    }

    public function dailyLimit(int $companyId, string $role): int
    {
        $settings = AiLabsSettings::forCompany($companyId);

        return match ($role) {
            'guest'       => (int) $settings->daily_limit_guest,
            'student'     => (int) $settings->daily_limit_student,
            default       => 100, // iç roller — senior/manager/admin_staff sabit 100
        };
    }

    public function remainingToday(int $companyId, string $role, int $userId, ?int $guestApplicationId = null): int
    {
        $limit = $this->dailyLimit($companyId, $role);
        if ($limit === 0) {
            return 999; // unlimited
        }
        $used = $this->dailyCount($role, $userId, $guestApplicationId);
        return max(0, $limit - $used);
    }

    /**
     * Son N soru/yanıt çiftini Gemini'nin beklediği formatta döndürür.
     * Konuşma sürekliliği için: "demin bahsettiğin", "devam edelim" gibi follow-up'lar çalışsın.
     *
     * @return array<int, array{role:string, content:string}>
     */
    private function recentHistory(string $role, int $userId, ?int $guestApplicationId, int $limit = 5): array
    {
        $rows = collect();

        if (in_array($role, ['guest', 'student'], true) && $guestApplicationId) {
            $rows = GuestAiConversation::query()
                ->where('guest_application_id', $guestApplicationId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['question', 'answer']);
        } elseif ($role === 'senior') {
            $rows = SeniorAiConversation::query()
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['question', 'answer']);
        } else {
            $rows = StaffAiConversation::query()
                ->withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('role', $role)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['question', 'answer']);
        }

        // Eski → yeni sırala, user/assistant olarak düzleştir
        $history = [];
        foreach ($rows->reverse() as $r) {
            if (!empty($r->question)) {
                $history[] = ['role' => 'user', 'content' => (string) $r->question];
            }
            if (!empty($r->answer)) {
                $history[] = ['role' => 'assistant', 'content' => (string) $r->answer];
            }
        }
        return $history;
    }

    private function dailyCount(string $role, int $userId, ?int $guestApplicationId): int
    {
        return match ($role) {
            'guest'   => $guestApplicationId ? GuestAiConversation::dailyCount($guestApplicationId) : 0,
            'student' => $guestApplicationId ? GuestAiConversation::dailyCount($guestApplicationId) : 0,
            'senior'  => SeniorAiConversation::dailyCount($userId),
            default   => StaffAiConversation::dailyCount($userId),
        };
    }

    private function saveConversation(
        int $companyId,
        string $role,
        int $userId,
        ?int $guestApplicationId,
        string $question,
        array $result,
        array $context
    ): ?\Illuminate\Database\Eloquent\Model {
        $answer = (string) ($result['content'] ?? '');
        $tokensIn = (int) ($result['tokens_input'] ?? 0);
        $tokensOut = (int) ($result['tokens_output'] ?? 0);
        $totalTokens = $tokensIn + $tokensOut;
        $citedSources = array_map('intval', $result['source_ids'] ?? []);

        $common = [
            'question'      => $question,
            'answer'        => $answer,
            'context'       => $context,
            'tokens_used'   => $totalTokens,
            'tokens_input'  => $tokensIn,
            'tokens_output' => $tokensOut,
            'response_mode' => $result['mode'] ?? null,
            'cited_sources' => $citedSources,
            'provider'      => 'gemini',
            'model'         => $result['model'] ?? null,
            'role'          => $role,
            'created_at'    => now(),
        ];

        if (in_array($role, ['guest', 'student'], true) && $guestApplicationId) {
            return GuestAiConversation::create(array_merge($common, [
                'guest_application_id' => $guestApplicationId,
            ]));
        }

        if ($role === 'senior') {
            return SeniorAiConversation::create(array_merge($common, [
                'user_id' => $userId,
            ]));
        }

        // manager / admin_staff / diğer iç roller
        return StaffAiConversation::create(array_merge($common, [
            'company_id' => $companyId,
            'user_id'    => $userId,
        ]));
    }
}
