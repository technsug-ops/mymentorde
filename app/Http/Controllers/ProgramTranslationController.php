<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Services\ProgramCatalog\ProgramTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lazy on-demand program detay çeviri endpoint'i.
 *
 * URL: POST /program/{program}/translate
 *
 * Akış:
 *   1. Detay sayfasında "🇹🇷 Türkçeye çevir" butonu
 *   2. JS AJAX POST → bu endpoint
 *   3. ProgramTranslationService → Gemini → 4 alanı çevir → DB'ye yaz
 *   4. Frontend response'tan TR alanları render eder
 *
 * Rate limit: 10/dk per kullanıcı (Gemini cost koruması).
 */
class ProgramTranslationController extends Controller
{
    public function __construct(private readonly ProgramTranslationService $translator) {}

    public function translate(Request $request, Program $program): JsonResponse
    {
        // Eğer kullanıcı manager ise force=true verebilir (manuel düzeltme override)
        $force = (bool) $request->input('force', false) && $request->user()?->is_manager;

        // API key company-spesifik — request'in tenant context'ini al
        $companyId = (int) ($request->attributes->get('company_id') ?? $request->user()?->company_id ?? 1);

        // Engineering/CS programlari icin uzun content + Gemini timeout veya parse hatasi 500'e
        // gidiyordu. Service throw etmedigi senaryolar (GeminiProvider OOM, network, vs.) icin
        // controller-seviyesinde guvenli yakalama. Hicbir cevap kullaniciya 500 olarak gitmesin.
        try {
            $result = $this->translator->translateProgram($program, force: $force, companyId: $companyId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ProgramTranslation.controller_failed', [
                'program_id' => $program->id,
                'course'     => $program->course_name,
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'success'  => false,
                'message'  => 'Ceviri servisi su an cevap vermedi. Lutfen birazdan tekrar deneyin.',
                'detail'   => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
                'skipped'  => 4,
            ], 503);
        }

        // PostHog: lazy translate triggered (cost ölçümü + popularity sinyali)
        try {
            $sessionToken = (string) $request->cookie('uni_match_session', '');
            app(\App\Services\Analytics\AnalyticsService::class)->capture('program_translate_clicked', [
                'program_id'        => $program->id,
                'course_name'       => $program->course_name,
                'translated_fields' => $result['translated_fields'] ?? 0,
                'success'           => empty($result['error']),
            ], $sessionToken !== '' ? $sessionToken : null);
        } catch (\Throwable $e) { /* asla kırma */ }

        if ($result['error']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'skipped' => $result['skipped_fields'],
            ], 422);
        }

        $program->refresh();

        return response()->json([
            'success'           => true,
            'translated_fields' => $result['translated_fields'],
            'skipped_fields'    => $result['skipped_fields'],
            'data' => [
                'description_tr'                => $program->description_tr,
                'qualification_requirements_tr' => $program->qualification_requirements_tr,
                'language_requirements_tr'      => $program->language_requirements_tr,
                'required_documents_tr'         => $program->required_documents_tr,
                'translated_at'                 => $program->translated_at?->toIso8601String(),
            ],
        ]);
    }
}
