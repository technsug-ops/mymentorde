<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\CmsContent;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\MarketingAiConversation;
use App\Services\AiWritingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    public function __construct(private readonly AiWritingService $aiService)
    {
    }

    /**
     * 3.1 — CMS içerik draft oluştur.
     * POST /mktg-admin/ai/generate-draft
     */
    public function generateDraft(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'topic'           => 'required|string|max:500',
            'category'        => 'required|in:blog,city_guide,university_guide,success_story,faq',
            'target_audience' => 'nullable|string|max:200',
            'language'        => 'required|in:tr,de,en',
            'tone'            => 'nullable|in:professional,casual,inspiring',
        ]);

        if (! $this->aiService->isAvailable()) {
            return response()->json(['ok' => false, 'error' => 'AI servisi yapılandırılmamış.'], 503);
        }

        $prompt = "Şu konuda {$data['language']} dilinde bir {$data['category']} içeriği yaz: {$data['topic']}."
            .($data['target_audience'] ? " Hedef kitle: {$data['target_audience']}." : '')
            .($data['tone'] ?? null ? " Ton: {$data['tone']}." : '');

        $result = $this->aiService->improveGermanDocument('marketing_content', $prompt, $data);

        if (! $result['ok']) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'AI hatası.'], 500);
        }

        $langKey = $data['language'];
        $content = CmsContent::create([
            'type'                     => $data['category'],
            'category'                 => $data['category'],
            "title_{$langKey}"         => Str::limit($data['topic'], 180),
            "content_{$langKey}"       => $result['content'],
            'status'                   => 'draft',
            'created_by'               => auth()->id(),
            'target_audience'          => $data['target_audience'] ?? null,
        ]);

        $this->logConversation('content', $prompt, (string) $result['content'], (int) ($result['tokens'] ?? 0));

        return response()->json([
            'ok'         => true,
            'content_id' => $content->id,
            'preview'    => Str::limit($result['content'], 500),
        ]);
    }

    /**
     * 3.1 — Email konu satırı varyantları.
     * POST /mktg-admin/ai/subject-variants/{id}
     */
    public function subjectVariants(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);
        $currentSubject = (string) ($template->subject_tr ?? $template->name ?? '');

        if (! $this->aiService->isAvailable()) {
            return response()->json(['ok' => false, 'error' => 'AI servisi yapılandırılmamış.'], 503);
        }

        $prompt = "Bu email konu satırı için Türkçe 3 alternatif üret (farklı yaklaşım: merak, aciliyet, fayda). JSON array olarak dön — sadece string listesi:\n\"{$currentSubject}\"";
        $result = $this->aiService->improveGermanDocument('email_subject', $prompt, ['language' => 'tr']);

        $variants = [];
        if ($result['ok']) {
            // AI array JSON döndürdüyse parse et, yoksa satırlara böl
            $raw = trim($result['content']);
            $parsed = json_decode($raw, true);
            $variants = is_array($parsed) ? $parsed : array_filter(array_map('trim', explode("\n", $raw)));
            $this->logConversation('email', $prompt, $raw, (int) ($result['tokens'] ?? 0));
        }

        return response()->json(['ok' => $result['ok'], 'variants' => array_values($variants)]);
    }

    /**
     * 3.1 — Genel AI soru.
     * POST /mktg-admin/ai/ask
     */
    public function ask(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'question'     => 'required|string|max:2000',
            'context_type' => 'nullable|in:content,email,campaign,segment,social',
        ]);

        if (! $this->aiService->isAvailable()) {
            return response()->json(['ok' => false, 'error' => 'AI servisi yapılandırılmamış.'], 503);
        }

        $contextType = $data['context_type'] ?? 'campaign';
        $result = $this->aiService->improveGermanDocument('marketing_ask', $data['question'], ['context' => $contextType]);

        if (! $result['ok']) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'AI hatası.'], 500);
        }

        $this->logConversation($contextType, $data['question'], (string) $result['content'], (int) ($result['tokens'] ?? 0));

        return response()->json(['ok' => true, 'answer' => $result['content']]);
    }

    /**
     * 3.1 — Konuşma geçmişi.
     * GET /mktg-admin/ai/history
     */
    public function history(Request $request): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        $history = MarketingAiConversation::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(30);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $history]);
        }

        return view('marketing-admin.ai.history', compact('history'));
    }

    private function logConversation(string $contextType, string $question, string $answer, int $tokens): void
    {
        MarketingAiConversation::create([
            'user_id'      => auth()->id(),
            'context_type' => $contextType,
            'question'     => $question,
            'answer'       => $answer,
            'tokens_used'  => $tokens,
            'created_at'   => now(),
        ]);
    }
}
