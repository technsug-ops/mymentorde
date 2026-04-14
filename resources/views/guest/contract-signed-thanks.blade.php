@extends('guest.layouts.app')

@section('title', 'Sözleşme Gönderildi')
@section('page_title', 'Sözleşme Gönderildi')

@push('head')
<style>
/* ── cst-* contract-signed-thanks scoped ── */

/* Breadcrumb — same as contract page */
.cst-breadcrumb { display:flex; align-items:center; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
.cst-step-chip { display:flex; align-items:center; gap:5px; padding:5px 13px; border-radius:20px; font-size:12px; font-weight:600; color:var(--u-muted,#64748b); background:var(--u-subtle,#f8fafc); border:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.cst-step-chip.done   { background:rgba(22,163,74,.1); color:#166534; border-color:rgba(22,163,74,.3); }
.cst-step-chip.active { background:rgba(37,99,235,.1); color:var(--u-brand,#2563eb); border-color:rgba(37,99,235,.3); font-weight:700; }
.cst-step-arrow { color:var(--u-muted,#64748b); font-size:14px; }

/* 2-column layout */
.cst-layout { display:grid; grid-template-columns:2fr 1fr; gap:20px; align-items:start; }
.cst-right-sticky { position:sticky; top:80px; max-height:calc(100vh - 96px); overflow-y:auto; display:flex; flex-direction:column; gap:14px; scrollbar-width:thin; }
@media(max-width:860px){ .cst-layout { grid-template-columns:1fr; } .cst-right-sticky { position:static; max-height:none; } }

/* Hero success card */
.cst-hero { background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%); border:1.5px solid rgba(22,163,74,.25); border-radius:16px; padding:32px 28px; text-align:center; margin-bottom:14px; position:relative; overflow:hidden; }
.cst-hero::before { content:''; position:absolute; top:-40px; right:-40px; width:140px; height:140px; background:rgba(22,163,74,.06); border-radius:50%; }
.cst-hero::after  { content:''; position:absolute; bottom:-30px; left:-30px; width:100px; height:100px; background:rgba(37,99,235,.05); border-radius:50%; }
.cst-hero-icon { font-size:52px; line-height:1; margin-bottom:14px; position:relative; z-index:1; }
.cst-hero-title { font-size:22px; font-weight:800; color:#166534; margin:0 0 10px; position:relative; z-index:1; }
.cst-hero-desc  { font-size:14px; color:#374151; line-height:1.7; margin:0 auto; max-width:480px; position:relative; z-index:1; }

/* Timeline steps */
.cst-timeline { display:flex; flex-direction:column; gap:0; }
.cst-tl-item  { display:flex; gap:16px; align-items:flex-start; padding:14px 0; border-bottom:1px solid var(--u-line,#e2e8f0); }
.cst-tl-item:last-child { border-bottom:none; padding-bottom:0; }
.cst-tl-icon  { width:40px; height:40px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:18px; }
.cst-tl-icon.pending { background:#fff7ed; border:2px solid #fed7aa; }
.cst-tl-icon.auto    { background:#eff6ff; border:2px solid #bfdbfe; }
.cst-tl-icon.done    { background:#f0fdf4; border:2px solid #bbf7d0; }
.cst-tl-body  { flex:1; min-width:0; }
.cst-tl-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); margin-bottom:3px; display:flex; align-items:center; gap:8px; }
.cst-tl-desc  { font-size:12px; color:var(--u-muted,#64748b); line-height:1.5; }

/* Doc prep grid */
.cst-doc-group { }
.cst-doc-label { font-size:11px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
.cst-doc-list  { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:4px; }
.cst-doc-list li { font-size:12px; color:var(--u-text,#0f172a); display:flex; align-items:flex-start; gap:6px; line-height:1.4; }
.cst-doc-list li::before { content:'○'; color:var(--u-muted,#64748b); flex-shrink:0; font-size:10px; margin-top:2px; }

/* Sidebar cards */
.cst-card { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:14px; overflow:hidden; }
.cst-card-head { padding:12px 16px; border-bottom:1px solid var(--u-line,#e2e8f0); font-size:13px; font-weight:700; color:var(--u-text,#0f172a); display:flex; align-items:center; gap:7px; background:var(--u-subtle,#f8fafc); }
.cst-card-body { padding:14px 16px; }

/* Info row */
.cst-info-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid var(--u-line,#e2e8f0); }
.cst-info-row:last-child { border-bottom:none; }
.cst-info-key { font-size:12px; color:var(--u-muted,#64748b); }
.cst-info-val { font-size:12px; font-weight:600; color:var(--u-text,#0f172a); text-align:right; }

/* Action links */
.cst-action { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--u-line,#e2e8f0); text-decoration:none; color:var(--u-text,#0f172a); font-size:13px; font-weight:600; transition:color .15s; }
.cst-action:last-child { border-bottom:none; }
.cst-action:hover { color:var(--u-brand,#2563eb); }
.cst-action-icon { width:32px; height:32px; border-radius:8px; background:var(--u-subtle,#f8fafc); border:1px solid var(--u-line,#e2e8f0); display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }

/* Info notice */
.cst-notice { background:rgba(37,99,235,.05); border:1px solid rgba(37,99,235,.2); border-radius:12px; padding:14px 16px; font-size:12px; color:#1e3a5f; line-height:1.7; }
.cst-notice strong { color:var(--u-brand,#2563eb); }
</style>
@endpush

@section('content')
@php
    $guest  = $guest  ?? null;
    $gName  = trim(($guest?->first_name ?? '') . ' ' . ($guest?->last_name ?? ''));
    $signedAt = $guest?->contract_signed_at ?? $guest?->updated_at;
@endphp

{{-- ── Breadcrumb ── --}}
<div class="cst-breadcrumb">
    @foreach([
        ['label'=>'Başvuru',       'done'=>true,  'active'=>false],
        ['label'=>'Değerlendirme', 'done'=>true,  'active'=>false],
        ['label'=>'Sözleşme',      'done'=>true,  'active'=>false],
        ['label'=>'Belgeler',      'done'=>false, 'active'=>true ],
        ['label'=>'Kayıt',         'done'=>false, 'active'=>false],
    ] as $i => $s)
        @if($i > 0)<span class="cst-step-arrow">›</span>@endif
        <div class="cst-step-chip {{ $s['done'] ? 'done' : ($s['active'] ? 'active' : '') }}">
            @if($s['done'])✓ @elseif($s['active'])● @endif{{ $s['label'] }}
        </div>
    @endforeach
    <span style="margin-left:auto;"><span class="badge ok">Sözleşme Teslim Edildi</span></span>
</div>

{{-- ── 2-Column Layout ── --}}
<div class="cst-layout">

    {{-- ══ LEFT ══ --}}
    <div>

        {{-- Hero --}}
        <div class="cst-hero">
            <div class="cst-hero-icon">🎉</div>
            <div class="cst-hero-title">İmzalı Sözleşmeniz Alındı!</div>
            <div class="cst-hero-desc">
                Sözleşmeniz ekibimize başarıyla iletildi.
                İnceleme tamamlandıktan sonra <strong>öğrenci portalına giriş izniniz tanımlanacaktır.</strong>
            </div>
            @if($signedAt)
            <div style="margin-top:14px;font-size:var(--tx-xs);color:#4b5563;position:relative;z-index:1;">
                📅 Gönderim zamanı: <strong>{{ \Carbon\Carbon::parse($signedAt)->format('d.m.Y H:i') }}</strong>
            </div>
            @endif
        </div>

        {{-- Sırada Ne Var --}}
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Sırada Ne Var?</div>
            </div>
            <div class="card-body">
                <div class="cst-timeline">
                    <div class="cst-tl-item">
                        <div class="cst-tl-icon pending">⏳</div>
                        <div class="cst-tl-body">
                            <div class="cst-tl-title">
                                1. Sözleşme İncelemesi
                                <span class="badge warn">Bekleniyor</span>
                            </div>
                            <div class="cst-tl-desc">Ekibimiz imzalı dosyanızı inceleyecek. Genellikle <strong>1–2 iş günü</strong> sürer.</div>
                        </div>
                    </div>
                    <div class="cst-tl-item">
                        <div class="cst-tl-icon auto">📬</div>
                        <div class="cst-tl-body">
                            <div class="cst-tl-title">
                                2. Onay Bildirimi
                                <span class="badge info">Otomatik</span>
                            </div>
                            <div class="cst-tl-desc">Onay tamamlandığında <strong>e-posta ve sistem bildirimi</strong> alacaksınız.</div>
                        </div>
                    </div>
                    <div class="cst-tl-item">
                        <div class="cst-tl-icon done">🎓</div>
                        <div class="cst-tl-body">
                            <div class="cst-tl-title">
                                3. Öğrenci Portalı Erişimi
                                <span class="badge ok">Aynı Hesap</span>
                            </div>
                            <div class="cst-tl-desc">Onay sonrası <strong>mevcut e-posta ve şifrenizle</strong> öğrenci portalına giriş yapabilirsiniz.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hazırlanabilecekler --}}
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Onay Beklerken Hazırlanabilecekler</div>
                <span class="badge info">Önerilen</span>
            </div>
            <div class="card-body">
                <p class="muted" style="margin:0 0 14px;font-size:var(--tx-xs);line-height:1.6;">
                    Aşağıdaki belgeleri önceden hazırlamanız ilerleyen süreçleri hızlandıracaktır.
                </p>
                <div class="grid2" style="gap:16px;">
                    <div class="cst-doc-group">
                        <div class="cst-doc-label">Kimlik & Kişisel</div>
                        <ul class="cst-doc-list">
                            <li>Pasaport fotokopisi (tüm sayfalar)</li>
                            <li>Nüfus cüzdanı / kimlik kartı</li>
                            <li>Biyometrik fotoğraf (3 adet)</li>
                        </ul>
                    </div>
                    <div class="cst-doc-group">
                        <div class="cst-doc-label">Eğitim Belgeleri</div>
                        <ul class="cst-doc-list">
                            <li>Lise / üniversite diploması</li>
                            <li>Transkript (noter onaylı)</li>
                            <li>Apostil &amp; tercüme (gerekirse)</li>
                        </ul>
                    </div>
                    <div class="cst-doc-group">
                        <div class="cst-doc-label">Finansal</div>
                        <ul class="cst-doc-list">
                            <li>Banka hesap özeti (son 3 ay)</li>
                            <li>Sponsor mektubu (varsa)</li>
                            <li>Finansal taahhüt belgesi</li>
                        </ul>
                    </div>
                    <div class="cst-doc-group">
                        <div class="cst-doc-label">Dil & Akademik</div>
                        <ul class="cst-doc-list">
                            <li>Dil sertifikası (TestDaF, IELTS vb.)</li>
                            <li>Motivasyon mektubu taslağı</li>
                            <li>CV / özgeçmiş</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notice --}}
        <div class="cst-notice">
            <strong>Aday Öğrenci Paneli aktif kalmaya devam eder.</strong><br>
            Onay sürecinde mesaj gönderebilir, belgelerinizi takip edebilir ve danışmanınızla iletişim kurabilirsiniz. Sorunuz için destek talebi oluşturabilirsiniz.
        </div>

    </div>{{-- /LEFT --}}

    {{-- ══ RIGHT ══ --}}
    <div class="cst-right-sticky">

        {{-- Durum Kartı --}}
        <div class="cst-card">
            <div class="cst-card-head">📋 Başvuru Durumu</div>
            <div class="cst-card-body">
                <div class="cst-info-row">
                    <span class="cst-info-key">Sözleşme</span>
                    <span class="badge ok">Teslim Edildi</span>
                </div>
                <div class="cst-info-row">
                    <span class="cst-info-key">Onay</span>
                    <span class="badge warn">Bekleniyor</span>
                </div>
                @if($gName)
                <div class="cst-info-row">
                    <span class="cst-info-key">Ad Soyad</span>
                    <span class="cst-info-val">{{ $gName }}</span>
                </div>
                @endif
                @if($guest?->email)
                <div class="cst-info-row">
                    <span class="cst-info-key">E-posta</span>
                    <span class="cst-info-val" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;">{{ $guest->email }}</span>
                </div>
                @endif
                @if($guest?->application_type)
                <div class="cst-info-row">
                    <span class="cst-info-key">Başvuru Tipi</span>
                    <span class="cst-info-val">{{ $guest->application_type }}</span>
                </div>
                @endif
                @if($signedAt)
                <div class="cst-info-row">
                    <span class="cst-info-key">Gönderim</span>
                    <span class="cst-info-val">{{ \Carbon\Carbon::parse($signedAt)->format('d.m.Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Hızlı Erişim --}}
        <div class="cst-card">
            <div class="cst-card-head">⚡ Hızlı Erişim</div>
            <div class="cst-card-body">
                <a href="{{ route('guest.dashboard') }}" class="cst-action">
                    <div class="cst-action-icon">🏠</div>
                    Panelime Dön
                </a>
                <a href="{{ route('guest.registration.documents') }}" class="cst-action">
                    <div class="cst-action-icon">📁</div>
                    Belgelerim
                </a>
                <a href="{{ route('guest.messages') }}" class="cst-action">
                    <div class="cst-action-icon">💬</div>
                    Danışmana Mesaj
                </a>
                <a href="{{ route('guest.tickets') }}" class="cst-action">
                    <div class="cst-action-icon">🎫</div>
                    Destek Talebi Aç
                </a>
                <a href="{{ route('guest.contract') }}" class="cst-action">
                    <div class="cst-action-icon">📄</div>
                    Sözleşmemi Görüntüle
                </a>
            </div>
        </div>

        {{-- Yardım --}}
        <div style="background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:14px 16px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-brand,#2563eb);margin-bottom:6px;">❓ Sorunuz mu var?</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;margin-bottom:10px;">
                Sözleşme süreci hakkında her türlü sorunuzu danışmanınıza iletebilirsiniz.
            </div>
            <a href="{{ route('guest.messages') }}" class="btn" style="font-size:var(--tx-xs);padding:7px 12px;">💬 Danışmana Sor</a>
        </div>

    </div>{{-- /RIGHT --}}

</div>{{-- /cst-layout --}}
@endsection
