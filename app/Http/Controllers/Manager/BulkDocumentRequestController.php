<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\DocumentUploadReminderMail;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Services\Analytics\AnalyticsService;
use App\Support\ModuleAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk Belge Talep — D10
 *
 * Manager birden fazla aday öğrenci / öğrenciye aynı kategoriden belge
 * talep linki gönderebilir. Her hedef için yeni bir DocumentUploadToken
 * oluşturulur (storeFor() loop'u). Email ve/veya WhatsApp ilk bildirimi
 * anında gönderilir; quota aşıldığında kısmi başarı raporu döndürülür.
 *
 * Akış:
 *   1. index   → bulk form (target type + kategori + kanal + süre)
 *   2. preview → seçilen ID'lerin önizlemesi (recipient_email/phone eksikleri)
 *   3. store   → toplu token oluştur, raporla, CSV indirme linki ver
 *   4. export  → batch token URL'lerini CSV (UTF-8 BOM) olarak indir
 */
class BulkDocumentRequestController extends Controller
{
    private const MAX_TARGETS = 200;
    private const DEFAULT_EXPIRES_DAYS = 7;
    private const MIN_EXPIRES_DAYS = 1;
    private const MAX_EXPIRES_DAYS = 30;

    public function index(Request $request)
    {
        $this->authorizeUse($request);

        $categories = DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('top_category_code')
            ->orderBy('sort_order')
            ->get(['code', 'name_tr', 'name_de', 'top_category_code']);

        $companyId = $this->resolveCompanyId();

        $guests = GuestApplication::query()
            ->where('company_id', $companyId)
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'lead_status'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $students = StudentAssignment::query()
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->select(['student_id', 'display_name', 'senior_email', 'branch'])
            ->orderBy('display_name')
            ->limit(500)
            ->get();

        $company = Company::find($companyId);
        $quotaLimit = $company?->doc_request_monthly_limit;
        $quotaUsage = $company?->docRequestMonthlyUsage() ?? 0;
        $quotaRemaining = $quotaLimit !== null && (int) $quotaLimit > 0
            ? max(0, (int) $quotaLimit - $quotaUsage)
            : null;

        return view('manager.document-requests.bulk', [
            'categories'      => $categories,
            'guests'          => $guests,
            'students'        => $students,
            'quotaLimit'      => $quotaLimit,
            'quotaUsage'      => $quotaUsage,
            'quotaRemaining'  => $quotaRemaining,
            'maxTargets'      => self::MAX_TARGETS,
            'defaultExpires'  => self::DEFAULT_EXPIRES_DAYS,
            'minExpires'      => self::MIN_EXPIRES_DAYS,
            'maxExpires'      => self::MAX_EXPIRES_DAYS,
            'lastBatchToken'  => $request->session()->get('bulk_doc_request_last_batch'),
            'lastBatchSummary'=> $request->session()->get('bulk_doc_request_last_summary'),
        ]);
    }

    public function preview(Request $request)
    {
        $this->authorizeUse($request);

        $data = $this->validateBulk($request);
        $targets = $this->loadTargets($data['target_type'], $data['target_ids']);

        $missing  = [];
        $ready    = [];
        foreach ($targets as $row) {
            $hasEmail = !empty($row['email']);
            $hasPhone = !empty($row['phone']);
            $entry = [
                'id'           => $row['id'],
                'display_name' => $row['display_name'],
                'email'        => $row['email'],
                'phone'        => $row['phone'],
                'channels'     => [],
            ];
            $missingChannels = [];
            foreach ($data['notification_channels'] as $ch) {
                if ($ch === 'email' && !$hasEmail)   $missingChannels[] = 'email';
                if ($ch === 'whatsapp' && !$hasPhone) $missingChannels[] = 'whatsapp';
            }
            $entry['missing_channels'] = $missingChannels;
            if (!empty($missingChannels)) {
                $missing[] = $entry;
            } else {
                $ready[] = $entry;
            }
        }

        return response()->json([
            'success'       => true,
            'ready'         => $ready,
            'missing'       => $missing,
            'ready_count'   => count($ready),
            'missing_count' => count($missing),
            'total_count'   => count($targets),
            'target_type'   => $data['target_type'],
            'channels'      => $data['notification_channels'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeUse($request);

        $data = $this->validateBulk($request);
        $companyId = $this->resolveCompanyId();

        // Quota kontrol: kalan kapasiteyi aşan istek 422
        $company = Company::find($companyId);
        if ($company) {
            $limit = (int) ($company->doc_request_monthly_limit ?? 0);
            if ($limit > 0) {
                $usage     = $company->docRequestMonthlyUsage();
                $remaining = max(0, $limit - $usage);
                if (count($data['target_ids']) > $remaining) {
                    return response()->json([
                        'error'          => "Bu işlem aylık limiti aşar. Kalan kapasite: {$remaining}, talep edilen: " . count($data['target_ids']) . ". Daha az kişi seçin veya planınızı yükseltin.",
                        'quota_limit'    => $limit,
                        'quota_usage'    => $usage,
                        'quota_remaining'=> $remaining,
                    ], 422);
                }
            }
        }

        $category = DocumentCategory::where('code', $data['category_code'])->first();
        if (!$category) {
            return response()->json(['error' => 'Belge kategorisi bulunamadı.'], 422);
        }

        $targets    = $this->loadTargets($data['target_type'], $data['target_ids']);
        $expiresAt  = CarbonImmutable::now()->addDays((int) $data['expires_in_days']);
        $batchToken = (string) Str::uuid();
        $userId     = $request->user()?->id;

        $created       = [];
        $failed        = [];
        $emailSent     = 0;
        $whatsappSent  = 0;

        $whatsapp = $this->resolveWhatsAppService();

        DB::beginTransaction();
        try {
            foreach ($targets as $row) {
                try {
                    $targetType = $data['target_type'] === 'guest'
                        ? DocumentUploadToken::TARGET_GUEST
                        : DocumentUploadToken::TARGET_STUDENT;

                    $token = DocumentUploadToken::create([
                        'company_id'          => $companyId,
                        'token'               => DocumentUploadToken::generateToken(),
                        'target_type'         => $targetType,
                        'target_id'           => (string) $row['id'],
                        'target_display_name' => $row['display_name'],
                        'category_code'       => $category->code,
                        'category_name'       => $category->name_tr ?? $category->code,
                        'document_name_de'    => $category->name_de ?? null,
                        'custom_message'      => $data['custom_message'] ?? null,
                        'recipient_email'     => $row['email'] ?: null,
                        'recipient_phone'     => $row['phone'] ?: null,
                        'created_by_user_id'  => $userId,
                        'max_uses'            => 1,
                        'used_count'          => 0,
                        'expires_at'          => $expiresAt,
                    ]);

                    // Batch meta (DB hot path'i bozmadan session'a ekle, CSV export için)
                    $created[] = [
                        'token_id'     => $token->id,
                        'target_id'    => (string) $row['id'],
                        'display_name' => $row['display_name'],
                        'email'        => $row['email'],
                        'phone'        => $row['phone'],
                        'token'        => $token->token,
                        'url'          => url('/u/' . $token->token),
                        'expires_at'   => $expiresAt->toIso8601String(),
                    ];

                    // Email — ilk talep maili (DocumentUploadReminderMail 'first' stage)
                    if (in_array('email', $data['notification_channels'], true) && !empty($row['email'])) {
                        try {
                            Mail::to($row['email'])->queue(new DocumentUploadReminderMail($token, 'first'));
                            $emailSent++;
                        } catch (\Throwable $e) {
                            Log::warning('bulk_doc_request mail fail', [
                                'token_id' => $token->id,
                                'email'    => $row['email'],
                                'err'      => $e->getMessage(),
                            ]);
                        }
                    }

                    // WhatsApp — D6 agent'in yazacağı method (varsa); yoksa sessizce geç
                    if (in_array('whatsapp', $data['notification_channels'], true)
                        && !empty($row['phone'])
                        && $whatsapp !== null) {
                        try {
                            if (method_exists($whatsapp, 'sendDocumentRequestReminder')) {
                                $ok = $whatsapp->sendDocumentRequestReminder($token, 'first');
                                if ($ok) $whatsappSent++;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('bulk_doc_request whatsapp fail', [
                                'token_id' => $token->id,
                                'phone'    => $row['phone'],
                                'err'      => $e->getMessage(),
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $failed[] = [
                        'target_id'    => (string) $row['id'],
                        'display_name' => $row['display_name'],
                        'error'        => $e->getMessage(),
                    ];
                    Log::warning('bulk_doc_request token create fail', [
                        'target_id' => $row['id'],
                        'err'       => $e->getMessage(),
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('bulk_doc_request store transaction fail', ['err' => $e->getMessage()]);
            return response()->json(['error' => 'Beklenmedik hata: ' . $e->getMessage()], 500);
        }

        // PostHog event
        try {
            app(AnalyticsService::class)->capture('bulk_doc_request_created', [
                'count'         => count($created),
                'failed_count'  => count($failed),
                'target_type'   => $data['target_type'],
                'category_code' => $category->code,
                'channels'      => $data['notification_channels'],
                'expires_days'  => (int) $data['expires_in_days'],
            ], (string) ($userId ?? 'system'));
        } catch (\Throwable $e) {
            // analytics best-effort
        }

        // Batch'i session'a kaydet (CSV export için kısa ömürlü)
        $summary = [
            'batch'         => $batchToken,
            'category_code' => $category->code,
            'category_name' => $category->name_tr,
            'target_type'   => $data['target_type'],
            'channels'      => $data['notification_channels'],
            'expires_at'    => $expiresAt->toIso8601String(),
            'created_count' => count($created),
            'failed_count'  => count($failed),
            'email_sent'    => $emailSent,
            'whatsapp_sent' => $whatsappSent,
            'created'       => $created,
            'failed'        => $failed,
            'created_at'    => CarbonImmutable::now()->toIso8601String(),
        ];

        $request->session()->put("bulk_doc_request_batch:{$batchToken}", $summary);
        $request->session()->put('bulk_doc_request_last_batch', $batchToken);
        $request->session()->put('bulk_doc_request_last_summary', $summary);

        return response()->json([
            'success'        => true,
            'batch'          => $batchToken,
            'created_count'  => count($created),
            'failed_count'   => count($failed),
            'email_sent'     => $emailSent,
            'whatsapp_sent'  => $whatsappSent,
            'csv_export_url' => route('manager.doc-request.bulk.export', ['batch' => $batchToken]),
            'created'        => $created,
            'failed'         => $failed,
        ]);
    }

    public function export(Request $request, string $batch): StreamedResponse
    {
        $this->authorizeUse($request);

        $summary = $request->session()->get("bulk_doc_request_batch:{$batch}");
        if (!$summary || empty($summary['created'])) {
            abort(404, 'Batch bulunamadı veya süresi geçti.');
        }

        $filename = 'belge-talepleri-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($summary): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM — Excel uyumu
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['student_id', 'name', 'email', 'phone', 'token_url', 'expires_at'], ',', '"');
            foreach ($summary['created'] as $row) {
                fputcsv($out, [
                    (string) ($row['target_id'] ?? ''),
                    (string) ($row['display_name'] ?? ''),
                    (string) ($row['email'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                    (string) ($row['url'] ?? ''),
                    (string) ($row['expires_at'] ?? ''),
                ], ',', '"');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function validateBulk(Request $request): array
    {
        return $request->validate([
            'target_type'           => 'required|string|in:guest,student',
            'target_ids'            => 'required|array|min:1|max:' . self::MAX_TARGETS,
            'target_ids.*'          => 'required|string|max:64',
            'category_code'         => 'required|string|exists:document_categories,code',
            'notification_channels' => 'required|array|min:1',
            'notification_channels.*' => 'string|in:email,whatsapp',
            'expires_in_days'       => 'nullable|integer|min:' . self::MIN_EXPIRES_DAYS . '|max:' . self::MAX_EXPIRES_DAYS,
            'custom_message'        => 'nullable|string|max:500',
        ]) + ['expires_in_days' => $request->input('expires_in_days', self::DEFAULT_EXPIRES_DAYS)];
    }

    /**
     * Hedef satırlarını normalize et — her satır: id, display_name, email, phone, company_id
     * Bilinmeyen / başka şirkete ait ID'ler atlanır.
     *
     * @return array<int,array{id:string,display_name:string,email:?string,phone:?string,company_id:int}>
     */
    private function loadTargets(string $type, array $ids): array
    {
        $companyId = $this->resolveCompanyId();
        $rows = [];

        if ($type === 'guest') {
            $guests = GuestApplication::query()
                ->where('company_id', $companyId)
                ->whereIn('id', array_filter(array_map('intval', $ids)))
                ->get(['id', 'first_name', 'last_name', 'email', 'phone']);
            foreach ($guests as $g) {
                $rows[] = [
                    'id'           => (string) $g->id,
                    'display_name' => trim(((string) $g->first_name) . ' ' . ((string) $g->last_name)),
                    'email'        => $g->email ? (string) $g->email : null,
                    'phone'        => $g->phone ? (string) $g->phone : null,
                    'company_id'   => $companyId,
                ];
            }
        } else { // student
            $students = StudentAssignment::query()
                ->where('company_id', $companyId)
                ->whereIn('student_id', array_map('strval', $ids))
                ->get(['student_id', 'display_name']);

            // email/phone bilgisi için bağlı GuestApplication üzerinden çek
            $studentIds = $students->pluck('student_id')->all();
            $guestMap = GuestApplication::query()
                ->where('company_id', $companyId)
                ->whereIn('converted_student_id', $studentIds)
                ->get(['converted_student_id', 'email', 'phone'])
                ->keyBy('converted_student_id');

            foreach ($students as $s) {
                $linked = $guestMap->get($s->student_id);
                $rows[] = [
                    'id'           => (string) $s->student_id,
                    'display_name' => (string) ($s->display_name ?? $s->student_id),
                    'email'        => $linked && $linked->email ? (string) $linked->email : null,
                    'phone'        => $linked && $linked->phone ? (string) $linked->phone : null,
                    'company_id'   => $companyId,
                ];
            }
        }
        return $rows;
    }

    /**
     * WhatsAppService'i container'dan al — yoksa null döner.
     * D6 agent paralel çalışıyor; class yoksa fallback'e düş.
     */
    private function resolveWhatsAppService()
    {
        if (!class_exists('App\\Services\\WhatsAppService')) {
            return null;
        }
        try {
            return app('App\\Services\\WhatsAppService');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveCompanyId(): int
    {
        return app()->bound('current_company_id')
            ? (int) app('current_company_id')
            : 1;
    }

    /**
     * Modül + permission kontrolü — ManagerDocumentRequestController ile aynı.
     */
    private function authorizeUse(Request $request): void
    {
        ModuleAccess::assertEnabled('doc_request');
        $user = $request->user();
        if (!$user || !$user->hasPermissionCode('doc_request.use')) {
            abort(403, 'Bu özellik için yetkiniz yok.');
        }
    }
}
