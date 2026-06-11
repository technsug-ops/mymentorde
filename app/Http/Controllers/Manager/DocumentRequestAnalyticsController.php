<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Support\ModuleAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manager — Belge Talep Analytics Dashboard.
 *
 * doc_request modülü (premium) için KPI + funnel + hatırlatma etkisi raporları.
 * Tüm sorgular company_id ile scope'lanır (BelongsToCompany trait token model'de
 * otomatik global scope ekler — explicit filtre cache key tutarlılığı için).
 *
 * Cache: 5 dk (manager dashboard tarzı), filtre hash'i + company_id ile per-tenant.
 */
class DocumentRequestAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeUse($request);

        [$start, $end, $filters] = $this->resolveFilters($request);
        $companyId = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);

        $cacheKey = sprintf(
            'doc_request_analytics_%d_%s',
            $companyId,
            md5(json_encode($filters))
        );

        $data = Cache::remember($cacheKey, 300, function () use ($start, $end, $filters) {
            return $this->buildAnalytics($start, $end, $filters);
        });

        $categories = DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('top_category_code')
            ->orderBy('sort_order')
            ->get(['code', 'name_tr', 'top_category_code']);

        return view('manager.document-requests.analytics', array_merge($data, [
            'filters'      => $filters,
            'categories'   => $categories,
            'targetTypes'  => DocumentUploadToken::TARGET_TYPES,
            'statusList'   => [
                'pending'  => 'Bekleyen',
                'uploaded' => 'Yüklendi',
                'expired'  => 'Süresi Doldu',
            ],
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeUse($request);

        [$start, $end, $filters] = $this->resolveFilters($request);
        $query = $this->scopedTokenQuery($start, $end, $filters);

        $filename = 'belge-talep-analytics-' . CarbonImmutable::now()->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM (Excel düzgün açsın)
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'token_id',
                'target_type',
                'target_name',
                'category_code',
                'category_name',
                'status',
                'created_at',
                'reminder_first_sent_at',
                'reminder_final_sent_at',
                'last_used_at',
                'expires_at',
                'used_count',
                'recipient_email',
            ]);

            $now = CarbonImmutable::now();
            $query->orderByDesc('id')->chunk(500, function ($chunk) use ($out, $now) {
                foreach ($chunk as $t) {
                    $status = $this->statusForToken($t, $now);
                    fputcsv($out, [
                        $t->id,
                        $t->target_type,
                        $t->target_display_name,
                        $t->category_code,
                        $t->category_name,
                        $status,
                        optional($t->created_at)->toDateTimeString(),
                        optional($t->reminder_first_sent_at)->toDateTimeString(),
                        optional($t->reminder_final_sent_at)->toDateTimeString(),
                        optional($t->last_used_at)->toDateTimeString(),
                        optional($t->expires_at)->toDateTimeString(),
                        $t->used_count,
                        $t->recipient_email,
                    ]);
                }
            });
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal: Analytics builder
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private function buildAnalytics(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $base = fn () => $this->scopedTokenQuery($start, $end, $filters);
        $now  = CarbonImmutable::now();

        // ── 1) KPI'lar ────────────────────────────────────────────────────
        $totalTokens      = (clone $base())->count();
        $uploadedTokens   = (clone $base())->where('used_count', '>', 0)->count();
        $expiredTokens    = (clone $base())
            ->where('used_count', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->count();
        $pendingTokens    = max(0, $totalTokens - $uploadedTokens - $expiredTokens);
        $conversionRate   = $totalTokens > 0 ? round(($uploadedTokens / $totalTokens) * 100, 1) : 0.0;

        // Ortalama upload süresi (created_at → last_used_at saniye delta, sonra dakikaya çevir)
        $avgUploadSeconds = (float) (clone $base())
            ->whereNotNull('last_used_at')
            ->where('used_count', '>', 0)
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, last_used_at)) as avg_sec')
            ->value('avg_sec');
        $avgUploadMinutes = $avgUploadSeconds > 0 ? round($avgUploadSeconds / 60, 1) : 0.0;

        // Hatırlatma sonrası dönüşüm
        $remindersSent       = (clone $base())->whereNotNull('reminder_first_sent_at')->count();
        $remindedAndUploaded = (clone $base())
            ->whereNotNull('reminder_first_sent_at')
            ->where('used_count', '>', 0)
            ->count();
        $reminderConversion  = $remindersSent > 0
            ? round(($remindedAndUploaded / $remindersSent) * 100, 1)
            : 0.0;

        // ── 2) Funnel ─────────────────────────────────────────────────────
        $tokensWithEmail = (clone $base())->whereNotNull('recipient_email')->count();
        $reminderFirst   = (clone $base())->whereNotNull('reminder_first_sent_at')->count();
        $reminderFinal   = (clone $base())->whereNotNull('reminder_final_sent_at')->count();

        // Manager onaylı belgeler — bu token'lardan üretilen Document'lar
        $approvedCount = (clone $base())
            ->whereNotNull('document_id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('documents')
                  ->whereColumn('documents.id', 'document_upload_tokens.document_id')
                  ->where('documents.status', 'approved')
                  ->whereNull('documents.deleted_at');
            })
            ->count();

        $funnel = [
            ['key' => 'created',   'label' => 'Token oluşturuldu',     'count' => $totalTokens,      'pct' => 100.0],
            ['key' => 'sent',      'label' => 'Email/SMS gönderildi',  'count' => $tokensWithEmail,  'pct' => $this->pct($tokensWithEmail, $totalTokens)],
            ['key' => 'reminded',  'label' => 'Hatırlatma gönderildi', 'count' => $reminderFirst,    'pct' => $this->pct($reminderFirst, $totalTokens)],
            ['key' => 'uploaded',  'label' => 'Belge yüklendi',        'count' => $uploadedTokens,   'pct' => $this->pct($uploadedTokens, $totalTokens)],
            ['key' => 'approved',  'label' => 'Manager onayladı',      'count' => $approvedCount,    'pct' => $this->pct($approvedCount, $totalTokens)],
        ];

        // ── 3) Kategori bazlı tablo ───────────────────────────────────────
        $byCategory = (clone $base())
            ->selectRaw('category_code,
                MAX(category_name) as category_name,
                COUNT(*) as total,
                SUM(CASE WHEN used_count > 0 THEN 1 ELSE 0 END) as uploaded,
                SUM(CASE WHEN used_count = 0 AND expires_at IS NOT NULL AND expires_at < ? THEN 1 ELSE 0 END) as expired',
                [$now]
            )
            ->groupBy('category_code')
            ->orderByDesc('total')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                $total      = (int) $row->total;
                $uploaded   = (int) $row->uploaded;
                $expired    = (int) $row->expired;
                $row->conversion_rate = $total > 0 ? round(($uploaded / $total) * 100, 1) : 0.0;
                $row->pending = max(0, $total - $uploaded - $expired);
                return $row;
            });

        // ── 4) Aylık trend (son 12 ay) ────────────────────────────────────
        $monthlyTrend = collect(range(11, 0))->map(function (int $ago) use ($filters) {
            $m       = CarbonImmutable::now()->subMonths($ago);
            $mStart  = $m->startOfMonth();
            $mEnd    = $m->endOfMonth();
            // Bu trend filtre-bağımsız (kategori/target_type filtresi uygula)
            $base = DocumentUploadToken::query()->whereBetween('created_at', [$mStart, $mEnd]);
            if (!empty($filters['category'])) {
                $base->where('category_code', $filters['category']);
            }
            if (!empty($filters['target_type'])) {
                $base->where('target_type', $filters['target_type']);
            }
            $total    = (clone $base)->count();
            $uploaded = (clone $base)->where('used_count', '>', 0)->count();
            return [
                'month'    => $m->format('Y-m'),
                'label'    => $m->format('M Y'),
                'total'    => $total,
                'uploaded' => $uploaded,
                'pct'      => $total > 0 ? round(($uploaded / $total) * 100, 1) : 0,
            ];
        });

        $trendMax = max(1, (int) $monthlyTrend->max('total'));

        // ── 5) Hatırlatma etkisi (3 grup) ─────────────────────────────────
        $noReminderTotal    = (clone $base())->whereNull('reminder_first_sent_at')->whereNull('reminder_final_sent_at')->count();
        $noReminderUploaded = (clone $base())->whereNull('reminder_first_sent_at')->whereNull('reminder_final_sent_at')->where('used_count', '>', 0)->count();
        $firstReminderTotal    = (clone $base())->whereNotNull('reminder_first_sent_at')->whereNull('reminder_final_sent_at')->count();
        $firstReminderUploaded = (clone $base())->whereNotNull('reminder_first_sent_at')->whereNull('reminder_final_sent_at')->where('used_count', '>', 0)->count();
        $finalReminderTotal    = (clone $base())->whereNotNull('reminder_final_sent_at')->count();
        $finalReminderUploaded = (clone $base())->whereNotNull('reminder_final_sent_at')->where('used_count', '>', 0)->count();

        $reminderImpact = [
            'none' => [
                'label'      => 'Hatırlatmasız',
                'total'      => $noReminderTotal,
                'uploaded'   => $noReminderUploaded,
                'pct'        => $this->pct($noReminderUploaded, $noReminderTotal),
            ],
            'first' => [
                'label'      => 'Sadece ilk hatırlatma',
                'total'      => $firstReminderTotal,
                'uploaded'   => $firstReminderUploaded,
                'pct'        => $this->pct($firstReminderUploaded, $firstReminderTotal),
            ],
            'final' => [
                'label'      => 'Son hatırlatma da gönderildi',
                'total'      => $finalReminderTotal,
                'uploaded'   => $finalReminderUploaded,
                'pct'        => $this->pct($finalReminderUploaded, $finalReminderTotal),
            ],
        ];

        // ── 6) Son 20 aktivite ────────────────────────────────────────────
        $recent = (clone $base())
            ->orderByDesc('id')
            ->limit(20)
            ->get([
                'id', 'target_type', 'target_id', 'target_display_name',
                'category_code', 'category_name', 'used_count', 'expires_at',
                'created_at', 'last_used_at', 'reminder_first_sent_at',
            ])
            ->map(function ($t) use ($now) {
                return [
                    'id'            => $t->id,
                    'target_type'   => $t->target_type,
                    'target_label'  => DocumentUploadToken::TARGET_TYPES[$t->target_type] ?? $t->target_type,
                    'target_name'   => $t->target_display_name ?: '—',
                    'category_name' => $t->category_name ?: $t->category_code,
                    'status'        => $this->statusForToken($t, $now),
                    'created_at'    => $t->created_at,
                    'last_used_at'  => $t->last_used_at,
                    'reminded'      => $t->reminder_first_sent_at !== null,
                ];
            });

        return [
            'kpi' => [
                'total_tokens'        => $totalTokens,
                'uploaded_tokens'     => $uploadedTokens,
                'pending_tokens'      => $pendingTokens,
                'expired_tokens'      => $expiredTokens,
                'conversion_rate'     => $conversionRate,
                'avg_upload_minutes'  => $avgUploadMinutes,
                'reminder_conversion' => $reminderConversion,
                'reminders_sent'      => $remindersSent,
            ],
            'funnel'         => $funnel,
            'byCategory'     => $byCategory,
            'monthlyTrend'   => $monthlyTrend,
            'trendMax'       => $trendMax,
            'reminderImpact' => $reminderImpact,
            'recent'         => $recent,
        ];
    }

    /**
     * Filtre uygulanmış token query — KPI + funnel + tablolar için ortak.
     */
    private function scopedTokenQuery(CarbonImmutable $start, CarbonImmutable $end, array $filters)
    {
        $q = DocumentUploadToken::query()->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['category'])) {
            $q->where('category_code', $filters['category']);
        }
        if (!empty($filters['target_type'])) {
            $q->where('target_type', $filters['target_type']);
        }
        if (!empty($filters['status'])) {
            $now = CarbonImmutable::now();
            switch ($filters['status']) {
                case 'uploaded':
                    $q->where('used_count', '>', 0);
                    break;
                case 'pending':
                    $q->where('used_count', 0)
                      ->where(function ($w) use ($now) {
                          $w->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                      });
                    break;
                case 'expired':
                    $q->where('used_count', 0)
                      ->whereNotNull('expires_at')
                      ->where('expires_at', '<', $now);
                    break;
            }
        }

        return $q;
    }

    private function statusForToken(DocumentUploadToken $t, CarbonImmutable $now): string
    {
        if ((int) $t->used_count > 0) {
            return 'uploaded';
        }
        if ($t->expires_at !== null && $t->expires_at < $now) {
            return 'expired';
        }
        return 'pending';
    }

    /**
     * @return array{0:CarbonImmutable,1:CarbonImmutable,2:array<string,string>}
     */
    private function resolveFilters(Request $request): array
    {
        $range = (string) $request->query('range', '30d');
        $end   = CarbonImmutable::now()->endOfDay();

        $start = match ($range) {
            '7d'   => $end->subDays(7)->startOfDay(),
            '30d'  => $end->subDays(30)->startOfDay(),
            '90d'  => $end->subDays(90)->startOfDay(),
            '180d' => $end->subDays(180)->startOfDay(),
            '365d' => $end->subDays(365)->startOfDay(),
            default => $end->subDays(30)->startOfDay(),
        };

        // Custom override
        if ($request->filled('from')) {
            try { $start = CarbonImmutable::parse((string) $request->query('from'))->startOfDay(); } catch (\Throwable) {}
        }
        if ($request->filled('to')) {
            try { $end = CarbonImmutable::parse((string) $request->query('to'))->endOfDay(); } catch (\Throwable) {}
        }

        $category   = trim((string) $request->query('category', ''));
        $status     = trim((string) $request->query('status', ''));
        $targetType = trim((string) $request->query('target_type', ''));

        if (!in_array($status, ['pending', 'uploaded', 'expired'], true)) {
            $status = '';
        }
        if (!array_key_exists($targetType, DocumentUploadToken::TARGET_TYPES)) {
            $targetType = '';
        }

        return [$start, $end, [
            'range'       => $range,
            'from'        => $start->toDateString(),
            'to'          => $end->toDateString(),
            'category'    => $category,
            'status'      => $status,
            'target_type' => $targetType,
        ]];
    }

    private function pct(int $part, int $total): float
    {
        if ($total <= 0) return 0.0;
        return round(($part / $total) * 100, 1);
    }

    private function authorizeUse(Request $request): void
    {
        ModuleAccess::assertEnabled('doc_request');
        $user = $request->user();
        if (!$user || !$user->hasPermissionCode('doc_request.use')) {
            abort(403, 'Bu özellik için yetkiniz yok.');
        }
    }
}
