<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Services\AiLabs\AnalyticsService;
use App\Services\AiLabs\ContentTemplates;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerAiLabsAnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        $this->ensureAdmin($request);

        $cid = $this->companyId();
        $data = $analytics->monthly($cid);

        return view('ai-labs.manager.analytics.index', array_merge($data, [
            'templates' => ContentTemplates::all(),
        ]));
    }

    /**
     * Tek bir aday (guest_application) için AI Intelligence detayı:
     * timeline + konu dağılımı + insight.
     */
    public function lead(Request $request, AnalyticsService $analytics, int $leadId): \Illuminate\View\View
    {
        $this->ensureAdmin($request);

        $cid = $this->companyId();
        $lead = \App\Models\GuestApplication::where('id', $leadId)
            ->where('company_id', $cid)
            ->firstOrFail();

        $conversations = \App\Models\GuestAiConversation::where('guest_application_id', $leadId)
            ->orderByDesc('created_at')
            ->get();

        $questions = $conversations->pluck('question')->all();
        $topics = $analytics->categorizeQuestions($questions);

        // Feedback'leri join et
        $feedbacks = \App\Models\AiLabsFeedback::withoutGlobalScopes()
            ->where('guest_application_id', $leadId)
            ->where('conversation_type', 'guest')
            ->get()
            ->keyBy('conversation_id');

        $conversations = $conversations->map(function ($c) use ($feedbacks) {
            $fb = $feedbacks->get($c->id);
            $c->feedback_rating = $fb?->rating;
            $c->feedback_reason = $fb?->reason;
            return $c;
        });

        return view('ai-labs.manager.analytics.lead', [
            'lead'          => $lead,
            'conversations' => $conversations,
            'topics'        => $topics,
            'total_tokens'  => $conversations->sum(fn ($c) => (int) $c->tokens_input + (int) $c->tokens_output),
        ]);
    }

    /**
     * FAQ adaylarını CSV olarak indir — kaynak olarak içerik üretmek için.
     * Columns: count, sample_question, roles, last_asked, intent_key
     */
    public function faqCsv(Request $request, AnalyticsService $analytics): StreamedResponse
    {
        $this->ensureAdmin($request);

        $cid = $this->companyId();
        $days = max(7, min(365, (int) $request->input('days', 60)));
        $minOcc = max(1, min(20, (int) $request->input('min', 2)));

        $candidates = $analytics->faqCandidates($cid, $days, $minOcc, 500);
        $filename = 'faq-candidates-' . now()->format('Y-m-d_His') . '.csv';

        return new StreamedResponse(function () use ($candidates) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM — Excel Türkçe karakterleri düzgün gösterir
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['count', 'sample_question', 'roles', 'last_asked', 'intent_key']);

            foreach ($candidates as $c) {
                $roles = '';
                foreach (($c['roles'] ?? []) as $role => $n) {
                    $roles .= "{$role}:{$n} ";
                }
                fputcsv($out, [
                    $c['count'] ?? 0,
                    $c['sample_question'] ?? '',
                    trim($roles),
                    $c['last_asked'] ? \Carbon\Carbon::parse($c['last_asked'])->toIso8601String() : '',
                    $c['intent_key'] ?? '',
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403, 'AI Labs analytics sadece yöneticilere açıktır.');
        }
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
