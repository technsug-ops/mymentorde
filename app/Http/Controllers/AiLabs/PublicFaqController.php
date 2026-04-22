<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\AiLabsContentDraft;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public FAQ sayfası — /sss.
 *
 * Manager'ın "yayında" (status=published) işaretlediği FAQ draftlarını public olarak gösterir.
 * Schema.org FAQPage structured data ile SEO-uyumlu.
 */
class PublicFaqController extends Controller
{
    public function index(Request $request): View
    {
        // İlk aktif şirket (single-tenant deployment)
        $companyId = (int) (Company::query()->where('is_active', true)->orderBy('id')->value('id') ?? 0);

        $drafts = AiLabsContentDraft::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('template_code', 'faq')
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->get();

        // FAQ'ları flat liste + kategori grupları olarak hazırla
        $faqs = [];           // flat: {q, a, category, topic}
        $topics = [];         // [topic_title => [faq1, faq2]]

        foreach ($drafts as $d) {
            $meta = $d->metadata ?: [];
            $items = $meta['faqs'] ?? [];
            if (!is_array($items) || empty($items)) continue;

            $topicTitle = (string) $d->title;
            if (!isset($topics[$topicTitle])) {
                $topics[$topicTitle] = [];
            }

            foreach ($items as $item) {
                $q = trim((string) ($item['question'] ?? ''));
                $a = trim((string) ($item['answer'] ?? ''));
                if ($q === '' || $a === '') continue;

                $entry = [
                    'question' => $q,
                    'answer'   => $a,
                    'category' => (string) ($item['category'] ?? 'Genel'),
                    'topic'    => $topicTitle,
                ];
                $faqs[] = $entry;
                $topics[$topicTitle][] = $entry;
            }
        }

        // Boş topic'leri temizle
        $topics = array_filter($topics, fn ($items) => !empty($items));

        return view('ai-labs.public.faq', [
            'faqs'       => $faqs,
            'topics'     => $topics,
            'totalCount' => count($faqs),
        ]);
    }
}
