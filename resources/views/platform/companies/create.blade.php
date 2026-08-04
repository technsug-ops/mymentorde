@extends('platform.layouts.app')

@section('title', 'Yeni Şirket — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <a href="{{ route('platform.companies') }}" style="font-size:12px;color:var(--plat-muted);"><x-icon name="arrow-left" size="12" /> Şirketlere dön</a>
        <h1 class="plat-page-title" style="margin-top:8px;">Yeni Şirket</h1>
        <p class="plat-page-sub">Self-service provisioning — şirket + ilk manager hesabı tek seferde oluşur</p>
    </div>
</div>

<form method="POST" action="{{ route('platform.companies.store') }}">
    @csrf

    <div class="plat-grid plat-grid-2">
        {{-- ŞIRKET BILGISI --}}
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="building-2" size="16" /> Şirket Bilgisi</h3>

            <div class="plat-form-group">
                <label class="plat-form-label">Şirket Adı *</label>
                <input type="text" name="name" class="plat-input" value="{{ old('name') }}" required maxlength="190" placeholder="Örn: ABC Eğitim Danışmanlığı">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Şirket Kodu (slug)</label>
                <input type="text" name="code" class="plat-input" value="{{ old('code') }}" maxlength="40" placeholder="Boş bırakılırsa otomatik üretilir">
                <small style="font-size:11px;color:var(--plat-muted);">URL-safe, unique. Sadece harf/rakam/underscore.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Faturalama E-postası</label>
                <input type="email" name="billing_email" class="plat-input" value="{{ old('billing_email') }}" maxlength="190" placeholder="faturalama@example.com">
            </div>

            <h4 style="font-size:12px;color:var(--plat-accent-2);text-transform:uppercase;letter-spacing:.5px;margin:22px 0 12px;">Subscription</h4>

            <div class="plat-form-group">
                <label class="plat-form-label">Tier *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach($tiers as $tierKey => $tierCfg)
                        @php $isDefault = old('subscription_tier', 'trial') === $tierKey; @endphp
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;cursor:pointer;{{ $isDefault ? 'border-color:var(--plat-accent);background:var(--plat-accent-bg);' : '' }}">
                            <input type="radio" name="subscription_tier" value="{{ $tierKey }}" {{ $isDefault ? 'checked' : '' }} required style="accent-color:var(--plat-accent);">
                            <div>
                                <div style="font-weight:700;color:#fff;font-size:13px;">{{ $tierCfg['label'] }}</div>
                                <div style="font-size:11px;color:var(--plat-muted);">€{{ $tierCfg['mrr_eur'] }}/ay</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Trial Bitiş Tarihi</label>
                <input type="date" name="trial_ends_at" class="plat-input" value="{{ old('trial_ends_at') }}">
                <small style="font-size:11px;color:var(--plat-muted);">Trial seçildiyse boş bırakılırsa 14 gün sonra otomatik atanır.</small>
            </div>
        </div>

        {{-- MANAGER HESABI --}}
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="user-tie" size="16" /> İlk Manager Hesabı</h3>
            <p class="plat-card-sub" style="margin-bottom:14px;">Şirket için login bilgilerini oluştur. Manager geç tutarsa direkt /manager/dashboard'a girer.</p>

            <div class="plat-form-group">
                <label class="plat-form-label">Manager Adı *</label>
                <input type="text" name="admin_name" class="plat-input" value="{{ old('admin_name') }}" required maxlength="120" placeholder="Adı Soyadı">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Manager E-postası *</label>
                <input type="email" name="admin_email" class="plat-input" value="{{ old('admin_email') }}" required maxlength="190" placeholder="manager@example.com">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Geçici Şifre *</label>
                <input type="text" name="admin_password" class="plat-input" value="{{ old('admin_password') }}" required minlength="8" maxlength="120" placeholder="En az 8 karakter">
                <small style="font-size:11px;color:var(--plat-muted);">Manager ilk girişte değiştirebilir.</small>
            </div>

            <div style="margin-top:20px;padding:12px;background:var(--plat-panel-2);border-radius:8px;border:1px solid var(--plat-border);">
                <div style="font-size:11px;font-weight:700;color:var(--plat-accent-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                    <x-icon name="info" size="11" /> Provisioning Notu
                </div>
                <div style="font-size:12px;color:var(--plat-muted);line-height:1.6;">
                    Şirket otomatik olarak <strong style="color:#fff;">aktif</strong> başlar.<br>
                    Modüller seçilen tier'dan otomatik dağıtılır.<br>
                    Manager email_verified_at = now (mail doğrulamasız).
                </div>
            </div>
        </div>

        {{-- WHITE-LABEL MARKA (opsiyonel) --}}
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="palette" size="16" /> White-label Marka</h3>
            <p class="plat-card-sub" style="margin-bottom:14px;">
                Marka adı boş bırakılırsa <strong style="color:#fff;">şirket adı</strong> kullanılır.
                Platformun markası (MentorDE logosu, adı, banka bilgisi) partner firmaya
                <strong style="color:#fff;">asla miras kalmaz</strong>.
            </p>

            <div class="plat-form-group">
                <label class="plat-form-label">Marka Adı</label>
                <input type="text" name="brand_name" class="plat-input" value="{{ old('brand_name') }}"
                       maxlength="120" placeholder="Örn. A Eğitim Danışmanlık">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Panelde, e-postalarda ve öğrenciye giden mesajlarda bu ad geçer.
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Logo URL</label>
                <input type="text" name="brand_logo_url" class="plat-input" value="{{ old('brand_logo_url') }}"
                       maxlength="500" placeholder="https://.../logo.svg">
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Ana Renk</label>
                <input type="text" name="brand_primary_color" class="plat-input" value="{{ old('brand_primary_color') }}"
                       maxlength="7" placeholder="#0d9488" pattern="^#[0-9a-fA-F]{6}$">
                <small style="font-size:11px;color:var(--plat-muted);">#rrggbb formatında.</small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Kendi Domaini</label>
                <input type="text" name="primary_domain" class="plat-input" value="{{ old('primary_domain') }}"
                       maxlength="190" placeholder="a.yourgermanuni.com">
                <small style="font-size:11px;color:var(--plat-muted);">
                    Bu adresten gelen ziyaretçi (giriş yapmamış olsa bile) bu markayı görür.
                    <strong style="color:#fff;">Önce DNS + SSL kurulmuş olmalı.</strong>
                </small>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label">Public Pazarlama İçeriği</label>
                <input type="hidden" name="public_marketing" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;cursor:pointer;">
                    <input type="checkbox" name="public_marketing" value="1" {{ old('public_marketing') ? 'checked' : '' }} style="accent-color:var(--plat-accent);margin-top:2px;">
                    <span style="font-size:12px;color:var(--plat-muted);line-height:1.6;">
                        Giriş sayfasında <strong style="color:#fff;">"Ücretsiz Başvuru"</strong> ve tanıtım
                        listesi gösterilsin.<br>
                        B2B partner firmalar için <strong style="color:#fff;">kapalı bırakın</strong> —
                        onların ziyaretçisi reklam görmemeli.
                    </span>
                </label>
            </div>

            <div style="margin-top:16px;padding:12px;background:var(--plat-panel-2);border-radius:8px;border:1px solid var(--plat-border);">
                <div style="font-size:12px;color:var(--plat-muted);line-height:1.6;">
                    Marka <strong style="color:#fff;">domainden</strong>, veri erişimi
                    <strong style="color:#fff;">kullanıcıdan</strong> belirlenir. Yani başka bir
                    firmanın adresinden giriş yapan kullanıcı yine yalnızca kendi verisini görür.
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:8px;margin-top:24px;justify-content:flex-end;">
        <a href="{{ route('platform.companies') }}" class="plat-btn plat-btn-ghost">İptal</a>
        <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="plus" size="14" /> Şirketi Oluştur</button>
    </div>
</form>

@endsection
