<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\AiLabsSettings;
use App\Models\KnowledgeSource;
use App\Models\MarketingAdminSetting;
use App\Services\AiLabs\GeminiProvider;
use App\Services\AiLabs\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Labs settings paneli — mod, provider, limit, marka adı.
 *
 * Route: /manager/ai-labs/settings (manager.role + module:ai_labs)
 */
class ManagerAiLabsSettingsController extends Controller
{
    public function show(Request $request, GeminiProvider $gemini): View
    {
        // Settings + Analytics gibi yönetimsel sayfalar sadece admin paneli rollerine
        $this->assertAdminRole($request);

        $cid = $this->companyId();
        $settings = AiLabsSettings::forCompany($cid);

        $brandName = (string) (MarketingAdminSetting::where('company_id', $cid)
            ->where('setting_key', 'ai_labs_brand_name')
            ->value('setting_value') ?: 'MentorDE AI Labs');

        $brandTagline = (string) (MarketingAdminSetting::where('company_id', $cid)
            ->where('setting_key', 'ai_labs_brand_tagline')
            ->value('setting_value') ?: 'Yurt dışı eğitim bilgi havuzu');

        $geminiKeyDb = (string) (MarketingAdminSetting::where('company_id', $cid)
            ->where('setting_key', 'ai_labs_gemini_key')
            ->value('setting_value') ?: '');

        $serperKeyDb = (string) (MarketingAdminSetting::where('company_id', $cid)
            ->where('setting_key', 'ai_labs_serper_key')
            ->value('setting_value') ?: '');

        $sourcesActive = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->count();

        $sourcesTotal = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->count();

        $sourcesSynced = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where('type', 'pdf')
            ->whereNotNull('gemini_file_id')
            ->count();

        $sourcesPendingSync = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where('type', 'pdf')
            ->whereNull('gemini_file_id')
            ->count();

        return view('ai-labs.manager.settings.show', [
            'settings'            => $settings,
            'brandName'           => $brandName,
            'brandTagline'        => $brandTagline,
            'geminiConfigured'    => $gemini->isConfigured($cid),
            'geminiKeyMasked'     => $geminiKeyDb ? substr($geminiKeyDb, 0, 6) . str_repeat('•', max(0, strlen($geminiKeyDb) - 10)) . substr($geminiKeyDb, -4) : '',
            'serperKeyMasked'     => $serperKeyDb ? substr($serperKeyDb, 0, 6) . str_repeat('•', max(0, strlen($serperKeyDb) - 10)) . substr($serperKeyDb, -4) : '',
            'sourcesActive'       => $sourcesActive,
            'sourcesTotal'        => $sourcesTotal,
            'sourcesSynced'       => $sourcesSynced,
            'sourcesPendingSync'  => $sourcesPendingSync,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->assertAdminRole($request);
        $cid = $this->companyId();

        $data = $request->validate([
            'default_mode'              => 'required|in:strict,hybrid',
            'primary_provider'          => 'required|in:gemini,claude,openai',
            'daily_limit_student'       => 'required|integer|min:0|max:1000',
            'daily_limit_guest'         => 'required|integer|min:0|max:500',
            'content_generator_enabled' => 'nullable|boolean',
            'monthly_doc_limit'         => 'required|integer|min:0|max:500',
            'brand_name'                => 'required|string|max:80',
            'brand_tagline'             => 'nullable|string|max:180',
            'gemini_api_key'            => 'nullable|string|max:200',
            'serper_api_key'            => 'nullable|string|max:200',
            'admin_instructions'        => 'nullable|string|max:5000',
        ]);

        $settings = AiLabsSettings::forCompany($cid);

        $newInstructions = trim((string) ($data['admin_instructions'] ?? ''));
        $changed = $newInstructions !== (string) ($settings->admin_instructions ?? '');

        $settings->update([
            'default_mode'              => $data['default_mode'],
            'primary_provider'          => $data['primary_provider'],
            'daily_limit_student'       => (int) $data['daily_limit_student'],
            'daily_limit_guest'         => (int) $data['daily_limit_guest'],
            'content_generator_enabled' => (bool) ($data['content_generator_enabled'] ?? false),
            'monthly_doc_limit'         => (int) $data['monthly_doc_limit'],
            'admin_instructions'        => $newInstructions !== '' ? $newInstructions : null,
            'instructions_updated_at'   => $changed ? now() : $settings->instructions_updated_at,
        ]);

        MarketingAdminSetting::updateOrCreate(
            ['company_id' => $cid, 'setting_key' => 'ai_labs_brand_name'],
            ['setting_value' => $data['brand_name'], 'updated_by_user_id' => auth()->id()]
        );
        MarketingAdminSetting::updateOrCreate(
            ['company_id' => $cid, 'setting_key' => 'ai_labs_brand_tagline'],
            ['setting_value' => (string) ($data['brand_tagline'] ?? ''), 'updated_by_user_id' => auth()->id()]
        );

        // API key'ler — sadece doluysa güncelle (boş bırakınca mevcut key korunsun)
        if (!empty($data['gemini_api_key'])) {
            MarketingAdminSetting::updateOrCreate(
                ['company_id' => $cid, 'setting_key' => 'ai_labs_gemini_key'],
                ['setting_value' => trim($data['gemini_api_key']), 'updated_by_user_id' => auth()->id()]
            );
        }
        if (!empty($data['serper_api_key'])) {
            MarketingAdminSetting::updateOrCreate(
                ['company_id' => $cid, 'setting_key' => 'ai_labs_serper_key'],
                ['setting_value' => trim($data['serper_api_key']), 'updated_by_user_id' => auth()->id()]
            );
        }

        return back()->with('status', 'AI Labs ayarları güncellendi.');
    }

    /**
     * Gemini API key + bağlantı testi — AJAX endpoint.
     * Eğer form'dan gemini_api_key gelirse kaydetmeden test edilir.
     */
    public function testConnection(Request $request, GeminiProvider $gemini): JsonResponse
    {
        $cid = $this->companyId();
        $overrideKey = trim((string) $request->input('gemini_api_key', ''));
        $result = $gemini->testConnection($cid, $overrideKey !== '' ? $overrideKey : null);

        return response()->json([
            'ok'      => (bool) ($result['ok'] ?? false),
            'message' => ($result['ok'] ?? false)
                ? ('Bağlantı başarılı — ' . count($result['models'] ?? []) . ' model erişilebilir.')
                : ('Hata: ' . ($result['error'] ?? 'unknown')),
            'models'  => array_slice($result['models'] ?? [], 0, 5),
            'tested_with' => $overrideKey !== '' ? 'form' : 'saved',
        ]);
    }

    /**
     * Tüm aktif PDF kaynakları Gemini'ye senkronize et — butondan tetiklenir.
     */
    public function syncNow(Request $request, KnowledgeBaseService $kb): RedirectResponse
    {
        $cid = $this->companyId();
        $result = $kb->syncAllSources($cid);

        $msg = "Sync tamam: {$result['synced']} yüklendi, {$result['skipped']} atlandı, {$result['failed']} başarısız.";
        if (!empty($result['errors'])) {
            $msg .= ' İlk hata: ' . ($result['errors'][0] ?? '');
        }

        return back()->with('status', $msg);
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    /**
     * Settings sayfaları sadece admin paneli rollerine — senior engellenir.
     */
    private function assertAdminRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403, 'AI Labs ayarlarına sadece yöneticiler erişebilir.');
        }
    }
}
