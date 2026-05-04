<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\TelegramMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TelegramAnalyticsController extends Controller
{
    private const TOPICS_META = [
        'vize' => ['Vize ve Sperrkonto', 'shield-check'],
        'dil' => ['Almanca Dil Sertifikaları', 'message-circle'],
        'randevu' => ['IDATA / Konsolosluk Randevu', 'calendar'],
        'denklik' => ['Diploma Denkliği', 'award'],
        'para' => ['Maliyet ve Para Transferi', 'wallet'],
        'sehir' => ['Şehir Seçimi', 'map-pin'],
        'master' => ['Yüksek Lisans / Master', 'graduation-cap'],
        'uni_assist' => ['Uni-Assist / VPD', 'file-check'],
        'studienkolleg' => ['Studienkolleg / FSP', 'book-open'],
        'yurt' => ['Konaklama / Yurt', 'home'],
        'is' => ['Çalışma / Öğrenci İşi', 'briefcase'],
        'sigorta' => ['Sağlık Sigortası', 'heart-pulse'],
        'anmeldung' => ['Anmeldung / Şehir Kaydı', 'clipboard-check'],
        'ausbildung' => ['Ausbildung Programları', 'tools'],
        'burs' => ['Burs İmkânları', 'star'],
        'aps' => ['APS Sertifikası', 'scroll'],
        'doktor_approbation' => ['Doktor: Approbation Süreci', 'stethoscope'],
        'doktor_fsp' => ['Doktor: FSP / Fachsprachprüfung', 'mic'],
        'doktor_hospitation_be' => ['Doktor: Hospitation / BE', 'hospital'],
        'doktor_kp_gutachten' => ['Doktor: KP / Gutachten', 'file-text'],
        'doktor_fachgebiet' => ['Doktor: Branş / Fachgebiet', 'activity'],
    ];

    public function index(Request $request): View
    {
        $sources = TelegramMessage::query()
            ->select('source', DB::raw('COUNT(*) as msg_count'))
            ->groupBy('source')
            ->orderByDesc('msg_count')
            ->get()
            ->map(fn ($r) => ['source' => $r->source, 'count' => (int) $r->msg_count])
            ->toArray();

        $totalCount = TelegramMessage::count();
        $minDate = TelegramMessage::min('sent_at');
        $maxDate = TelegramMessage::max('sent_at');

        return view('manager.telegram-analytics.index', [
            'sources'    => $sources,
            'totalCount' => $totalCount,
            'minDate'    => $minDate ? substr($minDate, 0, 10) : null,
            'maxDate'    => $maxDate ? substr($maxDate, 0, 10) : null,
            'topics'     => self::TOPICS_META,
        ]);
    }

    /**
     * AJAX: filtre uygulanmış stats + chart data döner.
     */
    public function stats(Request $request): JsonResponse
    {
        $q = $this->buildQuery($request);

        // Total stats
        $total = (clone $q)->count();
        $questions = (clone $q)->where('is_question', true)->count();
        $uniqueSenders = (clone $q)->distinct('sender_hash')->count('sender_hash');
        $sourceCount = (clone $q)->distinct('source')->count('source');
        $avgLen = (int) ((clone $q)->avg('text_len') ?? 0);

        // Monthly time series
        $monthly = (clone $q)
            ->select('month', DB::raw('COUNT(*) as c'))
            ->whereNotNull('month')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'count' => (int) $r->c])
            ->toArray();

        // Topic frequency — JSON_CONTAINS her topic için ayrı sorgu (basit ve cacheable)
        $topicCounts = [];
        foreach (array_keys(self::TOPICS_META) as $topic) {
            $topicCounts[$topic] = (clone $q)->whereJsonContains('topics', $topic)->count();
        }
        arsort($topicCounts);

        // Day-hour heatmap (7×24 grid)
        $heatmapRaw = (clone $q)
            ->select('dow', 'hour', DB::raw('COUNT(*) as c'))
            ->whereNotNull('dow')
            ->whereNotNull('hour')
            ->groupBy('dow', 'hour')
            ->get();
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($heatmapRaw as $r) {
            $heatmap[(int) $r->dow][(int) $r->hour] = (int) $r->c;
        }

        // Source dağılımı
        $sourceBreakdown = (clone $q)
            ->select('source', DB::raw('COUNT(*) as c'))
            ->groupBy('source')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => ['source' => $r->source, 'count' => (int) $r->c])
            ->toArray();

        return response()->json([
            'totals' => [
                'messages'      => $total,
                'questions'     => $questions,
                'unique_senders'=> $uniqueSenders,
                'sources'       => $sourceCount,
                'avg_length'    => $avgLen,
            ],
            'monthly'         => $monthly,
            'topics'          => $topicCounts,
            'heatmap'         => $heatmap,
            'source_breakdown'=> $sourceBreakdown,
        ]);
    }

    /**
     * AJAX: soru/mesaj search — filtreli, paginate.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $this->buildQuery($request);

        $term = trim((string) $request->input('term', ''));
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $q->where('text', 'LIKE', $like);
        }

        $onlyQuestions = $request->boolean('only_questions', true);
        if ($onlyQuestions) {
            $q->where('is_question', true);
        }

        $minLen = (int) $request->input('min_length', 30);
        if ($minLen > 0) $q->where('text_len', '>=', $minLen);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        $total = $q->count();
        $rows = $q->orderByDesc('sent_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get(['id', 'source', 'sent_at', 'sender_hash', 'text', 'is_question', 'topics']);

        return response()->json([
            'total' => $total,
            'page'  => $page,
            'per_page' => $perPage,
            'pages' => (int) ceil($total / $perPage),
            'rows'  => $rows->map(fn ($r) => [
                'id'           => $r->id,
                'source'       => $r->source,
                'sent_at'      => $r->sent_at ? $r->sent_at->format('Y-m-d H:i') : null,
                'sender'       => $r->sender_hash,
                'text'         => $r->text,
                'is_question'  => (bool) $r->is_question,
                'topics'       => $r->topics ?: [],
            ])->toArray(),
        ]);
    }

    /**
     * Manager kendi Telegram ChatExport ZIP'lerini yükleyebilsin diye.
     * ZIP içinden messages*.html'leri parse → DB'ye batch insert.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:zip|max:204800', // 200MB başına
        ]);

        $files = $request->file('files');
        $batchId = 'upload_' . date('YmdHis');
        $totalInserted = 0;

        foreach ($files as $f) {
            $label = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
            $tmpDir = storage_path('app/tmp_telegram_' . uniqid());
            @mkdir($tmpDir, 0755, true);

            try {
                $zip = new \ZipArchive();
                if ($zip->open($f->getRealPath()) !== true) {
                    return response()->json(['success' => false, 'error' => "ZIP açılamadı: {$label}"], 422);
                }
                $zip->extractTo($tmpDir);
                $zip->close();

                $htmlFiles = $this->findHtmlFiles($tmpDir);
                foreach ($htmlFiles as $hf) {
                    $msgs = $this->parseHtmlFile($hf, $label);
                    $totalInserted += $this->insertMessages($msgs, $batchId);
                }
            } finally {
                $this->rrmdir($tmpDir);
            }
        }

        return response()->json([
            'success'  => true,
            'inserted' => $totalInserted,
            'batch'    => $batchId,
        ]);
    }

    private function findHtmlFiles(string $dir): array
    {
        $out = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $name = $file->getFilename();
            if (str_starts_with($name, 'messages') && str_ends_with($name, '.html')) {
                $out[] = $file->getPathname();
            }
        }
        return $out;
    }

    private function parseHtmlFile(string $path, string $sourceLabel): array
    {
        $html = @file_get_contents($path);
        if (!$html) return [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $msgs = [];
        $lastSender = null;
        $nodes = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' message ')]");
        foreach ($nodes as $node) {
            $classAttr = $node->getAttribute('class') ?? '';
            if (str_contains($classAttr, 'service')) continue;

            $dateNode = $xpath->query(".//div[contains(@class,'date')]", $node)->item(0);
            $dateStr = $dateNode ? trim($dateNode->getAttribute('title') ?: '') : '';

            $fromNode = $xpath->query(".//div[contains(@class,'from_name')]", $node)->item(0);
            $sender = $fromNode ? trim($fromNode->textContent) : ($lastSender ?? '(bilinmiyor)');
            if ($fromNode) $lastSender = $sender;

            $textNode = $xpath->query(".//div[contains(@class,'text')]", $node)->item(0);
            $text = $textNode ? preg_replace('/\s+/', ' ', trim($textNode->textContent)) : '';

            if ($text === '') continue;

            $cleanText = $this->stripPii($text);
            if ($cleanText === '') continue;

            $msgs[] = [
                'source'      => $sourceLabel,
                'sender'      => $this->anonSender($sender),
                'date'        => $dateStr,
                'text'        => $cleanText,
                'is_question' => (bool) preg_match('/\?\s*$/', $cleanText),
                'is_short'    => (bool) preg_match('/^(merhaba|selam|teşekkür|sağol|tşk|merhabalar|selamlar)$/iu', trim($cleanText)),
            ];
        }
        return $msgs;
    }

    private function anonSender(string $name): string
    {
        $name = trim($name);
        $low = mb_strtolower($name);
        if ($name === '' || $low === 'deleted account' || $low === '(bilinmiyor)') return 'anon_deleted';
        return 'user_' . substr(sha1($name), 0, 8);
    }

    private function stripPii(string $text): string
    {
        $text = preg_replace('/@\w+/', '@user', $text);
        $text = preg_replace('/\b[\w.+-]+@[\w-]+\.[\w.-]+\b/', '***@***', $text);
        $text = preg_replace('/\+?\d[\d\s\-\(\)]{9,14}\d/', '***', $text);
        return trim($text);
    }

    private function insertMessages(array $msgs, string $batchId): int
    {
        if (empty($msgs)) return 0;
        $rows = [];
        $now = now();
        foreach ($msgs as $m) {
            $sentAt = $this->parseDate($m['date'] ?? '');
            $text = $m['text'];
            $rows[] = [
                'source'         => substr($m['source'], 0, 120),
                'sender_hash'    => substr($m['sender'], 0, 32),
                'sent_at'        => $sentAt?->format('Y-m-d H:i:s'),
                'text'           => $text,
                'is_question'    => (bool) ($m['is_question'] ?? false),
                'is_short'       => (bool) ($m['is_short'] ?? false),
                'text_len'       => min(65535, mb_strlen($text)),
                'year'           => $sentAt?->year,
                'month'          => $sentAt ? $sentAt->format('Y-m') : null,
                'dow'            => $sentAt?->dayOfWeek,
                'hour'           => $sentAt?->hour,
                'topics'         => json_encode($this->detectTopicsFromText($text), JSON_UNESCAPED_UNICODE),
                'imported_batch' => $batchId,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        // Chunk insert
        $inserted = 0;
        foreach (array_chunk($rows, 1000) as $chunk) {
            \DB::table('telegram_messages')->insert($chunk);
            $inserted += count($chunk);
        }
        return $inserted;
    }

    private function parseDate(string $s): ?\Carbon\Carbon
    {
        if (strlen($s) < 10) return null;
        try {
            return \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', substr($s, 0, 19));
        } catch (\Throwable) {
            return null;
        }
    }

    private function detectTopicsFromText(string $text): array
    {
        // Aynı pattern'leri import command'la senkron tutmak için array hardcode
        static $patterns = null;
        if ($patterns === null) {
            $patterns = [
                'vize' => '/\b(vize|visa|sperrkonto|sperrkont|bloke|elcilik|konsolosluk|videx)\b/i',
                'uni_assist' => '/\b(uni[- ]?assist|uniassist|vpd|hzb)\b/i',
                'aps' => '/\baps\b/i',
                'anmeldung' => '/\b(anmeldung|anmelden|wohnsitz)\b/i',
                'dil' => '/\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b/i',
                'yurt' => '/\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b/i',
                'sigorta' => '/\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer)\b/i',
                'burs' => '/\b(burs|stipendium|daad|scholarship)\b/i',
                'para' => '/\b(para|butce|euro|tl|maddi|harclik|gecim)\b/i',
                'is' => '/\b(is|minijob|werkstudent|calis|part time)\b/i',
                'studienkolleg' => '/\b(studienkolleg|studkol|hazirlik|feststellungspruefung)\b/i',
                'master' => '/\b(master|yuksek lisans)\b/i',
                'ausbildung' => '/\b(ausbildung|meslek egitim)\b/i',
                'denklik' => '/\b(denklik|taninma|recognition|anerkennung|zeugnis)\b/i',
                'randevu' => '/\b(randevu|appointment|termin|idata)\b/i',
                'sehir' => '/\b(berlin|munih|munich|munchen|hamburg|frankfurt|koln|stuttgart|leipzig|dresden|heidelberg|freiburg)\b/i',
                'doktor_approbation' => '/\b(approbation|approbat|appro\b|approbasyon)\b/i',
                'doktor_fsp' => '/\b(fsp|fachsprach|fachsprache|telc med|telc-med)\b/i',
                'doktor_hospitation_be' => '/\b(hospitation|hospitasyon|hospitati|berufserlaubnis|be\b)\b/i',
                'doktor_kp_gutachten' => '/\b(kenntnispr|gutachten|kp\b)\b/i',
                'doktor_fachgebiet' => '/\b(asistan|fachgebiet|fachartz|cerrahi|psikiyatri|pediatri|kardio|nefro|onkoloji|jinekoloji|noroloji|patoloji|radyoloji|anestezi|dermatoloji|uroloji|ortopedi|aile hekimli|tukmos|stellenangebot|stellen)\b/i',
            ];
        }
        $hits = [];
        foreach ($patterns as $topic => $pat) {
            if (preg_match($pat, $text)) $hits[] = $topic;
        }
        return $hits;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    private function buildQuery(Request $request)
    {
        $q = TelegramMessage::query();

        $from = (string) $request->input('from', '');
        $to   = (string) $request->input('to', '');
        if ($from) $q->where('sent_at', '>=', $from . ' 00:00:00');
        if ($to)   $q->where('sent_at', '<=', $to . ' 23:59:59');

        $sources = (array) $request->input('sources', []);
        if (!empty($sources)) {
            $q->whereIn('source', $sources);
        }

        $topic = (string) $request->input('topic', '');
        if ($topic !== '' && $topic !== 'all') {
            $q->whereJsonContains('topics', $topic);
        }

        return $q;
    }
}
