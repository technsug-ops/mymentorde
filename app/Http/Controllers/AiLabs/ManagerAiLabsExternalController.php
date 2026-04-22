<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeSource;
use App\Services\AiLabs\ExternalSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Labs — dış kaynak keşif paneli.
 *
 * Route: /manager/ai-labs/external
 *
 * Manager burada:
 *   - Wikipedia makalesi ara → import et
 *   - RSS feed URL ver → items listele → import et
 *   - Web'de ara (Serper) → sonuç seç → import et
 *
 * Her import knowledge_sources tablosuna yeni satır olarak yazılır.
 */
class ManagerAiLabsExternalController extends Controller
{
    public function index(Request $request, ExternalSourceService $ext): View
    {
        return view('ai-labs.manager.external.index', [
            'webSearchEnabled' => $ext->isWebSearchConfigured($this->companyId()),
        ]);
    }

    public function searchWikipedia(Request $request, ExternalSourceService $ext): JsonResponse
    {
        $data = $request->validate([
            'q'    => 'required|string|max:200',
            'lang' => 'nullable|in:tr,en,de',
        ]);

        $result = $ext->searchWikipedia($data['q'], $data['lang'] ?? 'tr', 8);
        return response()->json($result);
    }

    public function parseRss(Request $request, ExternalSourceService $ext): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|url|max:500',
        ]);

        $result = $ext->parseRss($data['url'], 20);
        return response()->json($result);
    }

    public function searchWeb(Request $request, ExternalSourceService $ext): JsonResponse
    {
        $data = $request->validate([
            'q'    => 'required|string|max:200',
            'lang' => 'nullable|in:tr,en,de',
        ]);

        $result = $ext->searchWeb($data['q'], $this->companyId(), 10, $data['lang'] ?? 'tr');
        return response()->json($result);
    }

    public function import(Request $request, ExternalSourceService $ext): RedirectResponse
    {
        $data = $request->validate([
            'source_type'        => 'required|in:wikipedia,rss,web',
            'title'              => 'required|string|max:200',
            'url'                => 'nullable|url|max:500',
            'wikipedia_title'    => 'nullable|string|max:200',
            'wikipedia_lang'     => 'nullable|in:tr,en,de',
            'category'           => 'nullable|string|max:80',
            'visible_to_roles'   => 'required|array|min:1',
            'visible_to_roles.*' => 'in:guest,student,senior,manager,admin_staff',
        ]);

        $prep = $ext->prepareForImport(
            $data['source_type'],
            $data['source_type'] === 'wikipedia'
                ? ['title' => $data['wikipedia_title'] ?? $data['title'], 'lang' => $data['wikipedia_lang'] ?? 'tr']
                : ['url' => $data['url'] ?? '', 'title' => $data['title']]
        );

        if (!($prep['ok'] ?? false)) {
            return back()->with('status', '⚠️ İçerik alınamadı: ' . ($prep['error'] ?? 'unknown'));
        }

        $roles = array_values($data['visible_to_roles']);
        $hasG = in_array('guest', $roles, true);
        $hasS = in_array('student', $roles, true);
        $targetAudience = match (true) {
            $hasG && $hasS => 'both',
            $hasG          => 'guest',
            $hasS          => 'student',
            default        => 'both',
        };

        $content = (string) ($prep['extract'] ?? '');
        $sourceType = $data['source_type'] === 'wikipedia' ? 'url' : 'url'; // external hep URL tipi
        $category = $data['category'] ?: ($data['source_type'] === 'wikipedia' ? 'wikipedia' : 'external');

        KnowledgeSource::create([
            'company_id'         => $this->companyId(),
            'title'              => $data['title'],
            'type'               => $sourceType,
            'category'           => $category,
            'target_audience'    => $targetAudience,
            'visible_to_roles'   => $roles,
            'url'                => (string) ($prep['url'] ?? $data['url'] ?? ''),
            'content_markdown'   => $content,
            'content_hash'       => hash('sha256', $content),
            'is_active'          => true,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('manager.ai-labs.sources')
            ->with('status', "✅ Dış kaynak eklendi: '{$data['title']}' (" . number_format(strlen($content)/1024, 1) . " KB).");
    }

    /**
     * Toplu import — birden fazla sonucu tek seferde bilgi havuzuna ekler.
     * Her item kendi başlığı/URL'i ile gelir; kategori ve roller paylaşılır.
     */
    public function importBulk(Request $request, ExternalSourceService $ext): RedirectResponse
    {
        // 20 URL × ~5sn fetch = 100sn; güvenli limit
        set_time_limit(240);
        @ini_set('max_execution_time', '240');

        $data = $request->validate([
            'source_type'        => 'required|in:wikipedia,rss,web',
            'items'              => 'required|array|min:1|max:20',
            'items.*.title'      => 'required|string|max:200',
            'items.*.url'        => 'nullable|url|max:500',
            'items.*.wiki_title' => 'nullable|string|max:200',
            'items.*.wiki_lang'  => 'nullable|in:tr,en,de',
            'category'           => 'nullable|string|max:80',
            'visible_to_roles'   => 'required|array|min:1',
            'visible_to_roles.*' => 'in:guest,student,senior,manager,admin_staff',
        ]);

        $roles = array_values($data['visible_to_roles']);
        $hasG = in_array('guest', $roles, true);
        $hasS = in_array('student', $roles, true);
        $targetAudience = match (true) {
            $hasG && $hasS => 'both',
            $hasG          => 'guest',
            $hasS          => 'student',
            default        => 'both',
        };

        $cid = $this->companyId();
        $category = $data['category'] ?: ($data['source_type'] === 'wikipedia' ? 'wikipedia' : 'external');

        $ok = 0;
        $fail = 0;
        $errors = [];

        foreach ($data['items'] as $item) {
            $prep = $ext->prepareForImport(
                $data['source_type'],
                $data['source_type'] === 'wikipedia'
                    ? ['title' => $item['wiki_title'] ?? $item['title'], 'lang' => $item['wiki_lang'] ?? 'tr']
                    : ['url' => $item['url'] ?? '', 'title' => $item['title']]
            );

            if (!($prep['ok'] ?? false)) {
                $fail++;
                $errors[] = "'{$item['title']}': " . ($prep['error'] ?? 'fetch_failed');
                continue;
            }

            $content = (string) ($prep['extract'] ?? '');

            KnowledgeSource::create([
                'company_id'         => $cid,
                'title'              => $item['title'],
                'type'               => 'url',
                'category'           => $category,
                'target_audience'    => $targetAudience,
                'visible_to_roles'   => $roles,
                'url'                => (string) ($prep['url'] ?? $item['url'] ?? ''),
                'content_markdown'   => $content,
                'content_hash'       => hash('sha256', $content),
                'is_active'          => true,
                'created_by_user_id' => auth()->id(),
            ]);
            $ok++;
        }

        $msg = "✅ {$ok} kaynak eklendi";
        if ($fail > 0) {
            $msg .= " · ⚠️ {$fail} başarısız";
            $firstErr = $errors[0] ?? '';
            if ($firstErr) $msg .= " (" . \Illuminate\Support\Str::limit($firstErr, 80) . ")";
        }

        return redirect()->route('manager.ai-labs.sources')->with('status', $msg);
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
