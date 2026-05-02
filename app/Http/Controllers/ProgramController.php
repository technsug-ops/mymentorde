<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramSourceLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Canonical Program detay sayfası — public.
 *
 * Wizard result kartından, manager dashboard'dan, veya direkt URL ile
 * erişilebilir. SHARED tablo (universities + programs) — login gerekmez.
 *
 * URL: GET /program/{program}
 */
class ProgramController extends Controller
{
    public function show(Program $program, Request $request): View
    {
        // Source linkler — hangi kaynaklardan geldiği
        $sources = ProgramSourceLink::query()
            ->where('program_id', $program->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('last_synced_at')
            ->get();

        // Üniversite eager-load
        $program->load('university');

        // PostHog event — program detay görüntüleme (wizard'dan veya direkt)
        try {
            $sessionToken = (string) $request->cookie('uni_match_session', '');
            $sources_codes = $sources->pluck('source')->toArray();
            app(\App\Services\Analytics\AnalyticsService::class)->capture('program_detail_viewed', [
                'program_id'   => $program->id,
                'course_name'  => $program->course_name,
                'university'   => $program->university_name_cached,
                'sources'      => $sources_codes,
                'has_tr'       => ! empty($program->description_tr),
                'quality'      => $program->quality_score,
                'is_uni_assist'=> (bool) ($program->university->is_uni_assist_member ?? false),
                'from_wizard'  => $sessionToken !== '',
            ], $sessionToken !== '' ? $sessionToken : null);
        } catch (\Throwable $e) { /* analytics asla request'i kırma */ }

        return view('program.show', [
            'program' => $program,
            'sources' => $sources,
        ]);
    }
}
