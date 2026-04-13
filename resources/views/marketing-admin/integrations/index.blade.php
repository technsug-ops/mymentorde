@extends('marketing-admin.layouts.app')

@section('title', 'Entegrasyonlar')

@section('page_subtitle', 'Entegrasyonlar — external platform bağlantıları, API ayarları ve sağlık matrisi')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select, .wf-field textarea {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.wf-field textarea { height:90px; padding:8px 10px; resize:vertical; }
.wf-field input:focus, .wf-field select:focus, .wf-field textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10);
}

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

.test-line { font-size:12px; color:var(--u-muted,#64748b); margin-top:4px; }
</style>

@if(session('status'))
<div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);margin-bottom:12px;">
    {{ session('status') }}
</div>
@endif
@if($errors->any())
<div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);margin-bottom:12px;">
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
</div>
@endif

<div style="display:grid;gap:12px;">

    {{-- AI Writer — multi-provider --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">AI Writer <span style="font-size:var(--tx-xs);font-weight:400;color:var(--u-muted,#64748b);">— Doküman Almanca Destek (multi-provider)</span></div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:10px;">
            4 provider desteklenir: <strong>OpenAI</strong>, <strong>Anthropic Claude</strong>, <strong>Google Gemini</strong>, <strong>OpenRouter</strong>.
            Her provider için ayrı key saklanır; aktif olanı dropdown'dan seç.<br>
            @if($aiWriter['has_active_key'])
                <span style="color:#1f6d35;font-weight:600;">• Aktif: {{ $aiWriter['provider_labels'][$aiWriter['provider']] ?? $aiWriter['provider'] }} — key: {{ $aiWriter['active_masked'] }} — model: {{ $aiWriter['model'] }}</span>
            @else
                <span style="color:#b12525;font-weight:600;">• Aktif provider'da key yok — istek başarısız olur</span>
            @endif
        </div>

        <form method="POST" action="/mktg-admin/integrations/ai-writer" style="display:grid;gap:12px;max-width:680px;">
            @csrf

            <label style="display:flex;align-items:center;gap:8px;font-size:var(--tx-xs);">
                <input type="checkbox" name="ai_writer_enabled" value="1" {{ $aiWriter['enabled'] ? 'checked' : '' }}>
                <span>AI Writer aktif</span>
            </label>

            <label style="display:grid;gap:4px;font-size:var(--tx-xs);">
                <span><strong>Aktif Provider</strong></span>
                <select name="ai_writer_provider" style="padding:7px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:var(--tx-xs);">
                    @foreach($aiWriter['provider_labels'] as $p => $label)
                        <option value="{{ $p }}" {{ $aiWriter['provider'] === $p ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:grid;gap:4px;font-size:var(--tx-xs);">
                <span>Model <span style="color:var(--u-muted,#64748b);">— boş = provider default'u</span></span>
                <input type="text" name="ai_writer_model" value="{{ $aiWriter['model'] }}"
                       placeholder="{{ $aiWriter['provider_defaults'][$aiWriter['provider']] ?? 'gpt-4o-mini' }}"
                       style="padding:7px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:var(--tx-xs);font-family:monospace;">
                <span style="color:var(--u-muted,#64748b);font-size:10px;">
                    Default'lar — OpenAI: <code>gpt-4o-mini</code> · Claude: <code>claude-haiku-4-5-20251001</code> · Gemini: <code>gemini-2.5-flash</code> · OpenRouter: <code>openai/gpt-4o-mini</code>
                </span>
            </label>

            <div style="border-top:1px dashed var(--u-line);padding-top:10px;display:grid;gap:10px;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted,#64748b);">API Keys (boş bırakırsan mevcut korunur)</div>

                @foreach($aiWriter['provider_labels'] as $p => $label)
                    @php $pk = $aiWriter['provider_keys'][$p]; @endphp
                    <label style="display:grid;gap:3px;font-size:var(--tx-xs);">
                        <span>
                            {{ $label }}
                            @if($pk['has_key'])
                                <span style="color:#1f6d35;">✓ {{ $pk['masked_key'] }}</span>
                            @else
                                <span style="color:#b12525;">— key yok</span>
                            @endif
                        </span>
                        <input type="password" name="ai_writer_{{ $p }}_key" autocomplete="new-password"
                               placeholder="{{ $aiWriter['provider_key_hints'][$p] ?? '' }}"
                               style="padding:6px 10px;border:1px solid var(--u-line);border-radius:6px;font-size:var(--tx-xs);font-family:monospace;">
                    </label>
                @endforeach
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-top:4px;">
                <button class="btn ok" type="submit" style="font-size:var(--tx-xs);padding:6px 18px;">Kaydet</button>
                <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 14px;" onclick="testProvider('ai_writer')">Aktif Provider'ı Test Et</button>
                <span id="ai_writerTestStatus" class="test-line">test bekliyor</span>
            </div>
        </form>

        @if(session('status'))
            <div style="margin-top:10px;padding:8px 12px;background:#e6f4ea;color:#1f6d35;border-radius:6px;font-size:var(--tx-xs);">
                ✓ {{ session('status') }}
            </div>
        @endif
    </div>

    {{-- Connection Health Matrix --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Connection Health Matrix</div>
        @if(!$connectionTableReady)
        <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:8px;padding:10px 14px;font-size:var(--tx-sm);">
            <code>marketing_integration_connections</code> tablosu yok. <code>php artisan migrate</code> çalıştır.
        </div>
        @else
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Provider</th>
                    <th>Auth</th>
                    <th>Durum</th>
                    <th>Token Son</th>
                    <th>Last Check</th>
                    <th>Aksiyon</th>
                </tr></thead>
                <tbody>
                    @foreach(($healthRows ?? []) as $row)
                    @php
                        $statusBadge = match($row['status']) {
                            'connected' => 'ok',
                            'expiring'  => 'warn',
                            'expired', 'error' => 'danger',
                            'pending'   => 'pending',
                            default     => 'info',
                        };
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $row['label'] }}</strong><br>
                            <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['provider'] }}</span>
                            @if(!empty($row['account_ref']) && $row['account_ref'] !== '-')
                            <br><span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">acc: {{ $row['account_ref'] }}</span>
                            @endif
                        </td>
                        <td style="font-size:var(--tx-xs);">{{ $row['auth_mode'] }}</td>
                        <td>
                            <span class="badge {{ $statusBadge }}">{{ $row['status'] }}</span>
                            @if(!empty($row['last_error']))
                            <br><span style="font-size:var(--tx-xs);color:var(--u-danger,#dc2626);">{{ $row['last_error'] }}</span>
                            @endif
                        </td>
                        <td style="font-size:var(--tx-xs);">
                            @if($row['token_expires_at'])
                            {{ $row['token_expires_at']->format('Y-m-d H:i') }}
                            <br><span style="color:var(--u-muted,#64748b);">kalan: {{ $row['expires_in_days'] }} gün</span>
                            @else
                            <span style="color:var(--u-muted,#64748b);">—</span>
                            @endif
                        </td>
                        <td style="font-size:var(--tx-xs);">
                            @if($row['last_checked_at']) {{ $row['last_checked_at']->format('Y-m-d H:i') }}
                            @else <span style="color:var(--u-muted,#64748b);">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:3px 9px;" onclick="testProvider('{{ $row['provider'] }}')">Test</button>
                                <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:3px 9px;" onclick="refreshProviderToken('{{ $row['provider'] }}')">Token Yenile</button>
                                @if($row['oauth_supported'])
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 9px;" href="/mktg-admin/integrations/oauth/{{ $row['provider'] }}/start">OAuth</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Settings Form --}}
    <form method="POST" action="/mktg-admin/integrations" id="integrations-form">
        @csrf @method('PUT')

        <div class="grid2" style="gap:12px;">

            {{-- Meta Ads --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Meta Ads</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_meta_enabled']['label'] }}</label>
                        <select name="ext_meta_enabled">
                            <option value="1" @selected((bool)($settings['ext_meta_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_meta_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_meta_api_version']['label'] }}</label><input name="ext_meta_api_version" value="{{ $settings['ext_meta_api_version']['value'] ?? '' }}" placeholder="{{ $settings['ext_meta_api_version']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_meta_ad_account_id']['label'] }}</label><input name="ext_meta_ad_account_id" value="{{ $settings['ext_meta_ad_account_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_meta_ad_account_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_meta_access_token']['label'] }}</label><input name="ext_meta_access_token" value="{{ $settings['ext_meta_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_meta_access_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_meta_oauth_client_id']['label'] }}</label><input name="ext_meta_oauth_client_id" value="{{ $settings['ext_meta_oauth_client_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_meta_oauth_client_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_meta_oauth_client_secret']['label'] }}</label><input name="ext_meta_oauth_client_secret" value="{{ $settings['ext_meta_oauth_client_secret']['value'] ?? '' }}" placeholder="{{ $settings['ext_meta_oauth_client_secret']['placeholder'] ?? '' }}"></div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                        <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('meta')">Meta Test</button>
                        <span id="metaTestStatus" class="test-line">test bekliyor</span>
                    </div>
                </div>
            </div>

            {{-- GA4 --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">GA4</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_ga4_enabled']['label'] }}</label>
                        <select name="ext_ga4_enabled">
                            <option value="1" @selected((bool)($settings['ext_ga4_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_ga4_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_property_id']['label'] }}</label><input name="ext_ga4_property_id" value="{{ $settings['ext_ga4_property_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_property_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_credentials']['label'] }}</label><input name="ext_ga4_credentials" value="{{ $settings['ext_ga4_credentials']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_credentials']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_access_token']['label'] }}</label><input name="ext_ga4_access_token" value="{{ $settings['ext_ga4_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_access_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_refresh_token']['label'] }}</label><input name="ext_ga4_refresh_token" value="{{ $settings['ext_ga4_refresh_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_refresh_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_oauth_client_id']['label'] }}</label><input name="ext_ga4_oauth_client_id" value="{{ $settings['ext_ga4_oauth_client_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_oauth_client_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_ga4_oauth_client_secret']['label'] }}</label><input name="ext_ga4_oauth_client_secret" value="{{ $settings['ext_ga4_oauth_client_secret']['value'] ?? '' }}" placeholder="{{ $settings['ext_ga4_oauth_client_secret']['placeholder'] ?? '' }}"></div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                        <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('ga4')">GA4 Test</button>
                        <span id="ga4TestStatus" class="test-line">test bekliyor</span>
                    </div>
                </div>
            </div>

            {{-- Google Ads (full width) --}}
            <div class="card" style="grid-column:1 / -1;">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Google Ads</div>
                <div class="grid2" style="gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_google_ads_enabled']['label'] }}</label>
                        <select name="ext_google_ads_enabled">
                            <option value="1" @selected((bool)($settings['ext_google_ads_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_google_ads_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_customer_id']['label'] }}</label><input name="ext_google_ads_customer_id" value="{{ $settings['ext_google_ads_customer_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_customer_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_login_customer_id']['label'] }}</label><input name="ext_google_ads_login_customer_id" value="{{ $settings['ext_google_ads_login_customer_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_login_customer_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_developer_token']['label'] }}</label><input name="ext_google_ads_developer_token" value="{{ $settings['ext_google_ads_developer_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_developer_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field" style="grid-column:1 / -1;"><label>{{ $settings['ext_google_ads_access_token']['label'] }}</label><input name="ext_google_ads_access_token" value="{{ $settings['ext_google_ads_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_access_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_refresh_token']['label'] }}</label><input name="ext_google_ads_refresh_token" value="{{ $settings['ext_google_ads_refresh_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_refresh_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_oauth_client_id']['label'] }}</label><input name="ext_google_ads_oauth_client_id" value="{{ $settings['ext_google_ads_oauth_client_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_oauth_client_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_google_ads_oauth_client_secret']['label'] }}</label><input name="ext_google_ads_oauth_client_secret" value="{{ $settings['ext_google_ads_oauth_client_secret']['value'] ?? '' }}" placeholder="{{ $settings['ext_google_ads_oauth_client_secret']['placeholder'] ?? '' }}"></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                    <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('google_ads')">Google Ads Test</button>
                    <span id="google_adsTestStatus" class="test-line">test bekliyor</span>
                </div>
            </div>

            {{-- TikTok Ads --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">TikTok Ads</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_tiktok_ads_enabled']['label'] }}</label>
                        <select name="ext_tiktok_ads_enabled">
                            <option value="1" @selected((bool)($settings['ext_tiktok_ads_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_tiktok_ads_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_tiktok_ads_advertiser_id']['label'] }}</label><input name="ext_tiktok_ads_advertiser_id" value="{{ $settings['ext_tiktok_ads_advertiser_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_tiktok_ads_advertiser_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_tiktok_ads_access_token']['label'] }}</label><input name="ext_tiktok_ads_access_token" value="{{ $settings['ext_tiktok_ads_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_tiktok_ads_access_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_tiktok_ads_oauth_client_id']['label'] }}</label><input name="ext_tiktok_ads_oauth_client_id" value="{{ $settings['ext_tiktok_ads_oauth_client_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_tiktok_ads_oauth_client_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_tiktok_ads_oauth_client_secret']['label'] }}</label><input name="ext_tiktok_ads_oauth_client_secret" value="{{ $settings['ext_tiktok_ads_oauth_client_secret']['value'] ?? '' }}" placeholder="{{ $settings['ext_tiktok_ads_oauth_client_secret']['placeholder'] ?? '' }}"></div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                        <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('tiktok_ads')">TikTok Test</button>
                        <span id="tiktok_adsTestStatus" class="test-line">test bekliyor</span>
                    </div>
                </div>
            </div>

            {{-- LinkedIn Ads --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">LinkedIn Ads</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_linkedin_ads_enabled']['label'] }}</label>
                        <select name="ext_linkedin_ads_enabled">
                            <option value="1" @selected((bool)($settings['ext_linkedin_ads_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_linkedin_ads_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_linkedin_ads_account_id']['label'] }}</label><input name="ext_linkedin_ads_account_id" value="{{ $settings['ext_linkedin_ads_account_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_linkedin_ads_account_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_linkedin_ads_access_token']['label'] }}</label><input name="ext_linkedin_ads_access_token" value="{{ $settings['ext_linkedin_ads_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_linkedin_ads_access_token']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_linkedin_ads_oauth_client_id']['label'] }}</label><input name="ext_linkedin_ads_oauth_client_id" value="{{ $settings['ext_linkedin_ads_oauth_client_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_linkedin_ads_oauth_client_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field"><label>{{ $settings['ext_linkedin_ads_oauth_client_secret']['label'] }}</label><input name="ext_linkedin_ads_oauth_client_secret" value="{{ $settings['ext_linkedin_ads_oauth_client_secret']['value'] ?? '' }}" placeholder="{{ $settings['ext_linkedin_ads_oauth_client_secret']['placeholder'] ?? '' }}"></div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                        <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('linkedin_ads')">LinkedIn Test</button>
                        <span id="linkedin_adsTestStatus" class="test-line">test bekliyor</span>
                    </div>
                </div>
            </div>

            {{-- Instagram Insights (full width) --}}
            <div class="card" style="grid-column:1 / -1;">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Instagram Insights</div>
                <div class="grid2" style="gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ext_instagram_insights_enabled']['label'] }}</label>
                        <select name="ext_instagram_insights_enabled">
                            <option value="1" @selected((bool)($settings['ext_instagram_insights_enabled']['value'] ?? false))>aktif</option>
                            <option value="0" @selected(!(bool)($settings['ext_instagram_insights_enabled']['value'] ?? false))>pasif</option>
                        </select>
                    </div>
                    <div class="wf-field"><label>{{ $settings['ext_instagram_business_account_id']['label'] }}</label><input name="ext_instagram_business_account_id" value="{{ $settings['ext_instagram_business_account_id']['value'] ?? '' }}" placeholder="{{ $settings['ext_instagram_business_account_id']['placeholder'] ?? '' }}"></div>
                    <div class="wf-field" style="grid-column:1 / -1;"><label>{{ $settings['ext_instagram_access_token']['label'] }}</label><input name="ext_instagram_access_token" value="{{ $settings['ext_instagram_access_token']['value'] ?? '' }}" placeholder="{{ $settings['ext_instagram_access_token']['placeholder'] ?? '' }}"></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                    <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:5px 12px;" onclick="testProvider('instagram_insights')">Instagram Test</button>
                    <span id="instagram_insightsTestStatus" class="test-line">test bekliyor</span>
                </div>
            </div>

            {{-- 3. Parti (full width) --}}
            <div class="card" style="grid-column:1 / -1;">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">3. Parti Operations <span style="font-size:var(--tx-xs);font-weight:400;color:var(--u-muted,#64748b);">— Calendly / Mailchimp / ClickUp</span></div>
                <div class="grid3" style="gap:12px;">

                    <div style="display:flex;flex-direction:column;gap:8px;padding:10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);font-weight:700;">Calendly</div>
                        <div class="wf-field">
                            <label>{{ $settings['ext_calendly_enabled']['label'] }}</label>
                            <select name="ext_calendly_enabled">
                                <option value="1" @selected((bool)($settings['ext_calendly_enabled']['value'] ?? false))>aktif</option>
                                <option value="0" @selected(!(bool)($settings['ext_calendly_enabled']['value'] ?? false))>pasif</option>
                            </select>
                        </div>
                        <div class="wf-field"><label>API Key</label><input name="ext_calendly_api_key" value="{{ $settings['ext_calendly_api_key']['value'] ?? '' }}" placeholder="{{ $settings['ext_calendly_api_key']['placeholder'] ?? '' }}"></div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:4px 10px;" onclick="testProvider('calendly')">Calendly Test</button>
                            <span id="calendlyTestStatus" class="test-line">test bekliyor</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;padding:10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);font-weight:700;">Mailchimp</div>
                        <div class="wf-field">
                            <label>{{ $settings['ext_mailchimp_enabled']['label'] }}</label>
                            <select name="ext_mailchimp_enabled">
                                <option value="1" @selected((bool)($settings['ext_mailchimp_enabled']['value'] ?? false))>aktif</option>
                                <option value="0" @selected(!(bool)($settings['ext_mailchimp_enabled']['value'] ?? false))>pasif</option>
                            </select>
                        </div>
                        <div class="wf-field"><label>API Key</label><input name="ext_mailchimp_api_key" value="{{ $settings['ext_mailchimp_api_key']['value'] ?? '' }}" placeholder="{{ $settings['ext_mailchimp_api_key']['placeholder'] ?? '' }}"></div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:4px 10px;" onclick="testProvider('mailchimp')">Mailchimp Test</button>
                            <span id="mailchimpTestStatus" class="test-line">test bekliyor</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;padding:10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);font-weight:700;">ClickUp</div>
                        <div class="wf-field">
                            <label>{{ $settings['ext_clickup_enabled']['label'] }}</label>
                            <select name="ext_clickup_enabled">
                                <option value="1" @selected((bool)($settings['ext_clickup_enabled']['value'] ?? false))>aktif</option>
                                <option value="0" @selected(!(bool)($settings['ext_clickup_enabled']['value'] ?? false))>pasif</option>
                            </select>
                        </div>
                        <div class="wf-field"><label>API Key</label><input name="ext_clickup_api_key" value="{{ $settings['ext_clickup_api_key']['value'] ?? '' }}" placeholder="{{ $settings['ext_clickup_api_key']['placeholder'] ?? '' }}"></div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <button class="btn alt" type="button" style="font-size:var(--tx-xs);padding:4px 10px;" onclick="testProvider('clickup')">ClickUp Test</button>
                            <span id="clickupTestStatus" class="test-line">test bekliyor</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- AI Prompt Templates (full width) --}}
            <div class="card" style="grid-column:1 / -1;">
                <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:6px;">AI Prompt Template Yönetimi <span style="font-size:var(--tx-xs);font-weight:400;color:var(--u-muted,#64748b);">— Doküman Yazımı</span></div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:12px;">
                    Student document-builder içinde <code>AI Destek Modu</code> seçildiğinde kullanılan system prompt override alanları. Boş bırakılırsa uygulama default prompt kullanır.
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="wf-field">
                        <label>{{ $settings['ai_writer_prompt_motivation']['label'] }}</label>
                        <textarea name="ai_writer_prompt_motivation">{{ $settings['ai_writer_prompt_motivation']['value'] ?? '' }}</textarea>
                    </div>
                    <div class="wf-field">
                        <label>{{ $settings['ai_writer_prompt_reference']['label'] }}</label>
                        <textarea name="ai_writer_prompt_reference">{{ $settings['ai_writer_prompt_reference']['value'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

        </div>{{-- /grid2 --}}

        {{-- Save --}}
        <div class="card" style="margin-top:12px;">
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn ok">Ayarları Kaydet</button>
                <a href="/mktg-admin/integrations" class="btn alt">Yenile</a>
            </div>
        </div>

    </form>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Entegrasyonlar</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Her provider için aktif/pasif ve gerekli credential alanlarını gir.</li>
            <li>Ayar kaydından sonra ilgili "Test" butonuyla dry-run bağlantı kontrolü yap.</li>
            <li>Scheduler <code>marketing:sync-external-metrics</code> ile metrikleri, <code>marketing:integrations-health</code> ile health durumunu otomatik günceller.</li>
        </ol>
    </details>

</div>

<script defer src="{{ Vite::asset('resources/js/marketing-admin-integrations.js') }}"></script>
@endsection
