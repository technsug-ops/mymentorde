<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use App\Models\MarketingIntegrationConnection;
use App\Services\AiWritingService;
use App\Services\Marketing\ExternalMetrics\ExternalMetricsSyncService;
use App\Services\Marketing\IntegrationHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use App\Support\SchemaCache;

class IntegrationController extends Controller
{
    public function index()
    {
        $defaults = $this->defaults();
        $rows = collect();
        if (SchemaCache::hasTable('marketing_admin_settings')) {
            $rows = MarketingAdminSetting::query()
                ->whereIn('setting_key', array_keys($defaults))
                ->get(['setting_key', 'setting_value'])
                ->keyBy('setting_key');
        }

        $providers = $this->providerCatalog();
        $connectionsByProvider = collect();
        if (SchemaCache::hasTable('marketing_integration_connections')) {
            $connectionsByProvider = MarketingIntegrationConnection::query()
                ->whereIn('provider', array_keys($providers))
                ->get()
                ->keyBy('provider');
        }

        $settings = [];
        foreach ($defaults as $key => $meta) {
            $settings[$key] = [
                'label' => $meta['label'],
                'type' => $meta['type'],
                'value' => data_get($rows, "{$key}.setting_value.value", $meta['default']),
                'placeholder' => $meta['placeholder'] ?? '',
            ];
        }

        $healthRows = [];
        foreach ($providers as $provider => $meta) {
            $conn = $connectionsByProvider->get($provider);
            $expiresAt = $conn?->token_expires_at;
            $expiresInDays = null;
            if ($expiresAt) {
                $expiresInDays = now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);
            }

            $healthRows[] = [
                'provider' => $provider,
                'label' => $meta['label'],
                'oauth_supported' => (bool) ($meta['oauth_supported'] ?? false),
                'auth_mode' => (string) ($conn?->auth_mode ?? 'manual'),
                'is_enabled' => (bool) ($conn?->is_enabled ?? false),
                'status' => (string) ($conn?->status ?? 'disconnected'),
                'account_ref' => (string) ($conn?->account_ref ?? '-'),
                'last_checked_at' => $conn?->last_checked_at,
                'last_synced_at' => $conn?->last_synced_at,
                'token_expires_at' => $expiresAt,
                'expires_in_days' => $expiresInDays,
                'last_error' => (string) ($conn?->last_error ?? ''),
            ];
        }

        $aiSvc = app(AiWritingService::class);
        $activeProvider = $aiSvc->currentProvider();
        $providerKeys = [];
        foreach (AiWritingService::PROVIDERS as $p) {
            $k = $aiSvc->apiKeyFor($p);
            $providerKeys[$p] = [
                'has_key'    => $k !== '',
                'masked_key' => $this->maskKey($k),
            ];
        }
        $aiWriter = [
            'enabled'         => $aiSvc->isEnabled(),
            'provider'        => $activeProvider,
            'model'           => $aiSvc->effectiveModel(),
            'has_active_key'  => $aiSvc->isConfigured(),
            'active_masked'   => $this->maskKey($aiSvc->effectiveApiKey()),
            'provider_keys'   => $providerKeys,
            'provider_labels' => [
                'openai'     => 'OpenAI (ChatGPT)',
                'anthropic'  => 'Anthropic (Claude)',
                'gemini'     => 'Google (Gemini)',
                'openrouter' => 'OpenRouter (hepsi tek API)',
            ],
            'provider_defaults' => [
                'openai'     => 'gpt-4o-mini',
                'anthropic'  => 'claude-haiku-4-5-20251001',
                'gemini'     => 'gemini-2.5-flash',
                'openrouter' => 'openai/gpt-4o-mini',
            ],
            'provider_key_hints' => [
                'openai'     => 'sk-proj-... veya sk-...',
                'anthropic'  => 'sk-ant-...',
                'gemini'     => 'AIzaSy...',
                'openrouter' => 'sk-or-...',
            ],
        ];

        return view('marketing-admin.integrations.index', [
            'pageTitle' => 'Integrations',
            'settings' => $settings,
            'healthRows' => $healthRows,
            'tableReady' => SchemaCache::hasTable('marketing_admin_settings'),
            'connectionTableReady' => SchemaCache::hasTable('marketing_integration_connections'),
            'aiWriter' => $aiWriter,
        ]);
    }

    private function maskKey(string $key): string
    {
        $k = trim($key);
        if ($k === '') return '';
        $len = strlen($k);
        if ($len <= 12) return str_repeat('•', max(0, $len - 2)) . substr($k, -2);
        return substr($k, 0, 6) . str_repeat('•', 8) . substr($k, -4);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!SchemaCache::hasTable('marketing_admin_settings')) {
            return redirect('/mktg-admin/integrations')->with('status', 'Ayar tablosu bulunamadi. php artisan migrate calistir.');
        }

        $defaults = $this->defaults();
        $rules = [];
        foreach ($defaults as $key => $meta) {
            $rule = ['nullable'];
            $type = (string) ($meta['type'] ?? 'string');
            if ($type === 'bool') {
                $rule[] = 'boolean';
            } elseif ($type === 'text') {
                $rule[] = 'string';
                $rule[] = 'max:20000';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:500';
            }
            $rules[$key] = $rule;
        }

        $data = $request->validate($rules);
        foreach ($defaults as $key => $meta) {
            $value = $data[$key] ?? null;
            if (($meta['type'] ?? 'string') === 'bool') {
                $value = $request->boolean($key, (bool) $meta['default']);
            } elseif ($value === null || $value === '') {
                $value = $meta['default'];
            } else {
                $value = trim((string) $value);
            }

            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => ['value' => $value],
                    'updated_by_user_id' => $request->user()?->id,
                ]
            );
        }

        $this->syncConnectionsFromSettings($request);

        return redirect('/mktg-admin/integrations')->with('status', 'Entegrasyon ayarlari kaydedildi.');
    }

    public function test(Request $request, string $provider, ExternalMetricsSyncService $service, AiWritingService $aiWritingService)
    {
        $provider = strtolower(trim($provider));
        if (in_array($provider, ['meta', 'ga4', 'google_ads'], true)) {
            $result = $service->sync([$provider], 1, true);
            $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 1;
            $key = $provider.'@company:'.$companyId;
            $row = $result['providers'][$key] ?? null;
            $ok = ($row['status'] ?? '') === 'ok';
            $this->updateConnectionHealth(
                provider: $provider,
                ok: $ok,
                errorMessage: (string) ($row['message'] ?? ''),
                rows: (int) ($row['rows'] ?? 0)
            );

            return response()->json([
                'ok' => $ok,
                'provider' => $provider,
                'status' => $row['status'] ?? 'error',
                'message' => $row['message'] ?? null,
                'rows' => (int) ($row['rows'] ?? 0),
            ]);
        }

        $response = match ($provider) {
            'calendly' => $this->testCalendly(),
            'mailchimp' => $this->testMailchimp(),
            'clickup' => $this->testClickup(),
            'tiktok_ads' => $this->testTiktokAds(),
            'linkedin_ads' => $this->testLinkedInAds(),
            'instagram_insights' => $this->testInstagramInsights(),
            'ai_writer' => $this->testAiWriter($aiWritingService),
            default => response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'error',
                'message' => 'gecersiz provider',
            ], Response::HTTP_UNPROCESSABLE_ENTITY),
        };

        $payload = $response->getData(true);
        $this->updateConnectionHealth(
            provider: $provider,
            ok: (bool) ($payload['ok'] ?? false),
            errorMessage: (string) ($payload['message'] ?? '')
        );

        return $response;
    }

    public function oauthStart(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower(trim($provider));
        $catalog = $this->providerCatalog();
        if (!array_key_exists($provider, $catalog) || !$catalog[$provider]['oauth_supported']) {
            return redirect('/mktg-admin/integrations')->with('status', 'Bu provider icin OAuth desteklenmiyor.');
        }

        $oauth = $this->oauthConfig($provider);
        if (!$oauth['supported']) {
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth bu provider icin henuz baglanmadi.');
        }
        if ($oauth['client_id'] === '' || $oauth['client_secret'] === '') {
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth icin client id/secret eksik.');
        }

        $state = bin2hex(random_bytes(12));
        $request->session()->put('marketing_oauth_state_'.$provider, $state);
        $request->session()->put('marketing_oauth_started_at_'.$provider, now()->toDateTimeString());
        $request->session()->put('marketing_oauth_provider_'.$provider, $provider);

        $query = array_filter([
            'client_id' => $oauth['client_id'],
            'redirect_uri' => $oauth['redirect_uri'],
            'response_type' => 'code',
            'scope' => $oauth['scope'],
            'state' => $state,
            'access_type' => $provider === 'google_ads' ? 'offline' : null,
            'prompt' => $provider === 'google_ads' ? 'consent' : null,
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->away($oauth['authorize_url'].'?'.http_build_query($query));
    }

    public function oauthCallback(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower(trim($provider));
        $stateInSession = (string) $request->session()->get('marketing_oauth_state_'.$provider, '');
        $stateFromQuery = (string) $request->query('state', '');
        if ($stateInSession === '' || $stateFromQuery === '' || !hash_equals($stateInSession, $stateFromQuery)) {
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth callback gecersiz state.');
        }

        if ((string) $request->query('error', '') !== '') {
            $err = (string) $request->query('error', 'oauth_error');
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth hata: '.$err);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth code gelmedi.');
        }

        $oauth = $this->oauthConfig($provider);
        if (!$oauth['supported']) {
            return redirect('/mktg-admin/integrations')->with('status', strtoupper($provider).' OAuth callback su an desteklenmiyor.');
        }

        try {
            $tokenPayload = array_filter([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $oauth['redirect_uri'],
                'client_id' => $oauth['client_id'],
                'client_secret' => $oauth['client_secret'],
            ], fn ($v) => $v !== null && $v !== '');

            $tokenRes = Http::timeout(30)->asForm()->post($oauth['token_url'], $tokenPayload);
            if (!$tokenRes->successful()) {
                return redirect('/mktg-admin/integrations')
                    ->with('status', strtoupper($provider).' OAuth token hatasi: http '.$tokenRes->status());
            }

            $json = $tokenRes->json();
            $accessToken = trim((string) ($json['access_token'] ?? ''));
            $refreshToken = trim((string) ($json['refresh_token'] ?? ''));
            $expiresIn = (int) ($json['expires_in'] ?? 3600);

            if ($accessToken === '') {
                return redirect('/mktg-admin/integrations')
                    ->with('status', strtoupper($provider).' OAuth token response gecersiz (access_token yok).');
            }

            $expiresAt = now()->addSeconds(max(300, $expiresIn));
            MarketingIntegrationConnection::query()->updateOrCreate(
                ['provider' => $provider],
                [
                    'auth_mode' => 'oauth',
                    'status' => 'connected',
                    'is_enabled' => true,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken !== '' ? $refreshToken : null,
                    'token_expires_at' => $expiresAt,
                    'last_checked_at' => now(),
                    'last_error' => null,
                    'updated_by_user_id' => $request->user()?->id,
                ]
            );

            if ($oauth['setting_access_token'] !== '') {
                $this->setSettingValue($oauth['setting_access_token'], $accessToken, $request);
            }
            if ($oauth['setting_refresh_token'] !== '' && $refreshToken !== '') {
                $this->setSettingValue($oauth['setting_refresh_token'], $refreshToken, $request);
            }

            return redirect('/mktg-admin/integrations')
                ->with('status', strtoupper($provider).' OAuth baglandi. Token kaydedildi.');
        } catch (\Throwable $e) {
            return redirect('/mktg-admin/integrations')
                ->with('status', strtoupper($provider).' OAuth callback exception: '.mb_substr($e->getMessage(), 0, 250));
        }
    }

    public function refreshToken(Request $request, string $provider)
    {
        $provider = strtolower(trim($provider));
        if (!SchemaCache::hasTable('marketing_integration_connections')) {
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'error',
                'message' => 'connection tablosu yok',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $row = MarketingIntegrationConnection::query()
            ->where('provider', $provider)
            ->first();
        if (!$row) {
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'error',
                'message' => 'connection kaydi yok',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$row->refresh_token) {
            $row->update([
                'status' => 'pending',
                'last_checked_at' => now(),
                'last_error' => 'refresh token yok',
                'updated_by_user_id' => $request->user()?->id,
            ]);
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'pending',
                'message' => 'refresh token yok',
            ]);
        }

        if (!in_array($provider, ['google_ads', 'ga4'], true)) {
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'pending',
                'message' => 'Bu provider icin refresh implementasyonu sonraki adimda eklenecek.',
            ]);
        }

        $clientId = $provider === 'ga4'
            ? $this->settingValue('ext_ga4_oauth_client_id')
            : $this->settingValue('ext_google_ads_oauth_client_id');
        $clientSecret = $provider === 'ga4'
            ? $this->settingValue('ext_ga4_oauth_client_secret')
            : $this->settingValue('ext_google_ads_oauth_client_secret');
        $refreshToken = trim((string) ($row->refresh_token ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            $row->update([
                'status' => 'pending',
                'last_checked_at' => now(),
                'last_error' => $provider.' oauth client_id/client_secret eksik',
                'updated_by_user_id' => $request->user()?->id,
            ]);
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'pending',
                'message' => $provider.' oauth client_id/client_secret eksik',
            ]);
        }

        try {
            $res = Http::timeout(30)->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if (!$res->successful()) {
                $msg = 'http '.$res->status();
                $row->update([
                    'status' => 'error',
                    'last_checked_at' => now(),
                    'last_error' => $msg,
                    'updated_by_user_id' => $request->user()?->id,
                ]);
                return response()->json([
                    'ok' => false,
                    'provider' => $provider,
                    'status' => 'error',
                    'message' => $msg,
                ]);
            }

            $payload = $res->json();
            $newAccessToken = trim((string) ($payload['access_token'] ?? ''));
            $expiresIn = (int) ($payload['expires_in'] ?? 3600);
            $newRefreshToken = trim((string) ($payload['refresh_token'] ?? ''));
            if ($newAccessToken === '') {
                return response()->json([
                    'ok' => false,
                    'provider' => $provider,
                    'status' => 'error',
                    'message' => 'access_token donmedi',
                ]);
            }

            $expiresAt = now()->addSeconds(max(300, $expiresIn));
            $row->update([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken !== '' ? $newRefreshToken : $refreshToken,
                'token_expires_at' => $expiresAt,
                'status' => 'connected',
                'last_checked_at' => now(),
                'last_error' => null,
                'updated_by_user_id' => $request->user()?->id,
            ]);

            if ($provider === 'ga4') {
                $this->setSettingValue('ext_ga4_access_token', $newAccessToken, $request);
                if ($newRefreshToken !== '') {
                    $this->setSettingValue('ext_ga4_refresh_token', $newRefreshToken, $request);
                }
            } else {
                $this->setSettingValue('ext_google_ads_access_token', $newAccessToken, $request);
                if ($newRefreshToken !== '') {
                    $this->setSettingValue('ext_google_ads_refresh_token', $newRefreshToken, $request);
                }
            }

            return response()->json([
                'ok' => true,
                'provider' => $provider,
                'status' => 'connected',
                'message' => 'token yenilendi',
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            $msg = mb_substr($e->getMessage(), 0, 500);
            $row->update([
                'status' => 'error',
                'last_checked_at' => now(),
                'last_error' => $msg,
                'updated_by_user_id' => $request->user()?->id,
            ]);
            return response()->json([
                'ok' => false,
                'provider' => $provider,
                'status' => 'error',
                'message' => $msg,
            ]);
        }
    }

    private function defaults(): array
    {
        return [
            'ext_meta_enabled' => ['label' => 'Meta aktif', 'type' => 'bool', 'default' => false],
            'ext_meta_api_version' => ['label' => 'Meta API version', 'type' => 'string', 'default' => 'v21.0', 'placeholder' => 'v21.0'],
            'ext_meta_ad_account_id' => ['label' => 'Meta Ad Account ID', 'type' => 'string', 'default' => '', 'placeholder' => 'act_123456789'],
            'ext_meta_access_token' => ['label' => 'Meta Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'EAAB...'],
            'ext_meta_oauth_client_id' => ['label' => 'Meta OAuth App ID', 'type' => 'string', 'default' => '', 'placeholder' => '1234567890'],
            'ext_meta_oauth_client_secret' => ['label' => 'Meta OAuth App Secret', 'type' => 'string', 'default' => '', 'placeholder' => 'app-secret'],

            'ext_ga4_enabled' => ['label' => 'GA4 aktif', 'type' => 'bool', 'default' => false],
            'ext_ga4_property_id' => ['label' => 'GA4 Property ID', 'type' => 'string', 'default' => '', 'placeholder' => '123456789'],
            'ext_ga4_credentials' => ['label' => 'GA4 Credentials JSON path', 'type' => 'string', 'default' => '', 'placeholder' => 'storage/app/ga4-service-account.json'],
            'ext_ga4_access_token' => ['label' => 'GA4 Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'ya29...'],
            'ext_ga4_refresh_token' => ['label' => 'GA4 Refresh Token', 'type' => 'string', 'default' => '', 'placeholder' => '1//0g...'],
            'ext_ga4_oauth_client_id' => ['label' => 'GA4 OAuth Client ID', 'type' => 'string', 'default' => '', 'placeholder' => 'xxxx.apps.googleusercontent.com'],
            'ext_ga4_oauth_client_secret' => ['label' => 'GA4 OAuth Client Secret', 'type' => 'string', 'default' => '', 'placeholder' => 'GOCSPX-...'],

            'ext_google_ads_enabled' => ['label' => 'Google Ads aktif', 'type' => 'bool', 'default' => false],
            'ext_google_ads_customer_id' => ['label' => 'Google Ads Customer ID', 'type' => 'string', 'default' => '', 'placeholder' => '123-456-7890'],
            'ext_google_ads_login_customer_id' => ['label' => 'Login Customer ID', 'type' => 'string', 'default' => '', 'placeholder' => '123-456-7890'],
            'ext_google_ads_developer_token' => ['label' => 'Developer Token', 'type' => 'string', 'default' => '', 'placeholder' => 'dev-token'],
            'ext_google_ads_access_token' => ['label' => 'Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'ya29...'],
            'ext_google_ads_refresh_token' => ['label' => 'Refresh Token', 'type' => 'string', 'default' => '', 'placeholder' => '1//0g...'],
            'ext_google_ads_oauth_client_id' => ['label' => 'OAuth Client ID', 'type' => 'string', 'default' => '', 'placeholder' => 'xxxx.apps.googleusercontent.com'],
            'ext_google_ads_oauth_client_secret' => ['label' => 'OAuth Client Secret', 'type' => 'string', 'default' => '', 'placeholder' => 'GOCSPX-...'],

            'ext_tiktok_ads_enabled' => ['label' => 'TikTok Ads aktif', 'type' => 'bool', 'default' => false],
            'ext_tiktok_ads_advertiser_id' => ['label' => 'TikTok Advertiser ID', 'type' => 'string', 'default' => '', 'placeholder' => '1234567890123456789'],
            'ext_tiktok_ads_access_token' => ['label' => 'TikTok Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'tt-...'],
            'ext_tiktok_ads_oauth_client_id' => ['label' => 'TikTok OAuth Client Key', 'type' => 'string', 'default' => '', 'placeholder' => 'client-key'],
            'ext_tiktok_ads_oauth_client_secret' => ['label' => 'TikTok OAuth Client Secret', 'type' => 'string', 'default' => '', 'placeholder' => 'client-secret'],

            'ext_linkedin_ads_enabled' => ['label' => 'LinkedIn Ads aktif', 'type' => 'bool', 'default' => false],
            'ext_linkedin_ads_account_id' => ['label' => 'LinkedIn Account ID', 'type' => 'string', 'default' => '', 'placeholder' => 'urn:li:sponsoredAccount:123456'],
            'ext_linkedin_ads_access_token' => ['label' => 'LinkedIn Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'AQX...'],
            'ext_linkedin_ads_oauth_client_id' => ['label' => 'LinkedIn OAuth Client ID', 'type' => 'string', 'default' => '', 'placeholder' => 'linkedin-client-id'],
            'ext_linkedin_ads_oauth_client_secret' => ['label' => 'LinkedIn OAuth Client Secret', 'type' => 'string', 'default' => '', 'placeholder' => 'linkedin-client-secret'],

            'ext_instagram_insights_enabled' => ['label' => 'Instagram Insights aktif', 'type' => 'bool', 'default' => false],
            'ext_instagram_business_account_id' => ['label' => 'Instagram Business Account ID', 'type' => 'string', 'default' => '', 'placeholder' => '1784...'],
            'ext_instagram_access_token' => ['label' => 'Instagram Access Token', 'type' => 'string', 'default' => '', 'placeholder' => 'IGQ...'],

            'ext_calendly_enabled' => ['label' => 'Calendly aktif', 'type' => 'bool', 'default' => false],
            'ext_calendly_api_key' => ['label' => 'Calendly API Key', 'type' => 'string', 'default' => '', 'placeholder' => 'CALENDLY_TOKEN'],

            'ext_mailchimp_enabled' => ['label' => 'Mailchimp aktif', 'type' => 'bool', 'default' => false],
            'ext_mailchimp_api_key' => ['label' => 'Mailchimp API Key', 'type' => 'string', 'default' => '', 'placeholder' => 'xxxx-us21'],

            'ext_clickup_enabled' => ['label' => 'ClickUp aktif', 'type' => 'bool', 'default' => false],
            'ext_clickup_api_key' => ['label' => 'ClickUp API Key', 'type' => 'string', 'default' => '', 'placeholder' => 'pk_...'],

            'ai_writer_prompt_motivation' => [
                'label' => 'AI Prompt - Motivasyon Mektubu (System Prompt Override)',
                'type' => 'text',
                'default' => '',
                'placeholder' => 'Bos birak: default prompt kullanilir',
            ],
            'ai_writer_prompt_reference' => [
                'label' => 'AI Prompt - Referans Mektubu (System Prompt Override)',
                'type' => 'text',
                'default' => '',
                'placeholder' => 'Bos birak: default prompt kullanilir',
            ],
        ];
    }

    /**
     * @return array<string,array{label:string,oauth_supported:bool}>
     */
    private function providerCatalog(): array
    {
        return [
            'meta' => ['label' => 'Meta Ads', 'oauth_supported' => true],
            'ga4' => ['label' => 'Google Analytics 4', 'oauth_supported' => true],
            'google_ads' => ['label' => 'Google Ads', 'oauth_supported' => true],
            'tiktok_ads' => ['label' => 'TikTok Ads', 'oauth_supported' => false],
            'linkedin_ads' => ['label' => 'LinkedIn Ads', 'oauth_supported' => true],
            'instagram_insights' => ['label' => 'Instagram Insights', 'oauth_supported' => true],
            'calendly' => ['label' => 'Calendly', 'oauth_supported' => false],
            'mailchimp' => ['label' => 'Mailchimp', 'oauth_supported' => false],
            'clickup' => ['label' => 'ClickUp', 'oauth_supported' => false],
            'ai_writer' => ['label' => 'AI Writer API', 'oauth_supported' => false],
        ];
    }

    private function syncConnectionsFromSettings(Request $request): void
    {
        if (!SchemaCache::hasTable('marketing_integration_connections')) {
            return;
        }

        $map = [
            'meta' => [
                'enabled' => $request->boolean('ext_meta_enabled'),
                'account_ref' => trim((string) $request->input('ext_meta_ad_account_id', '')),
                'access_token' => trim((string) $request->input('ext_meta_access_token', '')),
                'meta' => ['api_version' => trim((string) $request->input('ext_meta_api_version', 'v21.0'))],
            ],
            'ga4' => [
                'enabled' => $request->boolean('ext_ga4_enabled'),
                'account_ref' => trim((string) $request->input('ext_ga4_property_id', '')),
                'access_token' => trim((string) $request->input('ext_ga4_access_token', '')),
                'refresh_token' => trim((string) $request->input('ext_ga4_refresh_token', '')),
                'meta' => [
                    'credentials' => trim((string) $request->input('ext_ga4_credentials', '')),
                    'oauth_client_id' => trim((string) $request->input('ext_ga4_oauth_client_id', '')),
                ],
            ],
            'google_ads' => [
                'enabled' => $request->boolean('ext_google_ads_enabled'),
                'account_ref' => trim((string) $request->input('ext_google_ads_customer_id', '')),
                'access_token' => trim((string) $request->input('ext_google_ads_access_token', '')),
                'refresh_token' => trim((string) $request->input('ext_google_ads_refresh_token', '')),
                'meta' => [
                    'login_customer_id' => trim((string) $request->input('ext_google_ads_login_customer_id', '')),
                    'developer_token' => trim((string) $request->input('ext_google_ads_developer_token', '')),
                    'oauth_client_id' => trim((string) $request->input('ext_google_ads_oauth_client_id', '')),
                ],
            ],
            'tiktok_ads' => [
                'enabled' => $request->boolean('ext_tiktok_ads_enabled'),
                'account_ref' => trim((string) $request->input('ext_tiktok_ads_advertiser_id', '')),
                'access_token' => trim((string) $request->input('ext_tiktok_ads_access_token', '')),
                'meta' => [],
            ],
            'linkedin_ads' => [
                'enabled' => $request->boolean('ext_linkedin_ads_enabled'),
                'account_ref' => trim((string) $request->input('ext_linkedin_ads_account_id', '')),
                'access_token' => trim((string) $request->input('ext_linkedin_ads_access_token', '')),
                'meta' => [],
            ],
            'instagram_insights' => [
                'enabled' => $request->boolean('ext_instagram_insights_enabled'),
                'account_ref' => trim((string) $request->input('ext_instagram_business_account_id', '')),
                'access_token' => trim((string) $request->input('ext_instagram_access_token', '')),
                'meta' => [],
            ],
            'calendly' => [
                'enabled' => $request->boolean('ext_calendly_enabled'),
                'account_ref' => 'calendly',
                'access_token' => trim((string) $request->input('ext_calendly_api_key', '')),
                'meta' => [],
            ],
            'mailchimp' => [
                'enabled' => $request->boolean('ext_mailchimp_enabled'),
                'account_ref' => 'mailchimp',
                'access_token' => trim((string) $request->input('ext_mailchimp_api_key', '')),
                'meta' => [],
            ],
            'clickup' => [
                'enabled' => $request->boolean('ext_clickup_enabled'),
                'account_ref' => 'clickup',
                'access_token' => trim((string) $request->input('ext_clickup_api_key', '')),
                'meta' => [],
            ],
        ];

        foreach ($map as $provider => $row) {
            $hasToken = trim((string) ($row['access_token'] ?? '')) !== '';
            MarketingIntegrationConnection::query()->updateOrCreate(
                ['provider' => $provider],
                [
                    'auth_mode' => 'manual',
                    'is_enabled' => (bool) $row['enabled'],
                    'status' => (bool) $row['enabled'] ? ($hasToken ? 'connected' : 'pending') : 'disabled',
                    'account_ref' => (string) ($row['account_ref'] ?: null),
                    'access_token' => $row['access_token'] !== '' ? $row['access_token'] : null,
                    'refresh_token' => !empty($row['refresh_token']) ? $row['refresh_token'] : null,
                    'meta' => $row['meta'],
                    'updated_by_user_id' => $request->user()?->id,
                ]
            );
        }
    }

    private function updateConnectionHealth(string $provider, bool $ok, string $errorMessage = '', int $rows = 0): void
    {
        if (!SchemaCache::hasTable('marketing_integration_connections')) {
            return;
        }

        $now = now();
        MarketingIntegrationConnection::query()->updateOrCreate(
            ['provider' => $provider],
            [
                'status' => $ok ? 'connected' : 'error',
                'last_checked_at' => $now,
                'last_synced_at' => $ok && $rows > 0 ? $now : null,
                'last_error' => $ok ? null : mb_substr($errorMessage, 0, 500),
            ]
        );
    }

    private function testCalendly()
    {
        $token = $this->settingValue('ext_calendly_api_key');
        if ($token === '') {
            return response()->json(['ok' => false, 'provider' => 'calendly', 'status' => 'error', 'message' => 'calendly api key bos']);
        }

        $res = Http::timeout(20)
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->get('https://api.calendly.com/users/me');

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'calendly',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testMailchimp()
    {
        $apiKey = $this->settingValue('ext_mailchimp_api_key');
        if ($apiKey === '') {
            return response()->json(['ok' => false, 'provider' => 'mailchimp', 'status' => 'error', 'message' => 'mailchimp api key bos']);
        }
        $parts = explode('-', $apiKey);
        $dc = trim((string) end($parts));
        if ($dc === '' || count($parts) < 2) {
            return response()->json(['ok' => false, 'provider' => 'mailchimp', 'status' => 'error', 'message' => 'mailchimp key format gecersiz']);
        }

        $url = 'https://'.$dc.'.api.mailchimp.com/3.0/ping';
        $res = Http::timeout(20)->withBasicAuth('mentorde', $apiKey)->get($url);

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'mailchimp',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testClickup()
    {
        $token = $this->settingValue('ext_clickup_api_key');
        if ($token === '') {
            return response()->json(['ok' => false, 'provider' => 'clickup', 'status' => 'error', 'message' => 'clickup api key bos']);
        }

        $res = Http::timeout(20)
            ->withHeaders(['Authorization' => $token])
            ->get('https://api.clickup.com/api/v2/user');

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'clickup',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testTiktokAds()
    {
        $token = $this->settingValue('ext_tiktok_ads_access_token');
        if ($token === '') {
            return response()->json(['ok' => false, 'provider' => 'tiktok_ads', 'status' => 'error', 'message' => 'tiktok access token bos']);
        }

        $res = Http::timeout(20)
            ->withHeaders(['Access-Token' => $token])
            ->get('https://business-api.tiktok.com/open_api/v1.3/user/info/');

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'tiktok_ads',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testLinkedInAds()
    {
        $token = $this->settingValue('ext_linkedin_ads_access_token');
        if ($token === '') {
            return response()->json(['ok' => false, 'provider' => 'linkedin_ads', 'status' => 'error', 'message' => 'linkedin access token bos']);
        }

        $res = Http::timeout(20)
            ->withToken($token)
            ->get('https://api.linkedin.com/v2/me');

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'linkedin_ads',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testInstagramInsights()
    {
        $token = $this->settingValue('ext_instagram_access_token');
        $accountId = $this->settingValue('ext_instagram_business_account_id');
        if ($token === '') {
            return response()->json(['ok' => false, 'provider' => 'instagram_insights', 'status' => 'error', 'message' => 'instagram access token bos']);
        }
        if ($accountId === '') {
            return response()->json(['ok' => false, 'provider' => 'instagram_insights', 'status' => 'error', 'message' => 'instagram business account id bos']);
        }

        $url = 'https://graph.facebook.com/v21.0/'.urlencode($accountId);
        $res = Http::timeout(20)->get($url, [
            'fields' => 'id,username',
            'access_token' => $token,
        ]);

        return response()->json([
            'ok' => $res->successful(),
            'provider' => 'instagram_insights',
            'status' => $res->successful() ? 'ok' : 'error',
            'message' => $res->successful() ? 'connection ok' : ('http '.$res->status()),
            'rows' => 0,
        ]);
    }

    private function testAiWriter(AiWritingService $aiWritingService)
    {
        if (!$aiWritingService->isEnabled()) {
            return response()->json([
                'ok' => false,
                'provider' => 'ai_writer',
                'status' => 'disabled',
                'message' => 'AI_WRITER_ENABLED=false',
                'rows' => 0,
            ]);
        }

        if (!$aiWritingService->isConfigured()) {
            return response()->json([
                'ok' => false,
                'provider' => 'ai_writer',
                'status' => 'error',
                'message' => 'AI_WRITER_API_KEY eksik',
                'rows' => 0,
            ]);
        }

        $res = $aiWritingService->improveGermanDocument('motivation', 'Ich mochte in Deutschland studieren, weil ich mich fur Architektur interessiere.', [
            'target_program' => 'Architektur',
            'mode' => 'health_check',
        ]);

        return response()->json([
            'ok' => (bool) ($res['ok'] ?? false),
            'provider' => 'ai_writer',
            'status' => (bool) ($res['ok'] ?? false) ? 'ok' : 'error',
            'message' => (bool) ($res['ok'] ?? false)
                ? 'connection ok | provider: '.($res['provider'] ?? '-').' | model: '.($res['model'] ?? '-')
                : (string) ($res['error'] ?? 'ai_test_failed'),
            'rows' => 0,
        ]);
    }

    private function settingValue(string $key): string
    {
        $row = MarketingAdminSetting::query()
            ->where('setting_key', $key)
            ->first(['setting_value']);
        return trim((string) data_get($row, 'setting_value.value', ''));
    }

    /**
     * AI Writer çoklu provider ayarlarını kaydeder.
     * Her provider'ın kendi key'i vardır; aktif olan `ai_writer_provider` ile seçilir.
     * Boş gelen key input'u mevcut değeri korur (UI'den silmek için ayrı bir yol yok — şimdilik yeterli).
     */
    public function updateAiWriter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_writer_enabled'         => ['nullable', 'boolean'],
            'ai_writer_provider'        => ['nullable', 'string', 'in:openai,anthropic,gemini,openrouter'],
            'ai_writer_model'           => ['nullable', 'string', 'max:120'],
            'ai_writer_openai_key'      => ['nullable', 'string', 'max:500'],
            'ai_writer_anthropic_key'   => ['nullable', 'string', 'max:500'],
            'ai_writer_gemini_key'      => ['nullable', 'string', 'max:500'],
            'ai_writer_openrouter_key'  => ['nullable', 'string', 'max:500'],
        ]);

        $userId = $request->user()?->id;

        MarketingAdminSetting::setValue(
            'ai_writer_enabled',
            $request->boolean('ai_writer_enabled') ? '1' : '0',
            $userId
        );

        $provider = (string) ($validated['ai_writer_provider'] ?? 'openai');
        MarketingAdminSetting::setValue('ai_writer_provider', $provider, $userId);

        $newModel = trim((string) ($validated['ai_writer_model'] ?? ''));
        if ($newModel !== '') {
            MarketingAdminSetting::setValue('ai_writer_model', $newModel, $userId);
        }

        foreach (['openai', 'anthropic', 'gemini', 'openrouter'] as $p) {
            $field = "ai_writer_{$p}_key";
            $val = trim((string) ($validated[$field] ?? ''));
            if ($val !== '') {
                MarketingAdminSetting::setValue($field, $val, $userId);
            }
        }

        return redirect('/mktg-admin/integrations')->with('status', 'AI Writer ayarları kaydedildi.');
    }

    /**
     * @return array{
     *   supported: bool,
     *   authorize_url: string,
     *   token_url: string,
     *   client_id: string,
     *   client_secret: string,
     *   scope: string,
     *   redirect_uri: string,
     *   setting_access_token: string,
     *   setting_refresh_token: string
     * }
     */
    private function oauthConfig(string $provider): array
    {
        $appUrl = rtrim((string) config('app.url', url('/')), '/');
        $redirect = $appUrl.'/mktg-admin/integrations/oauth/'.$provider.'/callback';

        return match ($provider) {
            'google_ads' => [
                'supported' => true,
                'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'client_id' => $this->settingValue('ext_google_ads_oauth_client_id'),
                'client_secret' => $this->settingValue('ext_google_ads_oauth_client_secret'),
                'scope' => 'https://www.googleapis.com/auth/adwords',
                'redirect_uri' => $redirect,
                'setting_access_token' => 'ext_google_ads_access_token',
                'setting_refresh_token' => 'ext_google_ads_refresh_token',
            ],
            'ga4' => [
                'supported' => true,
                'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'client_id' => $this->settingValue('ext_ga4_oauth_client_id'),
                'client_secret' => $this->settingValue('ext_ga4_oauth_client_secret'),
                'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
                'redirect_uri' => $redirect,
                'setting_access_token' => 'ext_ga4_access_token',
                'setting_refresh_token' => 'ext_ga4_refresh_token',
            ],
            'meta' => [
                'supported' => true,
                'authorize_url' => 'https://www.facebook.com/v21.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v21.0/oauth/access_token',
                'client_id' => $this->settingValue('ext_meta_oauth_client_id'),
                'client_secret' => $this->settingValue('ext_meta_oauth_client_secret'),
                'scope' => 'ads_read,ads_management,business_management',
                'redirect_uri' => $redirect,
                'setting_access_token' => 'ext_meta_access_token',
                'setting_refresh_token' => '',
            ],
            'linkedin_ads' => [
                'supported' => true,
                'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
                'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
                'client_id' => $this->settingValue('ext_linkedin_ads_oauth_client_id'),
                'client_secret' => $this->settingValue('ext_linkedin_ads_oauth_client_secret'),
                'scope' => 'r_ads,rw_ads',
                'redirect_uri' => $redirect,
                'setting_access_token' => 'ext_linkedin_ads_access_token',
                'setting_refresh_token' => '',
            ],
            'instagram_insights' => [
                'supported' => true,
                'authorize_url' => 'https://www.facebook.com/v21.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v21.0/oauth/access_token',
                'client_id' => $this->settingValue('ext_meta_oauth_client_id'),
                'client_secret' => $this->settingValue('ext_meta_oauth_client_secret'),
                'scope' => 'instagram_basic,instagram_manage_insights,pages_read_engagement',
                'redirect_uri' => $redirect,
                'setting_access_token' => 'ext_instagram_access_token',
                'setting_refresh_token' => '',
            ],
            default => [
                'supported' => false,
                'authorize_url' => '',
                'token_url' => '',
                'client_id' => '',
                'client_secret' => '',
                'scope' => '',
                'redirect_uri' => $redirect,
                'setting_access_token' => '',
                'setting_refresh_token' => '',
            ],
        };
    }

    private function setSettingValue(string $key, string $value, Request $request): void
    {
        MarketingAdminSetting::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => ['value' => $value],
                'updated_by_user_id' => $request->user()?->id,
            ]
        );
    }

    // ─── 2.5 Entegrasyon Sağlık Monitörü ────────────────────────────────────

    /**
     * GET /mktg-admin/integrations/health
     * Tüm bağlı entegrasyonların token + durum kontrolü.
     */
    public function health(): \Illuminate\Http\JsonResponse
    {
        $svc     = app(IntegrationHealthService::class);
        $summary = $svc->run();

        $connections = MarketingIntegrationConnection::query()
            ->orderBy('provider')
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'provider'        => $c->provider,
                'status'          => $c->status,
                'last_checked_at' => $c->last_checked_at?->toDateTimeString(),
                'last_error'      => $c->last_error,
                'token_expires_at'=> $c->token_expires_at?->toDateTimeString(),
                'is_enabled'      => (bool) $c->is_enabled,
            ]);

        return response()->json([
            'ok'          => true,
            'summary'     => $summary,
            'connections' => $connections,
            'checked_at'  => now()->toDateTimeString(),
        ]);
    }
}
