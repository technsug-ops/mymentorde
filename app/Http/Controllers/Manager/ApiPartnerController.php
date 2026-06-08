<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ApiPartner;
use App\Models\ApiPartnerRequest;
use App\Models\UniMatchResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manager API Partner yönetimi.
 *
 * URL: /manager/api-partners
 * Auth: manager role + permission (mevcut grup middleware'i kapsar)
 *
 * Akış:
 *  - index: tüm partnerlar + son request sayıları
 *  - create + store: yeni partner provision → plaintext key BİR KEZ gösterilir
 *  - show: detaylı stats + audit log + lead conversion sayısı
 *  - rotate: yeni key generate, eski hash invalidate
 *  - toggle: is_active flip (revoke)
 */
class ApiPartnerController extends Controller
{
    public function index(): View
    {
        $partners = ApiPartner::query()
            ->orderByDesc('is_active')
            ->orderByDesc('last_used_at')
            ->orderBy('name')
            ->get();

        // Bugün ve son 7 gün request sayıları (tek query, grouped)
        $today = ApiPartnerRequest::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->selectRaw('api_partner_id, COUNT(*) as c')
            ->groupBy('api_partner_id')
            ->pluck('c', 'api_partner_id')
            ->all();
        $week = ApiPartnerRequest::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('api_partner_id, COUNT(*) as c')
            ->groupBy('api_partner_id')
            ->pluck('c', 'api_partner_id')
            ->all();

        // Partner kaynaklı UniMatch conversion sayısı (utm_source=partner + utm_campaign=slug)
        $conversions = UniMatchResponse::query()
            ->where('source', 'partner')
            ->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign as slug, COUNT(*) as total, SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed')
            ->groupBy('utm_campaign')
            ->get()
            ->keyBy('slug');

        return view('manager.api-partners.index', [
            'partners'      => $partners,
            'todayCounts'   => $today,
            'weekCounts'    => $week,
            'conversions'   => $conversions,
        ]);
    }

    public function create(): View
    {
        return view('manager.api-partners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:120',
            'contact_email'       => 'nullable|email|max:160',
            'website'             => 'nullable|url|max:200',
            'rate_limit_per_hour' => 'nullable|integer|min:10|max:100000',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $data['slug'] = Str::slug($data['name']);
        if (ApiPartner::query()->where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $data['rate_limit_per_hour'] = $data['rate_limit_per_hour'] ?? 1000;

        $result = ApiPartner::provision($data);

        // Plain key sadece bu redirect'te flash session'da, sonra silinir
        return redirect(\App\Support\PartnerRouting::url('show', $result['partner']))
            ->with('plaintext_key', $result['plaintext_key'])
            ->with('success', 'Partner oluşturuldu. API anahtarı yalnız bir kez gösterilir.');
    }

    public function show(Request $request, ApiPartner $apiPartner): View
    {
        // Endpoint dağılımı (son 30 gün)
        $endpoints = ApiPartnerRequest::query()
            ->where('api_partner_id', $apiPartner->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('endpoint, COUNT(*) as c, AVG(response_time_ms) as avg_ms')
            ->groupBy('endpoint')
            ->orderByDesc('c')
            ->get();

        // Son 50 request (audit log)
        $recentRequests = ApiPartnerRequest::query()
            ->where('api_partner_id', $apiPartner->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Lead conversion — UniMatchResponse utm_campaign=slug
        $leads = UniMatchResponse::query()
            ->where('source', 'partner')
            ->where('utm_campaign', $apiPartner->slug)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN current_step >= 12 THEN 1 ELSE 0 END) as deep_funnel
            ')
            ->first();

        // Hata oranı son 24h
        $last24h = ApiPartnerRequest::query()
            ->where('api_partner_id', $apiPartner->id)
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN response_code = 429 THEN 1 ELSE 0 END) as rate_limited
            ')
            ->first();

        return view('manager.api-partners.show', [
            'partner'        => $apiPartner,
            'endpoints'      => $endpoints,
            'recentRequests' => $recentRequests,
            'leads'          => $leads,
            'last24h'        => $last24h,
            'plaintextKey'   => $request->session()->pull('plaintext_key'),
        ]);
    }

    public function update(Request $request, ApiPartner $apiPartner): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:120',
            'contact_email'       => 'nullable|email|max:160',
            'website'             => 'nullable|url|max:200',
            'rate_limit_per_hour' => 'required|integer|min:10|max:100000',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $apiPartner->update($data);

        return redirect(\App\Support\PartnerRouting::url('show', $apiPartner))
            ->with('success', 'Partner bilgileri güncellendi.');
    }

    public function rotate(ApiPartner $apiPartner): RedirectResponse
    {
        $newKey = $apiPartner->rotateKey();

        return redirect(\App\Support\PartnerRouting::url('show', $apiPartner))
            ->with('plaintext_key', $newKey)
            ->with('success', 'Yeni anahtar oluşturuldu. Eski anahtar artık geçersiz.');
    }

    public function toggle(ApiPartner $apiPartner): RedirectResponse
    {
        $apiPartner->update(['is_active' => ! $apiPartner->is_active]);

        $state = $apiPartner->is_active ? 'aktif' : 'devre dışı';
        return redirect(\App\Support\PartnerRouting::url('index'))
            ->with('success', "Partner {$state} edildi.");
    }

    public function destroy(ApiPartner $apiPartner): RedirectResponse
    {
        // Audit log nullOnDelete ile partner_id NULL'a düşer, tarihçe korunur
        $apiPartner->delete();

        return redirect(\App\Support\PartnerRouting::url('index'))
            ->with('success', 'Partner silindi. Audit log tarihçesi korundu.');
    }
}
