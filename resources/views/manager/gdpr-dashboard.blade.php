@extends('manager.layouts.app')
@section('title','GDPR Uyum Paneli')
@section('page_title', 'GDPR Uyum Paneli')

@push('head')
<style>
/* ─── GDPR Dashboard polish ─── */
.gd-hero { background:linear-gradient(135deg,#eef4ff 0%,#f8faff 100%); border:1px solid #dbe4f2; border-radius:12px; padding:14px 18px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.gd-hero-info .title { font-size:14px; font-weight:700; color:#0f172a; margin:0 0 3px; }
.gd-hero-info .sub { font-size:11px; color:var(--u-muted,#64748b); margin:0; }
.gd-hero-actions { display:flex; gap:6px; flex-wrap:wrap; }
.gd-hero-actions .btn { font-size:11px !important; padding:6px 12px !important; min-height:30px !important; }

/* KPI strip */
.gdpr-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:900px){ .gdpr-kpi-strip { grid-template-columns:1fr 1fr; } }
.gdpr-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; transition:all .12s; }
.gdpr-kpi:hover { border-color:#bfdbfe; transform:translateY(-1px); box-shadow:0 2px 8px rgba(30,64,175,.08); }
.gdpr-kpi-val   { font-size:24px; font-weight:800; line-height:1.1; margin-bottom:4px; }
.gdpr-kpi-label { font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; }

/* Section card */
.gd-section { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:14px 16px; margin-bottom:12px; }
.gd-section h3 { margin:0 0 12px; font-size:13px; font-weight:700; color:var(--u-text,#0f172a); padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); letter-spacing:.2px; }

/* Alert banner */
.gd-alert { padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-left:3px solid #d97706; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; margin-bottom:12px; }
.gd-alert-title { font-size:12px; font-weight:700; color:#92400e; }
.gd-alert-sub { font-size:11px; color:#78350f; margin-top:2px; }
.gd-alert .btn { font-size:11px !important; padding:6px 14px !important; white-space:nowrap; flex-shrink:0; }

/* Consent progress */
.gd-consent-head { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px; }
.gd-consent-label { font-size:11px; color:var(--u-muted,#64748b); font-weight:500; }
.gd-consent-pct { font-size:18px; font-weight:800; color:#16a34a; }
.gd-consent-bar { height:8px; background:var(--u-line,#e5e9f0); border-radius:999px; overflow:hidden; margin-bottom:14px; }
.gd-consent-fill { height:100%; background:linear-gradient(90deg,#16a34a,#22c55e); border-radius:999px; transition:width .4s; }

.gdpr-stat-mini  { background:#f8fafc; border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:10px 12px; text-align:center; transition:all .12s; }
.gdpr-stat-mini:hover { border-color:#cbd5e1; }
.gdpr-stat-mini-val { font-size:20px; font-weight:800; color:var(--u-text,#0f172a); line-height:1.1; margin-bottom:3px; }
.gdpr-stat-mini-lbl { font-size:10px; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; font-weight:600; }

/* Link buttons */
.gdpr-link-btn { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid var(--u-line,#e5e9f0); border-radius:8px; font-size:12px; font-weight:600; color:var(--u-text,#0f172a); text-decoration:none; transition:all .12s; background:#fff; }
.gdpr-link-btn:hover { background:#eef4ff; border-color:#bfdbfe; color:#1e40af; transform:translateX(2px); }

/* Retention policy row */
.gd-policy-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.gd-policy-row:last-child { border-bottom:none; }
.gd-policy-row .badge { font-size:10px; }

/* Compliance note */
.gd-note { margin-top:12px; padding:10px 12px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; }
.gd-note-title { font-size:11px; font-weight:700; color:#0369a1; margin-bottom:3px; }
.gd-note-body { font-size:11px; color:#0c4a6e; line-height:1.6; }
</style>
@endpush

@section('content')
@php
$totalConsent = max(1, $consentStats['total']);
$activeRate   = round($consentStats['active'] / $totalConsent * 100, 1);
@endphp

{{-- Hero --}}
<div class="gd-hero">
    <div class="gd-hero-info">
        <h1 class="title">🔒 GDPR / DSGVO Uyum Paneli</h1>
        <p class="sub">Kişisel veri uyumu, rıza yönetimi ve veri saklama politikaları</p>
    </div>
    <div class="gd-hero-actions">
        <a class="btn alt" href="{{ url('/manager/requests') }}">🗑 Silme Talepleri</a>
        <a class="btn alt" href="{{ url('/config') }}#gdpr">⚙️ Ayarlar</a>
    </div>
</div>

{{-- KPI Strip --}}
<div class="gdpr-kpi-strip">
    <div class="gdpr-kpi" style="border-top-color:#d97706;">
        <div class="gdpr-kpi-val" style="color:#d97706;">{{ $pendingErasures }}</div>
        <div class="gdpr-kpi-label">Bekleyen Silme Talebi</div>
    </div>
    <div class="gdpr-kpi" style="border-top-color:#16a34a;">
        <div class="gdpr-kpi-val" style="color:#16a34a;">{{ $completedErasures }}</div>
        <div class="gdpr-kpi-label">Tamamlanan Silme</div>
    </div>
    <div class="gdpr-kpi">
        <div class="gdpr-kpi-val" style="color:#1e40af;">{{ $recentExports }}</div>
        <div class="gdpr-kpi-label">Son 30 Gün Export</div>
    </div>
    <div class="gdpr-kpi" style="border-top-color:#dc2626;">
        <div class="gdpr-kpi-val" style="color:#dc2626;">{{ $piiAccessLogs }}</div>
        <div class="gdpr-kpi-label">Son 7 Gün PII Erişim</div>
    </div>
</div>

{{-- Uyarı Bandı --}}
@if($pendingErasures > 0)
<div class="gd-alert">
    <div>
        <div class="gd-alert-title">⚠️ {{ $pendingErasures }} bekleyen silme talebi var</div>
        <div class="gd-alert-sub">GDPR kapsamındaki silme talepleri 30 gün içinde işlenmelidir.</div>
    </div>
    <a href="{{ url('/manager/requests') }}" class="btn warn">İncele →</a>
</div>
@endif

<div class="grid2" style="gap:12px;margin-bottom:12px;">
    {{-- Rıza Kayıtları --}}
    <section class="gd-section">
        <h3>📋 Rıza Kayıtları</h3>

        <div class="gd-consent-head">
            <span class="gd-consent-label">Aktif rıza oranı</span>
            <span class="gd-consent-pct">%{{ $activeRate }}</span>
        </div>
        <div class="gd-consent-bar">
            <div class="gd-consent-fill" style="width:{{ $activeRate }}%;"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
            <div class="gdpr-stat-mini">
                <div class="gdpr-stat-mini-val">{{ $consentStats['total'] }}</div>
                <div class="gdpr-stat-mini-lbl">Toplam</div>
            </div>
            <div class="gdpr-stat-mini" style="background:#f0fdf4;border-color:#bbf7d0;">
                <div class="gdpr-stat-mini-val" style="color:#16a34a;">{{ $consentStats['active'] }}</div>
                <div class="gdpr-stat-mini-lbl">Aktif</div>
            </div>
            <div class="gdpr-stat-mini" style="background:#fef2f2;border-color:#fecaca;">
                <div class="gdpr-stat-mini-val" style="color:#dc2626;">{{ $consentStats['revoked'] }}</div>
                <div class="gdpr-stat-mini-lbl">Geri Alınan</div>
            </div>
        </div>
    </section>

    {{-- Hızlı Erişim --}}
    <section class="gd-section">
        <h3>⚡ Hızlı Erişim</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a class="gdpr-link-btn" href="{{ url('/manager/requests') }}">
                🗑️ Silme Talepleri
                @if($pendingErasures > 0)
                <span class="badge warn" style="margin-left:auto;font-size:10px;">{{ $pendingErasures }}</span>
                @endif
            </a>
            <a class="gdpr-link-btn" href="{{ url('/config') }}#gdpr">
                ⚙️ Veri Saklama Ayarları
            </a>
            <a class="gdpr-link-btn" href="{{ url('/manager/notification-stats') }}">
                📊 Bildirim İstatistikleri
            </a>
        </div>
        <div class="gd-note">
            <div class="gd-note-title">📋 GDPR / DSGVO Uyum Notu</div>
            <div class="gd-note-body">
                Veri saklama politikaları her gece 03:00'te otomatik çalışır.
                Silme talepleri 30 gün içinde işlenmelidir.
            </div>
        </div>
    </section>
</div>

{{-- Veri Saklama Politikaları --}}
<section class="gd-section">
    <h3>🗄️ Aktif Veri Saklama Politikaları</h3>
    @if($retentionPolicies->isEmpty())
    <div style="padding:24px;text-align:center;">
        <div style="font-size:12px;color:var(--u-muted,#64748b);margin-bottom:8px;">Henüz politika tanımlanmamış.</div>
        <a href="{{ url('/config') }}#gdpr" class="btn alt" style="font-size:11px;padding:6px 14px;">+ Politika Ekle</a>
    </div>
    @else
    <div>
        @foreach($retentionPolicies as $policy)
        <div class="gd-policy-row">
            <span class="badge info">{{ $policy->entity_type }}</span>
            <span style="font-size:12px;color:var(--u-text,#0f172a);flex:1;">{{ $policy->action }}</span>
            <span style="font-size:11px;color:var(--u-muted,#64748b);white-space:nowrap;">{{ $policy->retention_days }} gün</span>
            <span class="badge {{ $policy->is_active ? 'ok' : 'danger' }}">
                {{ $policy->is_active ? 'Aktif' : 'Pasif' }}
            </span>
        </div>
        @endforeach
    </div>
    @endif
</section>

{{-- ════ YASAL METİN EDİTÖRÜ (3 kind × 3 locale) ════ --}}
@php
    $kinds = [
        'privacy' => ['emoji' => '📜', 'label' => 'Gizlilik / Veri Koruma', 'placeholders' => [
            'tr' => 'Kişisel Verilerin Korunması Aydınlatma Metni (KVKK)',
            'de' => 'Datenschutzerklärung (DSGVO)',
            'en' => 'Privacy Policy (GDPR)',
        ]],
        'cookie'  => ['emoji' => '🍪', 'label' => 'Çerez Politikası', 'placeholders' => [
            'tr' => 'Çerez Politikası',
            'de' => 'Cookie-Richtlinie',
            'en' => 'Cookie Policy',
        ]],
        'terms'   => ['emoji' => '📋', 'label' => 'Kullanım Şartları', 'placeholders' => [
            'tr' => 'Kullanım Şartları ve Hizmet Sözleşmesi',
            'de' => 'Nutzungsbedingungen / AGB',
            'en' => 'Terms of Use / Service Agreement',
        ]],
        'imprint' => ['emoji' => '🏛️', 'label' => 'Impressum / Künye', 'placeholders' => [
            'tr' => 'Künye / Şirket Bilgileri',
            'de' => 'Impressum (DDG § 5 zorunlu)',
            'en' => 'Imprint / Company Info',
        ]],
        'tom' => ['emoji' => '🛡️', 'label' => 'TOM — Teknik ve Organizasyonel Önlemler', 'placeholders' => [
            'tr' => 'Erişim kontrolü, şifreleme, yedekleme, çalışan eğitimi, fiziksel güvenlik...',
            'de' => 'TOM nach Art. 32 DSGVO — Zugangskontrolle, Verschlüsselung, Backup...',
            'en' => 'Technical and Organizational Measures (Art. 32 GDPR)',
        ]],
        'incident_plan' => ['emoji' => '🚨', 'label' => 'Veri İhlali Acil Eylem Planı', 'placeholders' => [
            'tr' => 'Veri ihlali tespiti → 72 saat bildirim → LDI NRW şablonu...',
            'de' => 'Datenpannen-Notfallplan (Art. 33-34 DSGVO) — 72h Meldepflicht...',
            'en' => 'Data Breach Notification Plan (Art. 33-34 GDPR)',
        ]],
    ];
@endphp

@if(session('flash_success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:10px 14px;border-radius:8px;margin:14px 0;font-size:13px;">
        {{ session('flash_success') }}
    </div>
@endif

<form method="post" action="{{ route('manager.gdpr.policy.save') }}" id="policyForm">
    @csrf

    @foreach($kinds as $kindKey => $kindMeta)
        <section class="gd-section">
            <h3>{{ $kindMeta['emoji'] }} {{ $kindMeta['label'] }}
                <span style="font-size:11px;font-weight:600;color:var(--u-muted,#64748b);margin-left:8px;">— 3 dil</span>
            </h3>

            @if($loop->first)
                <p style="font-size:12px;color:var(--u-muted,#64748b);margin:0 0 14px;line-height:1.6;">
                    Aşağıdaki 3 yasal metni KVKK (TR), DSGVO/Datenschutz (DE) ve GDPR (EN) olarak doğrudan düzenle.
                    Kayıt sonrası anında geçerli olur — config dosyasına dokunmaya gerek yok. HTML işaretleri kullanabilirsin
                    (<code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;strong&gt;</code> vb.).
                </p>
            @endif

            <div class="pt-tabs" style="display:flex;gap:6px;margin-bottom:14px;border-bottom:1px solid var(--u-line,#e5e9f0);padding-bottom:0;">
                @foreach(\App\Models\PolicyDocument::LOCALES as $i => $loc)
                    <button type="button"
                            class="pt-tab {{ $i === 0 ? 'active' : '' }}"
                            data-pt-kind="{{ $kindKey }}"
                            data-pt-tab="{{ $loc }}"
                            style="padding:8px 16px;border:none;border-bottom:2px solid transparent;background:transparent;font-size:13px;font-weight:600;color:var(--u-muted,#64748b);cursor:pointer;">
                        {{ \App\Models\PolicyDocument::LOCALE_LABELS[$loc] ?? $loc }}
                    </button>
                @endforeach
            </div>

            @foreach(\App\Models\PolicyDocument::LOCALES as $i => $loc)
                @php $entry = $policyTexts[$kindKey][$loc] ?? ['title' => '', 'body' => '', 'updated_at' => null]; @endphp
                <div class="pt-pane" data-pt-kind-pane="{{ $kindKey }}" data-pt-pane="{{ $loc }}" style="display:{{ $i === 0 ? 'block' : 'none' }};">
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Başlık ({{ strtoupper($loc) }})</label>
                    <input type="text"
                           name="titles[{{ $kindKey }}][{{ $loc }}]"
                           value="{{ $entry['title'] }}"
                           maxlength="190"
                           placeholder="{{ $kindMeta['placeholders'][$loc] ?? '' }}"
                           style="width:100%;padding:9px 11px;border:1px solid var(--u-line,#cbd5e1);border-radius:8px;font-size:13px;margin-bottom:10px;">

                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">İçerik ({{ strtoupper($loc) }})</label>
                    <textarea name="bodies[{{ $kindKey }}][{{ $loc }}]"
                              rows="16"
                              style="width:100%;padding:11px 13px;border:1px solid var(--u-line,#cbd5e1);border-radius:8px;font-size:13px;font-family:ui-monospace,'SF Mono',Monaco,monospace;line-height:1.6;resize:vertical;">{{ $entry['body'] }}</textarea>

                    @if(!empty($entry['updated_at']))
                        <div style="font-size:11px;color:var(--u-muted,#94a3b8);margin-top:6px;">
                            Son güncelleme: {{ \Carbon\Carbon::parse($entry['updated_at'])->format('d.m.Y H:i') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
    @endforeach

    {{-- Sticky save bar --}}
    <div style="position:sticky;bottom:0;background:var(--u-card,#fff);padding:14px 18px;border-top:1px solid var(--u-line,#e5e9f0);border-radius:0 0 10px 10px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 -2px 8px rgba(0,0,0,.04);margin-top:14px;">
        <div style="font-size:12px;color:var(--u-muted,#64748b);">
            💾 Kaydet butonu 6 yasal metnin (Gizlilik · Çerez · Kullanım · Impressum · TOM · Acil Plan) TR/DE/EN dillerini topluca günceller (toplam 18 alan).
        </div>
        <button type="submit" class="btn ok" style="padding:10px 24px;font-size:13px;font-weight:700;">
            💾 Tümünü Kaydet
        </button>
    </div>
</form>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Her kind için ayrı tab grubu (kind içinde locale tab'ları)
    document.querySelectorAll('[data-pt-tab]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var kind = this.dataset.ptKind;
            var key  = this.dataset.ptTab;
            // Sadece bu kind'in tab'larını sıfırla
            document.querySelectorAll('[data-pt-kind="' + kind + '"]').forEach(function(b){
                b.classList.remove('active');
                b.style.borderBottomColor = 'transparent';
                b.style.color = 'var(--u-muted,#64748b)';
            });
            this.classList.add('active');
            this.style.borderBottomColor = '#1e40af';
            this.style.color = '#1e40af';
            // Sadece bu kind'in pane'lerini göster/gizle
            document.querySelectorAll('[data-pt-kind-pane="' + kind + '"]').forEach(function(p){
                p.style.display = (p.dataset.ptPane === key) ? 'block' : 'none';
            });
        });
    });
    // İlk active stillerini uygula (her kind için)
    document.querySelectorAll('[data-pt-tab].active').forEach(function(b){
        b.style.borderBottomColor = '#1e40af';
        b.style.color = '#1e40af';
    });
})();
</script>
@endpush

@endsection
