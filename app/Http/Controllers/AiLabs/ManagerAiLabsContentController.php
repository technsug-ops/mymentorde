<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\AiLabsContentDraft;
use App\Services\AiLabs\ContentGeneratorService;
use App\Services\AiLabs\ContentTemplates;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * AI Labs içerik üretici — manager yönetim paneli.
 */
class ManagerAiLabsContentController extends Controller
{
    public function index(Request $request): View
    {
        $cid = $this->companyId();

        $drafts = AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('ai-labs.manager.content.index', [
            'drafts'    => $drafts,
            'templates' => ContentTemplates::all(),
        ]);
    }

    public function newForm(Request $request, string $template): View
    {
        $tpl = ContentTemplates::find($template);
        if (!$tpl) {
            abort(404, 'Template bulunamadı.');
        }

        return view('ai-labs.manager.content.new', [
            'templateCode' => $template,
            'template'     => $tpl,
        ]);
    }

    public function generate(Request $request, string $template, ContentGeneratorService $gen): RedirectResponse
    {
        // Uzun içerik üretimi (blog, uni-rec) 30sn aşabilir + retry payı
        set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $tpl = ContentTemplates::find($template);
        if (!$tpl) {
            abort(404, 'Template bulunamadı.');
        }

        // Field validation — her template'in kendi required'ları
        $rules = ['title' => 'required|string|max:300'];
        foreach ($tpl['fields'] ?? [] as $key => $field) {
            $r = [];
            if ($field['required'] ?? false) $r[] = 'required';
            else $r[] = 'nullable';

            $type = $field['type'] ?? 'text';
            if ($type === 'number')     $r[] = 'numeric';
            elseif ($type === 'date')   $r[] = 'date';
            else                        $r[] = 'string|max:3000';

            $rules["fields.{$key}"] = implode('|', $r);
        }
        $data = $request->validate($rules);

        $cid = $this->companyId();
        $userId = (int) (auth()->id() ?? 0);

        $result = $gen->generate(
            $cid,
            $userId,
            $template,
            (string) $data['title'],
            (array) ($request->input('fields') ?? [])
        );

        if (!($result['ok'] ?? false)) {
            $err = (string) ($result['error'] ?? 'unknown');
            $friendly = $this->humanizeError($err);
            return back()->withInput()->with('status', '⚠️ ' . $friendly);
        }

        return redirect()
            ->route('manager.ai-labs.content.edit', ['draft' => $result['draft']->id])
            ->with('status', '✅ İçerik başarıyla üretildi.');
    }

    public function edit(Request $request, int $draft): View
    {
        $cid = $this->companyId();
        $row = AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('id', $draft)
            ->where('company_id', $cid)
            ->firstOrFail();

        $template = ContentTemplates::find($row->template_code);

        return view('ai-labs.manager.content.edit', [
            'draft'    => $row,
            'template' => $template,
        ]);
    }

    public function update(Request $request, int $draft): RedirectResponse
    {
        $cid = $this->companyId();
        $row = AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('id', $draft)
            ->where('company_id', $cid)
            ->firstOrFail();

        $data = $request->validate([
            'title'   => 'required|string|max:300',
            'content' => 'required|string|max:100000',
            'status'  => 'required|in:draft,published,archived',
        ]);

        $row->update($data);

        return back()->with('status', '💾 Kaydedildi.');
    }

    public function destroy(Request $request, int $draft): RedirectResponse
    {
        $cid = $this->companyId();
        AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('id', $draft)
            ->where('company_id', $cid)
            ->delete();

        return redirect()->route('manager.ai-labs.content.index')->with('status', '🗑 Draft silindi.');
    }

    // ── Export ────────────────────────────────────────────────────────────

    /**
     * SEO anahtar kelime önerisi — blog template form'undan AJAX çağrılır.
     */
    public function suggestKeywords(Request $request, ContentGeneratorService $gen): JsonResponse
    {
        set_time_limit(120);
        @ini_set('max_execution_time', '120');

        $data = $request->validate([
            'topic'    => 'required|string|min:3|max:200',
            'audience' => 'nullable|string|max:50',
            'language' => 'nullable|in:tr,en,de',
        ]);

        $result = $gen->suggestKeywords(
            $this->companyId(),
            $data['topic'],
            $data['audience'] ?? 'prospective_students',
            $data['language'] ?? 'tr'
        );

        return response()->json($result);
    }

    public function exportMarkdown(Request $request, int $draft): Response
    {
        $row = $this->findDraft($draft);
        $filename = $this->safeFilename($row->title) . '.md';
        return response($row->content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request, int $draft): Response
    {
        $row = $this->findDraft($draft);
        $html = $this->markdownToHtml($row->content);

        $pdf = Pdf::loadView('ai-labs.manager.content.pdf', [
            'title'   => $row->title,
            'content' => $html,
        ])->setPaper('a4');

        return $pdf->download($this->safeFilename($row->title) . '.pdf');
    }

    public function exportDocx(Request $request, int $draft): Response
    {
        $row = $this->findDraft($draft);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle($row->title, 1);
        $section->addTextBreak();

        // Basit: her satırı ayrı paragraf
        foreach (explode("\n", $row->content) as $line) {
            $line = trim($line);
            if ($line === '') {
                $section->addTextBreak();
                continue;
            }
            // H2/H3 markdown yakala
            if (str_starts_with($line, '### ')) {
                $section->addTitle(substr($line, 4), 3);
            } elseif (str_starts_with($line, '## ')) {
                $section->addTitle(substr($line, 3), 2);
            } elseif (str_starts_with($line, '# ')) {
                $section->addTitle(substr($line, 2), 1);
            } elseif (str_starts_with($line, '- ') || str_starts_with($line, '* ')) {
                $section->addListItem(substr($line, 2), 0);
            } else {
                $section->addText($line);
            }
        }

        $filename = $this->safeFilename($row->title) . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function findDraft(int $id): AiLabsContentDraft
    {
        return AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $this->companyId())
            ->firstOrFail();
    }

    private function safeFilename(string $title): string
    {
        $slug = preg_replace('/[^A-Za-z0-9\-_]+/', '_', $title);
        $slug = trim($slug, '_');
        return substr($slug ?: 'draft', 0, 80);
    }

    /**
     * Gemini API hatalarını kullanıcı dostu mesajlara çevirir.
     */
    private function humanizeError(string $err): string
    {
        if (str_contains($err, 'http_429') || str_contains($err, 'quota')) {
            return 'Günlük Gemini API kotası aşıldı. Ücretsiz tier: 250 istek/gün. '
                . 'Artırmak için Google AI Studio\'da billing ekle (ilk 1500 istek ücretsiz, sonrası ~$0.30/1M token) '
                . 'veya yarın reset olana kadar bekle.';
        }
        if (str_contains($err, 'http_401') || str_contains($err, 'http_403')) {
            return 'Gemini API anahtarı geçersiz veya yetkisi yok. AI Labs Ayarlar\'dan kontrol et.';
        }
        if (str_contains($err, 'http_503') || str_contains($err, 'UNAVAILABLE')) {
            return 'Gemini şu an yoğun (503). Birkaç dakika sonra tekrar dene.';
        }
        if (str_contains($err, 'http_400')) {
            return 'Gemini API isteği reddetti — muhtemelen prompt çok uzun veya geçersiz. Girdileri kısalt.';
        }
        if (str_contains($err, 'no_api_key')) {
            return 'Gemini API anahtarı tanımlı değil. AI Labs Ayarlar\'dan ekle.';
        }
        // Diğer durumlar için kısa gösterim
        return 'İçerik üretilemedi. Hata: ' . mb_substr($err, 0, 150);
    }

    /** Minimal markdown → HTML (PDF export için). */
    private function markdownToHtml(string $md): string
    {
        // Metadata bloğunu çıkar
        $md = preg_replace('/^---\s*(.+?)\s*---\s*/s', '', $md);

        $html = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
        // Headings
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        // Lists
        $html = preg_replace('/^[\-\*] (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*?<\/li>\n?)+/s', '<ul>$0</ul>', $html);
        // Paragraphs (boş satır ayırıcı)
        $html = preg_replace('/\n{2,}/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';
        $html = preg_replace('/<p>(\s*<(?:h[1-3]|ul))/', '$1', $html);
        $html = preg_replace('/(<\/(?:h[1-3]|ul)>)\s*<\/p>/', '$1', $html);

        return $html;
    }
}
