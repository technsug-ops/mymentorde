<?php

namespace App\Services;

use App\Models\GuestAiConversation;
use App\Models\GuestApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * K3 — Guest AI Başvuru Asistanı (Anthropic Claude API)
 *
 * Paket bazlı günlük limit:
 *   pkg_basic: 5/gün, pkg_plus: 10/gün, pkg_premium: sınırsız, seçilmemiş: 3/gün
 */
class AiGuestAssistantService
{
    private const PACKAGE_LIMITS = [
        'pkg_basic'   => 5,
        'pkg_plus'    => 10,
        'pkg_premium' => 999,
    ];

    private const DEFAULT_LIMIT = 3;

    public function getDailyLimit(?GuestApplication $guest): int
    {
        $pkg = $guest?->selected_package_code ?? '';
        return self::PACKAGE_LIMITS[$pkg] ?? self::DEFAULT_LIMIT;
    }

    public function getRemainingToday(?GuestApplication $guest): int
    {
        if (!$guest) {
            return 0;
        }
        $used  = GuestAiConversation::dailyCount($guest->id);
        $limit = $this->getDailyLimit($guest);
        return max(0, $limit - $used);
    }

    public function ask(GuestApplication $guest, string $question, array $context = []): array
    {
        $remaining = $this->getRemainingToday($guest);
        if ($remaining <= 0) {
            return [
                'ok'     => false,
                'answer' => 'Günlük soru limitinize ulaştınız. Yarın tekrar deneyin veya paketinizi yükseltin.',
            ];
        }

        $systemPrompt = $this->buildSystemPrompt($guest, $context);

        try {
            $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
            if (!$apiKey) {
                return $this->fallbackAnswer($question);
            }

            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 512,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $question],
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('AI Guest Assistant API error', ['status' => $response->status()]);
                return $this->fallbackAnswer($question);
            }

            $json        = $response->json();
            $answer      = $json['content'][0]['text'] ?? '';
            $tokensUsed  = $json['usage']['input_tokens'] + $json['usage']['output_tokens'] ?? 0;

            GuestAiConversation::create([
                'guest_application_id' => $guest->id,
                'question'             => $question,
                'answer'               => $answer,
                'context'              => $context ?: null,
                'tokens_used'          => $tokensUsed,
                'created_at'           => now(),
            ]);

            return ['ok' => true, 'answer' => $answer, 'remaining' => $remaining - 1];

        } catch (\Throwable $e) {
            Log::error('AI Guest Assistant exception', ['error' => $e->getMessage()]);
            return $this->fallbackAnswer($question);
        }
    }

    private function buildSystemPrompt(GuestApplication $guest, array $context): string
    {
        $docsUploaded = (int) ($context['docs_uploaded'] ?? 0);
        $docsRequired = (int) ($context['docs_required'] ?? 1);
        $docsStatus   = ($docsUploaded >= $docsRequired)
            ? 'tamamlandı'
            : "{$docsUploaded}/{$docsRequired} yüklü";

        $progressPct = (int) ($context['progress_percent'] ?? 0);

        return <<<PROMPT
Sen MentorDE eğitim danışmanlığı platformunun AI asistanısın.
Bu bir potansiyel müşteri (Guest) — henüz sözleşme imzalamamış.

Aday bilgileri:
- Başvuru tipi: {$guest->application_type}
- Hedef ülke: {$guest->application_country}
- Hedef şehir: {$guest->target_city}
- Dil seviyesi: {$guest->language_level}
- Paket: {$guest->selected_package_title ?: 'seçilmedi'}
- Belge durumu: {$docsStatus}
- Sözleşme: {$guest->contract_status}
- İlerleme: %{$progressPct}

Kurallar:
- Türkçe yanıt ver, samimi ama profesyonel ton
- Kısa ve net cevap ver (max 3 paragraf)
- Somut aksiyon öner ("Belgeler sayfasına gidin", "Plus paketi size uygun olabilir")
- Almanya eğitim sistemi hakkında doğru bilgi ver
- Hukuki taahhütte bulunma — "kesin kabul alırsınız" deme
- Vize konusunda "konsolosluk kararını biz belirleyemeyiz" uyarısı ver
- Paket satışını nazikçe öner ama baskıcı olma
- "Daha detaylı bilgi için danışmanınıza yazın" yönlendirmesi ekle
PROMPT;
    }

    private function fallbackAnswer(string $question): array
    {
        return [
            'ok'     => false,
            'answer' => 'AI asistanı şu anda kullanılamıyor. Lütfen danışmanınıza mesaj gönderin veya bilet açın.',
        ];
    }
}
