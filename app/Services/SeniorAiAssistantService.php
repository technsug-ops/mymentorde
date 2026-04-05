<?php

namespace App\Services;

use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\SeniorAiConversation;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Senior AI Danışman Asistanı (Anthropic Claude API)
 *
 * Senior portalında danışmanlara yardım eder:
 *   - Öğrenciye özel bağlam (belge durumu, risk seviyesi, süreç aşaması)
 *   - Genel Almanya eğitim sistemi bilgisi
 *   - Yanıt taslakları, üniversite önerileri, süreç rehberliği
 *
 * Günlük limit: seniorlar için 50 soru/gün (sınır esnetilebilir).
 */
class SeniorAiAssistantService
{
    private const DAILY_LIMIT = 50;

    public function getDailyLimit(): int
    {
        return self::DAILY_LIMIT;
    }

    public function getRemainingToday(int $userId): int
    {
        $used = SeniorAiConversation::dailyCount($userId);
        return max(0, self::DAILY_LIMIT - $used);
    }

    /**
     * @param  User   $senior     Oturumdaki senior kullanıcı
     * @param  string $question   Senior'ın sorusu
     * @param  array  $context    ['student_id' => ..., 'student_name' => ..., ...]
     */
    public function ask(User $senior, string $question, array $context = []): array
    {
        $userId    = (int) $senior->id;
        $remaining = $this->getRemainingToday($userId);

        if ($remaining <= 0) {
            return [
                'ok'     => false,
                'answer' => 'Günlük 50 soru limitinize ulaştınız. Yarın tekrar kullanabilirsiniz.',
            ];
        }

        $studentId = (string) ($context['student_id'] ?? '');
        $studentContext = $studentId !== '' ? $this->loadStudentContext($studentId, $senior) : [];

        $systemPrompt = $this->buildSystemPrompt($senior, $studentContext);

        try {
            $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
            if (! $apiKey) {
                return $this->fallbackAnswer();
            }

            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 768,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $question],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('SeniorAiAssistant API error', ['status' => $response->status()]);
                return $this->fallbackAnswer();
            }

            $json       = $response->json();
            $answer     = $json['content'][0]['text'] ?? '';
            $tokensUsed = ($json['usage']['input_tokens'] ?? 0) + ($json['usage']['output_tokens'] ?? 0);

            SeniorAiConversation::create([
                'user_id'    => $userId,
                'student_id' => $studentId ?: null,
                'question'   => $question,
                'answer'     => $answer,
                'context'    => $studentContext ?: null,
                'tokens_used'=> $tokensUsed,
                'created_at' => now(),
            ]);

            return ['ok' => true, 'answer' => $answer, 'remaining' => $remaining - 1];

        } catch (\Throwable $e) {
            Log::error('SeniorAiAssistant exception', ['error' => $e->getMessage()]);
            return $this->fallbackAnswer();
        }
    }

    /**
     * Belirtilen öğrencinin bağlam verilerini yükle.
     * Senior'ın atanmış öğrencisi olup olmadığını doğrular.
     */
    private function loadStudentContext(string $studentId, User $senior): array
    {
        $seniorEmail = strtolower((string) ($senior->email ?? ''));

        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail])
            ->first();

        if (! $assignment) {
            return [];  // Bu öğrenci bu senior'a atanmamış — güvenlik
        }

        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->first([
                'id', 'first_name', 'last_name', 'application_type',
                'application_country', 'target_city', 'language_level',
                'contract_status', 'lead_status', 'selected_package_title',
                'docs_ready',
            ]);

        $approvedDocs = Document::query()
            ->where('student_id', $studentId)
            ->where('status', 'approved')
            ->count();

        $totalDocs = Document::query()
            ->where('student_id', $studentId)
            ->count();

        $rejectedDocs = Document::query()
            ->where('student_id', $studentId)
            ->where('status', 'rejected')
            ->count();

        return [
            'student_id'        => $studentId,
            'student_name'      => $guest ? trim($guest->first_name . ' ' . $guest->last_name) : $studentId,
            'application_type'  => $guest?->application_type ?? '—',
            'target_city'       => $guest?->target_city ?? '—',
            'language_level'    => $guest?->language_level ?? '—',
            'contract_status'   => $guest?->contract_status ?? '—',
            'lead_status'       => $guest?->lead_status ?? '—',
            'package'           => $guest?->selected_package_title ?? '—',
            'docs_approved'     => $approvedDocs,
            'docs_total'        => $totalDocs,
            'docs_rejected'     => $rejectedDocs,
            'risk_level'        => $assignment->risk_level ?? '—',
            'pipeline_stage'    => $assignment->pipeline_stage ?? '—',
            'branch'            => $assignment->branch ?? '—',
        ];
    }

    private function buildSystemPrompt(User $senior, array $ctx): string
    {
        $seniorName = (string) ($senior->name ?? 'Danışman');

        // Öğrenci bağlamı varsa ekle
        $studentBlock = '';
        if (! empty($ctx['student_id'])) {
            $studentBlock = <<<BLOCK

Şu an görüştüğün öğrenci ({$ctx['student_id']}):
- Ad: {$ctx['student_name']}
- Başvuru tipi: {$ctx['application_type']}
- Hedef şehir: {$ctx['target_city']}
- Dil seviyesi: {$ctx['language_level']}
- Sözleşme durumu: {$ctx['contract_status']}
- Süreç aşaması: {$ctx['lead_status']}
- Paket: {$ctx['package']}
- Belgeler: {$ctx['docs_approved']}/{$ctx['docs_total']} onaylı, {$ctx['docs_rejected']} reddedildi
- Risk seviyesi: {$ctx['risk_level']}
- Pipeline: {$ctx['pipeline_stage']}
- Branş: {$ctx['branch']}
BLOCK;
        }

        return <<<PROMPT
Sen MentorDE eğitim danışmanlığı platformunun Senior Danışman AI Asistanısın.
Şu an {$seniorName} ile çalışıyorsun — bu kişi deneyimli bir eğitim danışmanı.
{$studentBlock}

Rolün:
- Danışmana öğrencileriyle ilgili sorularında destek olmak
- Almanya eğitim sistemi (üniversiteler, Studienkolleg, vize, bloke hesap, tanıma vb.) hakkında güncel bilgi vermek
- Öğrenciye yazılacak e-posta veya mesaj taslakları hazırlamak
- Süreç adımları, eksik belgeler, üniversite seçimi konularında rehberlik etmek
- Risk analizi yaparak hangi öğrenciye öncelik verilmesi gerektiğini önermek

Kurallar:
- Türkçe yanıt ver, net ve profesyonel ton kullan
- Danışman perspektifinden cevap ver (öğrenciye değil, danışmana konuşuyorsun)
- Somut öneriler sun: "Şu belgeyi isteyin", "Bu üniversiteye başvurulabilir", "Vize için şu adımı takip edin"
- Yanıtı yapılandır: gerekirse madde listesi kullan
- Max 4 paragraf veya 8 madde ile kısa tut
- Kesin kabul garantisi verme
- Güncel olmayabilecek bilgiler için "konsolosluk/üniversite web sitesini kontrol edin" uyarısı ekle
PROMPT;
    }

    private function fallbackAnswer(): array
    {
        return [
            'ok'     => false,
            'answer' => 'AI asistanı şu anda kullanılamıyor. Lütfen birkaç dakika sonra tekrar deneyin.',
        ];
    }
}
