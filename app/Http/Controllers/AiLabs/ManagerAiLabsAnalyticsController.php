<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Services\AiLabs\AnalyticsService;
use App\Services\AiLabs\ContentTemplates;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerAiLabsAnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        // Analytics sadece yöneticiler — senior engellenir
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403, 'AI Labs analytics sadece yöneticilere açıktır.');
        }

        $cid = $this->companyId();
        $data = $analytics->monthly($cid);

        return view('ai-labs.manager.analytics.index', array_merge($data, [
            'templates' => ContentTemplates::all(),
        ]));
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
