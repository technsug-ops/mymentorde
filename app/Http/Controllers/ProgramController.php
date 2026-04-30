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
    public function show(Program $program): View
    {
        // Source linkler — hangi kaynaklardan geldiği
        $sources = ProgramSourceLink::query()
            ->where('program_id', $program->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('last_synced_at')
            ->get();

        // Üniversite eager-load
        $program->load('university');

        return view('program.show', [
            'program' => $program,
            'sources' => $sources,
        ]);
    }
}
