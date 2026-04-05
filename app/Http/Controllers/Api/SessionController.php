<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * K2 — Aktif Oturum Yönetimi
 */
class SessionController extends Controller
{
    public function activeSessions(Request $request): \Illuminate\Http\JsonResponse
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => [
                'id'            => $s->id,
                'ip'            => $s->ip_address,
                'device'        => $this->parseUserAgent((string) ($s->user_agent ?? '')),
                'last_activity' => Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                'is_current'    => $s->id === session()->getId(),
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    public function revokeSession(Request $request, string $sessionId): \Illuminate\Http\JsonResponse
    {
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    private function parseUserAgent(string $ua): string
    {
        if (str_contains($ua, 'Mobile')) {
            return 'Mobil';
        }
        if (str_contains($ua, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'Safari')) {
            return 'Safari';
        }
        return 'Bilinmeyen';
    }
}
