@extends('platform.layouts.app')

@section('title', $company->name . ' — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <a href="{{ route('platform.companies') }}" style="font-size:12px;color:var(--plat-muted);"><x-icon name="arrow-left" size="12" /> Şirketlere dön</a>
        <h1 class="plat-page-title" style="margin-top:8px;">{{ $company->name }}</h1>
        <p class="plat-page-sub">
            #{{ $company->id }} · {{ $company->code }}
            @if($company->billing_email) · <x-icon name="mail" size="10" /> {{ $company->billing_email }} @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        @if($company->is_active)
            <span class="plat-badge plat-badge-active"><x-icon name="check" size="10" /> Aktif</span>
        @else
            <span class="plat-badge plat-badge-inactive"><x-icon name="x" size="10" /> Pasif</span>
        @endif
        <span class="plat-badge plat-badge-{{ $company->subscription_tier ?? 'trial' }}">{{ $tierLabels[$company->subscription_tier] ?? $company->subscription_tier }}</span>
    </div>
</div>

{{-- KPI ÖZET --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="dollar-sign" size="12" /> MRR</div>
        <div class="plat-kpi-value">€{{ number_format((float) $company->mrr_eur, 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">
            @if($company->subscription_renews_at)
                Yenileme: {{ $company->subscription_renews_at->format('d.m.Y') }}
            @else
                Yenileme tarihi yok
            @endif
        </div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Öğrenci</div>
        <div class="plat-kpi-value">{{ number_format($studentTotal) }}</div>
        <div class="plat-kpi-sub">
            @if($studentLimit)
                Limit: {{ number_format($studentLimit) }} ({{ $studentUsagePct }}%)
            @else
                Sınırsız
            @endif
        </div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="gauge" size="12" /> 30 Gün Giriş</div>
        <div class="plat-kpi-value">{{ number_format($recentLogins) }}</div>
        <div class="plat-kpi-sub">kullanıcı login</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="trending-up" size="12" /> 30 Gün Başvuru</div>
        <div class="plat-kpi-value">{{ number_format($recentApplications) }}</div>
        <div class="plat-kpi-sub">yeni aday</div>
    </div>
</div>

{{-- TIER YONETIM + KULLANICILAR --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="crown" size="16" /> Subscription Tier</h3>
        <form method="POST" action="{{ route('platform.companies.tier', $company->id) }}">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label">Tier Seç</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach(config('subscription_tiers') as $tierKey => $tierCfg)
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;cursor:pointer;{{ $company->subscription_tier === $tierKey ? 'border-color:var(--plat-accent);background:var(--plat-accent-bg);' : '' }}">
                            <input type="radio" name="subscription_tier" value="{{ $tierKey }}" {{ $company->subscription_tier === $tierKey ? 'checked' : '' }} style="accent-color:var(--plat-accent);">
                            <div>
                                <div style="font-weight:700;color:#fff;font-size:13px;">{{ $tierCfg['label'] }}</div>
                                <div style="font-size:11px;color:var(--plat-muted);">€{{ $tierCfg['mrr_eur'] }}/ay</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Trial Bitiş</label>
                <input type="date" name="trial_ends_at" class="plat-input" value="{{ optional($company->trial_ends_at)->format('Y-m-d') }}">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Yenileme Tarihi</label>
                <input type="date" name="subscription_renews_at" class="plat-input" value="{{ optional($company->subscription_renews_at)->format('Y-m-d') }}">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">MRR (€) — boş bırakırsan tier varsayılanı</label>
                <input type="number" step="0.01" name="mrr_eur" class="plat-input" value="{{ $company->mrr_eur }}">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Faturalama E-postası</label>
                <input type="email" name="billing_email" class="plat-input" value="{{ $company->billing_email }}">
            </div>

            <div class="plat-form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="auto_sync_modules" name="auto_sync_modules" value="1" checked style="accent-color:var(--plat-accent);">
                <label for="auto_sync_modules" style="font-size:13px;color:var(--plat-text);cursor:pointer;">Modülleri tier'dan otomatik senkronize et</label>
            </div>

            <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="refresh-cw" size="14" /> Tier Güncelle</button>
        </form>
    </div>

    {{-- WHITE-LABEL MARKA --}}
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="palette" size="16" /> White-label Marka</h3>
        <p class="plat-card-sub" style="margin-bottom:14px;">
            Boş bırakılan alan platformun varsayılanına döner.
            Marka <strong>domainden</strong>, veri erişimi <strong>kullanıcıdan</strong> belirlenir.
        </p>

        <form method="POST" action="{{ route('platform.companies.branding', $company->id) }}">
            @csrf

            <div class="plat-form-group">
                <label class="plat-form-label">Marka Adı</label>
                <input type="text" name="brand_name" class="plat-input"
                       value="{{ old('brand_name', $company->brand_name) }}"
                       maxlength="120" placeholder="{{ $company->name }}">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Panelde, e-postalarda ve öğrenciye giden mesajlarda görünür.
                    Boş bırakılırsa <strong>şirket adı</strong> kullanılır.
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Logo URL</label>
                <input type="text" name="brand_logo_url" class="plat-input"
                       value="{{ old('brand_logo_url', $company->brand_logo_url) }}"
                       maxlength="500" placeholder="https://.../logo.svg">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Ana Renk</label>
                <input type="text" name="brand_primary_color" class="plat-input"
                       value="{{ old('brand_primary_color', $company->brand_primary_color) }}"
                       maxlength="7" placeholder="#0d9488" pattern="^#[0-9a-fA-F]{6}$">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Kendi Domaini</label>
                <input type="text" name="primary_domain" class="plat-input"
                       value="{{ old('primary_domain', $company->primary_domain) }}"
                       maxlength="190" placeholder="a.yourgermanuni.com">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Bu adresten gelen ziyaretçi (giriş yapmamış olsa bile) bu markayı görür.
                    <strong>Önce DNS + SSL kurulmuş olmalı.</strong>
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Public Pazarlama İçeriği</label>
                <input type="hidden" name="public_marketing" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;cursor:pointer;">
                    <input type="checkbox" name="public_marketing" value="1"
                           {{ old('public_marketing', $company->public_marketing) ? 'checked' : '' }}
                           style="accent-color:var(--plat-accent);margin-top:2px;">
                    <span style="font-size:11px;color:var(--plat-muted);line-height:1.6;">
                        Giriş sayfasında <strong>"Ücretsiz Başvuru"</strong> ve tanıtım listesi gösterilsin.
                        B2B partner firmalarda <strong>kapalı</strong> olmalı.
                    </span>
                </label>
            </div>

            <button type="submit" class="plat-btn plat-btn-primary">
                <x-icon name="check" size="14" /> Markayı Kaydet
            </button>
        </form>
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="users" size="16" /> Kullanıcılar (Role Bazlı)</h3>
        @php
            $roleLabels = [
                'manager'         => 'Yönetici',
                'senior'          => 'Eğitim Danışmanı',
                'mentor'          => 'Mentor',
                'student'         => 'Öğrenci',
                'guest'           => 'Aday',
                'dealer'          => 'Satış Ortağı',
                'marketing_admin' => 'Marketing Admin',
                'system_admin'    => 'System Admin',
            ];
            $totalUsers = array_sum($userCounts);
        @endphp
        <p class="plat-card-sub" style="margin-bottom:12px;">Toplam {{ number_format($totalUsers) }} kullanıcı</p>
        <table class="plat-table" style="font-size:12px;">
            <thead>
                <tr><th>Rol</th><th style="text-align:right;">Sayı</th><th style="text-align:right;">Oran</th></tr>
            </thead>
            <tbody>
                @forelse($userCounts as $role => $count)
                    <tr>
                        <td><strong style="color:#fff;">{{ $roleLabels[$role] ?? $role }}</strong></td>
                        <td style="text-align:right;font-weight:700;color:#fff;">{{ number_format($count) }}</td>
                        <td style="text-align:right;">
                            <span class="plat-badge plat-badge-premium">{{ $totalUsers > 0 ? round($count / $totalUsers * 100) : 0 }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color:var(--plat-muted);text-align:center;">Henüz kullanıcı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODÜL TOGGLE MATRIX --}}
<div class="plat-card" style="margin-bottom:24px;">
    <h3 class="plat-card-title">
        <x-icon name="layers" size="16" /> Modül Kontrolü
        <span style="margin-left:auto;font-size:11px;font-weight:500;color:var(--plat-muted);">{{ count($enabledModules) }} aktif / {{ count($moduleMeta) }} toplam</span>
    </h3>
    <p class="plat-card-sub" style="margin-bottom:14px;">Tier'dan farklı manuel modül ayarı. "Tier'a Sıfırla" için yukarıdan tier formunu kullan.</p>

    <form method="POST" action="{{ route('platform.companies.modules', $company->id) }}">
        @csrf

        @foreach($moduleGroups as $groupKey => $groupLabel)
            <div style="margin-bottom:20px;">
                <div style="font-size:11px;font-weight:700;color:var(--plat-accent-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">{{ $groupLabel }}</div>
                @foreach($moduleMeta as $moduleKey => $meta)
                    @if(($meta['group'] ?? '') !== $groupKey) @continue @endif
                    @php $isLocked = $meta['locked'] ?? false; @endphp
                    @php $isEnabled = in_array($moduleKey, $enabledModules, true) || $isLocked; @endphp
                    <div class="plat-module-toggle {{ $isLocked ? 'locked' : '' }}">
                        <div class="plat-module-name">
                            <span class="plat-module-name-title">{{ $meta['label'] }} <code style="font-size:10px;color:var(--plat-muted);font-weight:500;">{{ $moduleKey }}</code></span>
                            <span class="plat-module-name-desc">{{ $meta['desc'] ?? '' }}</span>
                        </div>
                        <label class="plat-switch">
                            <input type="checkbox" name="modules[]" value="{{ $moduleKey }}" {{ $isEnabled ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                            <span class="plat-switch-slider"></span>
                        </label>
                        @if($isLocked)
                            <input type="hidden" name="modules[]" value="{{ $moduleKey }}">
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- doc_request quota override (tier'in limitini ezer) --}}
        <div class="plat-form-group" style="background:var(--plat-panel-2);padding:14px;border-radius:8px;border:1px solid var(--plat-border);">
            <label class="plat-form-label">Doc Request Aylık Limit (NULL = tier varsayılanı)</label>
            <input type="number" min="0" max="10000" name="doc_request_monthly_limit" class="plat-input" value="{{ $company->doc_request_monthly_limit }}" placeholder="Tier varsayılanı: {{ $tierConfig['limits']['doc_request_monthly'] ?? 'sınırsız' }}">
        </div>

        <button type="submit" class="plat-btn plat-btn-primary" style="margin-top:14px;"><x-icon name="check" size="14" /> Modülleri Kaydet</button>
    </form>
</div>

@endsection
