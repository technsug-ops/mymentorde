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

        {{-- Askıya alma: test kayıtları ve sözleşmesi biten firmalar için.
             Ana şirket kapatılamaz — varsayılan şirket çözümlemesi ona bağlı. --}}
        @unless(\App\Support\Brand::isPrimary($company))
            <form method="POST" action="{{ route('platform.companies.status', $company->id) }}" style="display:inline;">
                @csrf
                <input type="hidden" name="is_active" value="{{ $company->is_active ? 0 : 1 }}">
                <button type="submit" class="plat-btn plat-btn-ghost" style="font-size:12px;padding:4px 12px;">
                    {{ $company->is_active ? 'Askıya Al' : 'Yeniden Aç' }}
                </button>
            </form>
        @endunless
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

            @php
                // brand_overrides config/brand.php'nin şeklini taklit eder.
                $_bo = $company->brand_overrides;
                if (is_string($_bo)) { $_bo = json_decode($_bo, true); }
                $_mailFrom = is_array($_bo) ? ($_bo['mail_from_address'] ?? '') : '';
            @endphp

            <div class="plat-form-group">
                <label class="plat-form-label">Gönderen E-posta Adresi</label>
                <input type="email" name="mail_from_address" class="plat-input"
                       value="{{ old('mail_from_address', $_mailFrom) }}"
                       maxlength="190" placeholder="{{ config('mail.from.address') }}">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Bu şirketin mailleri bu adresten çıkar.
                    <strong>Alan adı mail sağlayıcısında doğrulanmış olmalı</strong> —
                    doğrulanmamış bir adres bu şirketin tüm mailini keser.
                    Boş bırakılırsa platformun adresi kullanılır.
                </small>
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
                <label class="plat-form-label">Üst Firma</label>
                <select name="parent_company_id" class="plat-select" style="width:100%;">
                    <option value="">— Yok (bağımsız) —</option>
                    @foreach($allCompanies ?? [] as $opt)
                        @continue((int) $opt->id === (int) $company->id)
                        <option value="{{ $opt->id }}"
                            {{ (int) old('parent_company_id', $company->parent_company_id) === (int) $opt->id ? 'selected' : '' }}>
                            {{ $opt->brand_name ?: $opt->name }}
                        </option>
                    @endforeach
                </select>
                <small style="font-size:11px;color:var(--plat-muted);display:block;margin-top:6px;">
                    Üst firmanın <strong>personeli</strong> bu şirketin adaylarını ve öğrencilerini görür,
                    süreçlerini yürütebilir. Bu şirketin kullanıcıları üst firmayı <strong>göremez</strong> —
                    izolasyon yataydır.
                    <br>Öğrenci, aday ve bayi rolleri bu erişime <strong>hiçbir zaman</strong> dahil değildir.
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Başvuru Linki Adresi</label>
                <input type="text" name="slug" class="plat-input"
                       value="{{ old('slug', $company->slug) }}"
                       maxlength="58" placeholder="{{ \Illuminate\Support\Str::slug($company->name, '-') }}">
                @php
                    $_applyLink = \App\Support\ApplyCompanyResolver::linkFor($company);
                    $_acceptsApply = \App\Support\ApplyCompanyResolver::acceptsApplications($company);
                    $_portalHost = \App\Support\ApplyCompanyResolver::publicPortalHost();
                @endphp
                <small style="font-size:11px;color:var(--plat-muted);display:block;margin-top:6px;">
                    Firmanın öğrencisine vereceği adres:
                    <code style="color:#fff;background:var(--plat-panel-2);padding:2px 6px;border-radius:4px;">{{ $_applyLink }}</code>
                    <br>Bu linkten gelen başvuru <strong>bu firmaya</strong> yazılır ve yalnızca bu firma görür.
                    @if(empty($company->primary_domain) && !empty($_portalHost))
                        <br>Adres ortak giriş kapısı <strong>{{ $_portalHost }}</strong> üzerinden veriliyor.
                        Firmaya kendi domainini tanımlarsan link o adrese döner.
                    @elseif(empty($company->primary_domain) && empty($_portalHost))
                        <br><strong style="color:#f59e0b;">⚠ Ortak giriş kapısı tanımlı değil</strong> —
                        adres panelin domaini ile üretiliyor. Nötr portal şirketini
                        "Ortak Giriş Kapısı" olarak işaretle.
                    @endif
                    @unless($_acceptsApply)
                        <br><strong style="color:#f59e0b;">⚠ Bu şirketin aktif personeli yok — link şu an 404 veriyor.</strong>
                        Lead'e bakacak bir yönetici eklenmeden başvuru kabul edilmez.
                    @endunless
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Panel Modu</label>
                <select name="panel_mode" class="plat-select" style="width:100%;">
                    <option value="full" {{ ($company->panel_mode ?? 'full') !== 'partner' ? 'selected' : '' }}>
                        Tam panel — kendi operasyonunu yürüten firma
                    </option>
                    <option value="partner" {{ ($company->panel_mode ?? 'full') === 'partner' ? 'selected' : '' }}>
                        Sade partner penceresi — öğrenci takibi
                    </option>
                </select>
                <small style="font-size:11px;color:var(--plat-muted);display:block;margin-top:6px;">
                    <strong style="color:#fff;">Sade</strong> modda menü 8 maddeye iner: adaylar, öğrenciler,
                    aday ekle, belgeler, destek, duyurular, hesap.
                    <br>İnsan kaynakları, finans, sistem yönetimi, AI Labs, UniMatch ve bayi ağı gibi
                    alanların <strong style="color:#fff;">adresleri de kapanır</strong> — menüyü gizlemek
                    tek başına yeterli olmazdı.
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Ortak Giriş Kapısı</label>
                <input type="hidden" name="is_public_portal" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;cursor:pointer;">
                    <input type="checkbox" name="is_public_portal" value="1"
                           {{ old('is_public_portal', $company->is_public_portal) ? 'checked' : '' }}
                           style="accent-color:var(--plat-accent);margin-top:2px;">
                    <span style="font-size:11px;color:var(--plat-muted);line-height:1.6;">
                        Bu şirketin domaini, <strong>kendi domaini olmayan</strong> partner firmaların
                        başvuru linklerinde kullanılır. Nötr portal (YourGermanUni) için işaretli olmalı;
                        aksi halde partnere <strong>MentorDE domainli</strong> link verilir.
                    </span>
                </label>
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

    {{-- ── YETKİ TAVANI ─────────────────────────────────────────────── --}}
    @unless(\App\Support\Brand::isPrimary($company))
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="shield" size="16" /> Yetki Kısıtları</h3>
        <p class="plat-card-sub" style="margin-bottom:14px;">
            Rol yetkiyi <strong>verir</strong>, buradaki işaretler <strong>daraltır</strong>.
            Hiçbiri işaretli değilse firma rolünün verdiği her şeyi yapabilir.
            <br>Koyduğun kısıt bu firmanın <strong>altındaki firmaları da</strong> bağlar.
        </p>

        @php
            $_own = collect($company->denied_permission_codes ?? []);
            $_effective = collect(\App\Models\Company::effectiveDeniedPermissions((int) $company->id));
            $_inherited = $_effective->diff($_own);
        @endphp

        @if($_inherited->isNotEmpty())
            <div style="padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;margin-bottom:14px;font-size:12px;color:var(--plat-muted);line-height:1.6;">
                <strong style="color:#f59e0b;">Üst firmadan miras:</strong>
                {{ $_inherited->map(fn ($c) => \App\Support\PermissionCeiling::RESTRICTABLE[$c]['label'] ?? $c)->implode(', ') }}
                <br>Bunlar üstteki firmada tanımlı — buradan kaldırılamaz.
            </div>
        @endif

        <form method="POST" action="{{ route('platform.companies.permissions', $company->id) }}">
            @csrf
            <input type="hidden" name="denied_permission_codes[]" value="">

            @foreach(\App\Support\PermissionCeiling::grouped() as $groupName => $items)
                <div style="font-size:11px;color:var(--plat-accent-2);text-transform:uppercase;letter-spacing:.5px;margin:16px 0 8px;">{{ $groupName }}</div>

                @foreach($items as $code => $meta)
                    @php $_isInherited = $_inherited->contains($code); @endphp
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:9px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;margin-bottom:6px;cursor:{{ $_isInherited ? 'not-allowed' : 'pointer' }};opacity:{{ $_isInherited ? '.6' : '1' }};">
                        <input type="checkbox" name="denied_permission_codes[]" value="{{ $code }}"
                               {{ $_own->contains($code) || $_isInherited ? 'checked' : '' }}
                               {{ $_isInherited ? 'disabled' : '' }}
                               style="accent-color:var(--plat-accent);margin-top:3px;">
                        <span style="font-size:12px;line-height:1.5;">
                            <strong style="color:#fff;">{{ $meta['label'] }}</strong>
                            <span style="display:block;color:var(--plat-muted);font-size:11px;">{{ $meta['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            @endforeach

            <button type="submit" class="plat-btn plat-btn-primary" style="margin-top:14px;">
                <x-icon name="check" size="14" /> Kısıtları Kaydet
            </button>
        </form>
    </div>
    @endunless

    {{-- ── PANEL KULLANICILARI ──────────────────────────────────────────
         Yalnızca firmanın PANEL hesapları. Öğrenci ve aday hesapları burada
         YOK: onlar müşterinin müşterisi, kişisel verileri bu konsolda
         gösterilmez. Panel kullanıcısı ise bizimle sözleşmeli hesap sahibi. --}}
    {{-- ── FİRMANIN KENDİ MAİL TAŞIYICISI ──────────────────────────────────
         White-label'da gönderim kimliği firmaya ait olmalı. Tanımlanmazsa
         bağlı olunan portalın, o da yoksa platformun taşıyıcısı kullanılır. --}}
    <div class="plat-card" style="margin-bottom:16px;">
        <h3 class="plat-card-title"><x-icon name="mail" size="16" /> Mail Taşıyıcısı</h3>

        @php
            $_ms      = $mailSetting ?? null;
            $_driver  = old('driver', $_ms->driver ?? \App\Models\CompanyMailSetting::DRIVER_SMTP);
            $_hasCred = $_ms && (($_ms->driver === 'resend' && $_ms->api_key) || $_ms->password);
        @endphp

        <p class="plat-card-sub" style="margin-bottom:14px;">
            Firma kendi mail sunucusunu ya da kendi Resend hesabını kullanmak isterse buraya girilir.
            Boş bırakılırsa <strong style="color:#fff;">bağlı olduğu portalın</strong>, o da yoksa
            platformun taşıyıcısı kullanılır.
        </p>

        @if($_ms)
            <div style="padding:10px 12px;border-radius:8px;margin-bottom:14px;
                        background:{{ $_ms->is_active ? 'rgba(22,163,74,.12)' : 'rgba(217,119,6,.12)' }};
                        border:1px solid {{ $_ms->is_active ? 'rgba(22,163,74,.4)' : 'rgba(217,119,6,.4)' }};
                        font-size:12.5px;">
                @if($_ms->is_active)
                    <strong style="color:#4ade80;">DEVREDE</strong> —
                    {{ $_ms->driver === 'resend' ? 'Resend (firma hesabı)' : $_ms->host . ':' . $_ms->port }}
                    @if($_ms->last_tested_at) · son test {{ $_ms->last_tested_at->format('d.m.Y H:i') }} @endif
                @else
                    <strong style="color:#fbbf24;">KAPALI</strong> — test edilmeden kullanılmaz.
                    @if($_ms->last_test_error)
                        <div style="margin-top:6px;color:#fca5a5;font-size:11.5px;word-break:break-word;">
                            Son hata: {{ \Illuminate\Support\Str::limit($_ms->last_test_error, 300) }}
                        </div>
                    @endif
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('platform.companies.mail-setting.update', $company->id) }}">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label">Sürücü</label>
                <select name="driver" class="plat-select" style="width:100%;">
                    @foreach(\App\Models\CompanyMailSetting::DRIVERS as $val => $label)
                        <option value="{{ $val }}" {{ $_driver === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <div class="plat-form-group" style="flex:2;min-width:180px;">
                    <label class="plat-form-label">SMTP Sunucu</label>
                    <input type="text" name="host" class="plat-input" maxlength="190"
                           value="{{ old('host', $_ms->host ?? '') }}" placeholder="mail.firma.com">
                </div>
                <div class="plat-form-group" style="width:110px;">
                    <label class="plat-form-label">Port</label>
                    <input type="number" name="port" class="plat-input"
                           value="{{ old('port', $_ms->port ?? 587) }}" placeholder="587">
                </div>
                <div class="plat-form-group" style="width:130px;">
                    <label class="plat-form-label">Şifreleme</label>
                    <select name="encryption" class="plat-select">
                        <option value="">STARTTLS</option>
                        <option value="ssl" {{ old('encryption', $_ms->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <div class="plat-form-group" style="flex:1;min-width:180px;">
                    <label class="plat-form-label">Kullanıcı Adı</label>
                    <input type="text" name="username" class="plat-input" maxlength="190"
                           value="{{ old('username', $_ms->username ?? '') }}">
                </div>
                <div class="plat-form-group" style="flex:1;min-width:180px;">
                    <label class="plat-form-label">Şifre</label>
                    <input type="password" name="password" class="plat-input" autocomplete="new-password"
                           placeholder="{{ $_hasCred ? '•••••••• (kayıtlı)' : '' }}">
                </div>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Resend API Anahtarı</label>
                <input type="password" name="api_key" class="plat-input" autocomplete="new-password"
                       placeholder="{{ $_hasCred ? '•••••••• (kayıtlı)' : 're_...' }}">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Yalnızca sürücü <strong>Resend</strong> ise kullanılır.
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Gönderen Adresi (taşıyıcıya ait)</label>
                <input type="email" name="from_address" class="plat-input" maxlength="190"
                       value="{{ old('from_address', $_ms->from_address ?? '') }}">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Kimlik bilgisi hangi alan adına aitse gönderim de ondan çıkmalı,
                    yoksa sağlayıcı reddeder. Boşsa markadaki adres kullanılır.
                </small>
            </div>

            <small style="display:block;font-size:11px;color:var(--plat-muted);margin-bottom:10px;">
                Şifre ve anahtar <strong>şifreli</strong> saklanır ve bir daha gösterilmez.
                Boş bırakırsan mevcut değer korunur.
            </small>

            <button type="submit" class="plat-btn plat-btn-ghost">Taşıyıcıyı Kaydet</button>
        </form>

        @if($_ms)
            <form method="POST" action="{{ route('platform.companies.mail-setting.test', $company->id) }}"
                  style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid var(--plat-border);">
                @csrf
                <div style="flex:1;min-width:200px;">
                    <label class="plat-form-label">Test Alıcısı</label>
                    <input type="email" name="to" class="plat-input" required placeholder="kendi@adresin.com">
                </div>
                <button type="submit" class="plat-btn plat-btn-ghost">Test Et ve Devreye Al</button>
            </form>
            <small style="display:block;font-size:11px;color:var(--plat-muted);margin-top:6px;">
                Taşıyıcı ancak <strong>başarılı testten sonra</strong> kullanılır — yanlış kimlik bilgisi
                bu firmanın tüm mailini sessizce keser.
            </small>

            <form method="POST" action="{{ route('platform.companies.mail-setting.destroy', $company->id) }}"
                  style="margin-top:12px;" onsubmit="return confirm('Taşıyıcı kaldırılsın mı? Firma platformun taşıyıcısına döner.');">
                @csrf @method('DELETE')
                <button type="submit" class="plat-btn plat-btn-ghost" style="color:#fca5a5;border-color:rgba(220,38,38,.4);">
                    Taşıyıcıyı Kaldır
                </button>
            </form>
        @endif
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="key" size="16" /> Panel Hesapları</h3>
        <p class="plat-card-sub" style="margin-bottom:14px;">
            Firma şifresini kaybederse buradan sıfırlayabilirsiniz. Yeni şifre
            <strong style="color:#fff;">tek sefer</strong> gösterilir ve kullanıcı ilk girişte
            değiştirmek zorundadır.
        </p>

        {{-- Yeni hesap açma.

             Konsol mevcut hesapları listeliyor ve şifrelerini sıfırlıyordu ama
             YENİ hesap açamıyordu. Firma kullanıcısız kalırsa (ilk yönetici
             silinmişse ya da firma boş kurulmuşsa) şirkete girmenin başka yolu
             kalmıyordu — başvuru linki de personelsiz firmada 404 veriyor. --}}
        <form method="POST" action="{{ route('platform.companies.staff.store', $company->id) }}"
              style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--plat-border);">
            @csrf
            <div style="flex:1;min-width:150px;">
                <label class="plat-form-label">Ad Soyad</label>
                <input type="text" name="name" class="plat-input" required maxlength="120" value="{{ old('name') }}">
            </div>
            <div style="flex:1.4;min-width:200px;">
                <label class="plat-form-label">E-posta</label>
                <input type="email" name="email" class="plat-input" required maxlength="190" value="{{ old('email') }}">
            </div>
            <button type="submit" class="plat-btn plat-btn-ghost">Yönetici Ekle</button>
            <p class="plat-card-sub" style="width:100%;margin:2px 0 0;font-size:11.5px;">
                Rol <strong style="color:#fff;">yönetici</strong> olarak açılır; diğer roller firmanın
                kendi personel ekranından eklenir. Danışman rolü buradan bilerek açılamaz —
                bir firmaya danışman eklemek operasyonu o firmaya taşır.
            </p>
        </form>

        @if(empty($staffAccounts) || $staffAccounts->isEmpty())
            <p style="margin:0;color:var(--plat-muted);font-size:13px;">
                Bu şirkette panel kullanıcısı yok — başvuru linki de 404 verir.
            </p>
        @else
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($staffAccounts as $acc)
                    <div style="padding:12px 13px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                            <div style="flex:1;min-width:180px;">
                                <span style="font-weight:600;font-size:13px;">{{ $acc->name }}</span>
                                <span style="display:block;font-size:11.5px;color:var(--plat-muted);">
                                    {{ $acc->role }}
                                    @unless($acc->is_active) · <span style="color:#f59e0b;">pasif</span> @endunless
                                    @if($acc->password_must_change) · <span style="color:var(--plat-accent-2);">şifre değiştirmeli</span> @endif
                                </span>
                            </div>

                            <form method="POST" action="{{ route('platform.companies.staff.reset-password', [$company->id, $acc->id]) }}">
                                @csrf
                                <button type="submit" class="plat-btn plat-btn-ghost" style="font-size:12px;padding:5px 12px;">
                                    Şifre Sıfırla
                                </button>
                            </form>
                        </div>

                        {{-- Giriş e-postası aynı zamanda kimlik; ayrı formda. --}}
                        <form method="POST" action="{{ route('platform.companies.staff.email', [$company->id, $acc->id]) }}"
                              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            @csrf
                            <input type="email" name="email" value="{{ $acc->email }}" required maxlength="190"
                                   class="plat-input" style="flex:1;min-width:220px;font-size:12.5px;height:34px;">
                            <button type="submit" class="plat-btn plat-btn-ghost" style="font-size:12px;padding:5px 12px;">
                                E-postayı Kaydet
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <p style="font-size:11px;color:var(--plat-muted);margin-top:12px;line-height:1.6;">
                Şifre sıfırlama <strong>sessiz değildir</strong> — eski şifre çalışmaz olur ve firma
                bunu fark eder. Müşteri verisine gizlice erişim (impersonation) bilinçli olarak kapalıdır.
            </p>
        @endif
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
