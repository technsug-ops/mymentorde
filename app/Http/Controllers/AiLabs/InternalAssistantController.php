<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\SeniorAiConversation;
use App\Models\StaffAiConversation;
use App\Services\AiLabs\AiLabsAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Labs — iç roller (senior, manager, admin_staff) için ortak asistan controller.
 *
 * Role middleware'den çıkarılır. Her rol /senior/ai-assistant, /manager/ai-assistant,
 * /admin/ai-assistant şeklinde ayrı route'tan erişir ama aynı controller metodları çalışır.
 */
class InternalAssistantController extends Controller
{
    private const VALID_ROLES = ['senior', 'manager', 'admin_staff'];

    public function page(Request $request, string $role): View
    {
        $this->assertValidRole($role);
        return view('ai-labs.internal.assistant', [
            'role'          => $role,
            'roleLabel'     => $this->roleLabel($role),
            'portalLayout'  => $this->portalLayout($role),
        ]);
    }

    /**
     * SSE streaming — harfler gele gele yazılır.
     * Akış: stream chunks → son event'te mode/sources/conversation_id metadata.
     */
    public function askStream(Request $request, string $role): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->assertValidRole($role);

        $user = $request->user();
        $question = trim((string) $request->input('question', ''));
        $companyId = (int) ($user?->company_id ?? app('current_company_id') ?? 0);

        return response()->stream(function () use ($user, $role, $question, $companyId) {
            // PHP max_execution_time (default 30s) Gemini streaming için yetersiz —
            // uzun yanıtlarda 30s içinde bitmiyor ve curl WRITEFUNCTION timeout alıyor.
            @set_time_limit(120); // 2 dk — streaming response için yeterli
            @ignore_user_abort(true);

            $send = function (array $event) {
                echo "data: " . json_encode($event, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level() > 0) @ob_flush();
                flush();
            };

            if (!$user) { $send(['error' => 'auth']); return; }
            if (mb_strlen($question) < 3) { $send(['error' => 'Lütfen bir soru yaz.']); return; }
            if ($companyId === 0) { $send(['error' => 'no_company']); return; }

            // Limit kontrolü
            $assistant = app(AiLabsAssistantService::class);
            $remaining = $assistant->remainingToday($companyId, $role, (int) $user->id, null);
            if ($remaining <= 0) {
                $send(['error' => 'Günlük limit doldu.', 'limit_exceeded' => true]);
                return;
            }

            // Router + provider + context + history — manuel çağrı (streaming için)
            $router = app(\App\Services\AiLabs\ResponseRouter::class);
            $gemini = app(\App\Services\AiLabs\GeminiProvider::class);
            $kb     = app(\App\Services\AiLabs\KnowledgeBaseService::class);
            $prompt = app(\App\Services\AiLabs\SystemPromptBuilder::class);

            $settings = \App\Models\AiLabsSettings::forCompany($companyId);
            $brandName = (string) (\App\Models\MarketingAdminSetting::where('company_id', $companyId)
                ->where('setting_key', 'ai_labs_brand_name')->value('setting_value') ?: 'MentorDE AI Asistanı');

            $firstName = explode(' ', trim((string) ($user->name ?? '')))[0] ?? '';
            $userContext = ['first_name' => $firstName, 'full_name' => (string) ($user->name ?? ''), 'email' => (string) ($user->email ?? '')];

            $ctx = $kb->prepareContext($companyId, $role);
            $systemPrompt = $prompt->build(
                $role, $settings->default_mode, $brandName,
                $ctx['system_context'], $ctx['source_ids'] ?? [],
                (string) ($settings->admin_instructions ?? ''),
                $userContext
            );

            // Geçmişi de al
            $ref = new \ReflectionMethod($assistant, 'recentHistory');
            $ref->setAccessible(true);
            $history = $ref->invoke($assistant, $role, (int) $user->id, null, 5);

            // Stream başlat
            $send(['event' => 'start']);

            $fullText = '';
            $usage = ['input' => 0, 'output' => 0];
            $model = null;

            foreach ($gemini->streamChat($systemPrompt, $question, $ctx['file_refs'] ?? [],
                ['temperature' => 0.3, 'max_output_tokens' => 2048, 'history' => $history], $companyId) as $chunk) {
                if (!empty($chunk['error'])) {
                    $send(['error' => $chunk['error']]);
                    return;
                }
                if (!empty($chunk['text'])) {
                    $fullText .= $chunk['text'];
                    $send(['chunk' => $chunk['text']]);
                }
                if (!empty($chunk['done'])) {
                    $usage = $chunk['usage'] ?? $usage;
                    $model = $chunk['model'] ?? null;
                }
            }

            // Post-stream: parse meta, save conversation
            $meta = $prompt->parseResponseMeta($fullText);

            // Source validation
            if (!empty($meta['source_ids']) && !empty($ctx['source_ids'])) {
                $meta['source_ids'] = array_values(array_intersect($meta['source_ids'], $ctx['source_ids']));
            }
            if ($meta['mode'] === 'source' && empty($meta['source_ids'])) {
                $meta['mode'] = 'external';
            }

            // Sources metadata
            $sourcesMeta = [];
            if (!empty($meta['source_ids'])) {
                $sourcesMeta = \App\Models\KnowledgeSource::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->whereIn('id', $meta['source_ids'])
                    ->get(['id', 'title', 'type', 'url'])
                    ->map(fn ($s) => ['id' => (int)$s->id, 'title' => (string)$s->title, 'type' => (string)$s->type, 'url' => $s->url])
                    ->all();

                \App\Models\KnowledgeSource::query()
                    ->withoutGlobalScopes()
                    ->whereIn('id', $meta['source_ids'])
                    ->update(['citation_count' => \DB::raw('citation_count + 1'), 'last_used_at' => now()]);
            }

            // Conversation kaydet
            $saveRef = new \ReflectionMethod($assistant, 'saveConversation');
            $saveRef->setAccessible(true);
            $convResult = [
                'content' => $meta['content'],
                'mode' => $meta['mode'],
                'source_ids' => $meta['source_ids'],
                'tokens_input' => $usage['input'],
                'tokens_output' => $usage['output'],
                'model' => $model,
            ];
            $conversation = $saveRef->invoke($assistant, $companyId, $role, (int) $user->id, null, $question, $convResult, $userContext);

            // Final event — metadata
            $send([
                'done'              => true,
                'mode'              => $meta['mode'],
                'sources_meta'      => $sourcesMeta,
                'source_ids'        => $meta['source_ids'],
                'conversation_id'   => $conversation?->id,
                'conversation_type' => match ($role) {
                    'guest', 'student' => 'guest',
                    'senior'           => 'senior',
                    default            => 'staff',
                },
                'remaining'         => max(0, $remaining - 1),
                'tokens_input'      => $usage['input'],
                'tokens_output'     => $usage['output'],
                'clean_content'     => $meta['content'], // frontend'de meta-free metni göstersin
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function ask(Request $request, string $role, AiLabsAssistantService $assistant): JsonResponse
    {
        $this->assertValidRole($role);

        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'answer' => 'Oturum bulunamadı.'], 401);
        }

        $question = trim((string) $request->input('question', ''));
        if (mb_strlen($question) < 3) {
            return response()->json(['ok' => false, 'answer' => 'Lütfen bir soru yazın.'], 422);
        }

        $companyId = (int) ($user->company_id ?? app('current_company_id') ?? 0);
        if ($companyId === 0) {
            return response()->json(['ok' => false, 'answer' => 'Şirket bağlamı bulunamadı.'], 400);
        }

        $firstName = explode(' ', trim((string) ($user->name ?? '')))[0] ?? '';
        $result = $assistant->ask(
            $companyId,
            $role,
            (int) $user->id,
            null,
            $question,
            [
                'first_name' => $firstName,
                'full_name'  => (string) ($user->name ?? ''),
                'email'      => (string) ($user->email ?? ''),
            ]
        );

        return response()->json($result);
    }

    public function history(Request $request, string $role): JsonResponse
    {
        $this->assertValidRole($role);

        $user = $request->user();
        if (!$user) {
            return response()->json(['history' => []]);
        }

        if ($role === 'senior') {
            $rows = SeniorAiConversation::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'question', 'answer', 'response_mode', 'cited_sources', 'created_at']);
        } else {
            $rows = StaffAiConversation::query()
                ->withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('role', $role)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'question', 'answer', 'response_mode', 'cited_sources', 'created_at']);
        }

        return response()->json(['history' => $rows]);
    }

    public function remaining(Request $request, string $role, AiLabsAssistantService $assistant): JsonResponse
    {
        $this->assertValidRole($role);

        $user = $request->user();
        if (!$user) {
            return response()->json(['remaining' => 0, 'limit' => 0]);
        }

        $companyId = (int) ($user->company_id ?? app('current_company_id') ?? 0);

        return response()->json([
            'remaining' => $assistant->remainingToday($companyId, $role, (int) $user->id, null),
            'limit'     => $assistant->dailyLimit($companyId, $role),
        ]);
    }

    private function assertValidRole(string $role): void
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            abort(404, 'Geçersiz rol.');
        }
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'senior'      => 'Eğitim Danışmanı',
            'manager'     => 'Yönetici',
            'admin_staff' => 'Admin Personel',
            default       => ucfirst($role),
        };
    }

    private function portalLayout(string $role): string
    {
        return match ($role) {
            'senior'      => 'senior.layouts.app',
            'manager'     => 'manager.layouts.app',
            'admin_staff' => 'manager.layouts.app', // admin genelde manager layout kullanıyor
            default       => 'manager.layouts.app',
        };
    }
}
