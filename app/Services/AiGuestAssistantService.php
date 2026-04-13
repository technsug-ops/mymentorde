<?php

namespace App\Services;

use App\Models\GuestAiConversation;
use App\Models\GuestApplication;
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

        // Delegate to AiWritingService → Marketing Admin'de seçilen aktif provider kullanılır
        // (OpenAI / Anthropic / Gemini / OpenRouter). Key yönetimi tek yerden.
        $result = app(AiWritingService::class)->chat($systemPrompt, $question, 512);

        if (!$result['ok']) {
            Log::warning('AI Guest Assistant provider error', [
                'provider' => $result['provider'] ?? '-',
                'error'    => $result['error'] ?? 'unknown',
            ]);
            return $this->fallbackAnswer($question);
        }

        $answer = (string) ($result['content'] ?? '');
        if ($answer === '') {
            return $this->fallbackAnswer($question);
        }

        try {
            GuestAiConversation::create([
                'guest_application_id' => $guest->id,
                'question'             => $question,
                'answer'               => $answer,
                'context'              => $context ?: null,
                'tokens_used'          => (int) ($result['tokens_used'] ?? 0),
                'created_at'           => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI Guest Assistant conversation log failed', ['error' => $e->getMessage()]);
            // logging failure shouldn't break the response — devam
        }

        return ['ok' => true, 'answer' => $answer, 'remaining' => $remaining - 1];
    }

    private function buildSystemPrompt(GuestApplication $guest, array $context): string
    {
        $docsUploaded = (int) ($context['docs_uploaded'] ?? 0);
        $docsRequired = (int) ($context['docs_required'] ?? 1);
        $docsStatus   = ($docsUploaded >= $docsRequired)
            ? 'tamamlandı'
            : "{$docsUploaded}/{$docsRequired} yüklü";

        $progressPct = (int) ($context['progress_percent'] ?? 0);

        // NOT: HEREDOC içinde ternary operatörü ({$a ?: $b}) çalışmaz — PHP parse error verir.
        // Bu yüzden boş olabilecek alanları önce değişkene çıkarıp default'u burada uyguluyoruz.
        $applicationType  = $guest->application_type    ?: '-';
        $applicationCtry  = $guest->application_country ?: '-';
        $targetCity       = $guest->target_city         ?: '-';
        $languageLevel    = $guest->language_level      ?: '-';
        $packageTitle     = $guest->selected_package_title ?: 'seçilmedi';
        $contractStatus   = $guest->contract_status     ?: '-';

        return <<<PROMPT
Sen MentorDE eğitim danışmanlığı platformunun AI asistanısın.
Bu bir potansiyel müşteri (Guest) — henüz sözleşme imzalamamış.

Aday bilgileri:
- Başvuru tipi: {$applicationType}
- Hedef ülke: {$applicationCtry}
- Hedef şehir: {$targetCity}
- Dil seviyesi: {$languageLevel}
- Paket: {$packageTitle}
- Belge durumu: {$docsStatus}
- Sözleşme: {$contractStatus}
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
