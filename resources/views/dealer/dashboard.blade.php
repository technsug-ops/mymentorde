@extends('dealer.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('head')
<style>
/* ── Dashboard — Guest/Student pattern: simple, scannable, action-driven ── */
.dd-greeting { margin-bottom:20px; }
.dd-greeting h1 { font-size:22px; font-weight:700; color:var(--text,#111); margin:0 0 4px; }
.dd-greeting p  { font-size:14px; color:var(--muted,#64748b); margin:0; }

/* Journey progress */
.dd-journey { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:14px; padding:22px 24px; margin-bottom:18px; }
.dd-journey-title { font-size:13px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:14px; }
.dd-steps { display:flex; gap:0; align-items:flex-start; }
.dd-step { flex:1; text-align:center; position:relative; }
.dd-step-dot { width:32px; height:32px; border-radius:50%; margin:0 auto 8px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; border:2px solid #e2e8f0; background:#f8fafc; color:#94a3b8; transition:all .3s; }
.dd-step.done .dd-step-dot  { background:#16a34a; border-color:#16a34a; color:#fff; }
.dd-step.active .dd-step-dot { background:#3b82f6; border-color:#3b82f6; color:#fff; animation:pulse 2s infinite; }
.dd-step-label { font-size:11px; color:var(--muted,#94a3b8); font-weight:600; }
.dd-step.done .dd-step-label { color:#16a34a; }
.dd-step.active .dd-step-label { color:#3b82f6; }
.dd-step-line { position:absolute; top:16px; left:calc(50% + 18px); right:calc(-50% + 18px); height:2px; background:#e2e8f0; z-index:0; }
.dd-step.done .dd-step-line { background:#16a34a; }
.dd-step:last-child .dd-step-line { display:none; }
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,.3)} 50%{box-shadow:0 0 0 8px rgba(59,130,246,0)} }

/* Hero CTA */
.dd-hero { background:linear-gradient(135deg,#16a34a,#0891b2); border-radius:14px; padding:24px 28px; margin-bottom:18px; display:flex; align-items:center; gap:18px; flex-wrap:wrap; color:#fff; }
.dd-hero-icon { font-size:40px; flex-shrink:0; }
.dd-hero-body { flex:1; min-width:200px; }
.dd-hero-body h2 { font-size:18px; font-weight:700; margin:0 0 4px; color:#fff; }
.dd-hero-body p  { font-size:13px; opacity:.9; margin:0; }
.dd-hero-btn { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.3); padding:10px 24px; border-radius:10px; font-weight:700; font-size:14px; text-decoration:none; white-space:nowrap; transition:background .2s; }
.dd-hero-btn:hover { background:rgba(255,255,255,.35); color:#fff; }

/* KPI grid */
.dd-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.dd-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:16px 18px; text-align:center; }
.dd-kpi-icon { font-size:22px; margin-bottom:6px; }
.dd-kpi-val  { font-size:24px; font-weight:800; color:var(--text,#111); }
.dd-kpi-label { font-size:11px; color:var(--muted,#64748b); font-weight:600; margin-top:2px; }
@media(max-width:700px){ .dd-kpis { grid-template-columns:1fr 1fr; } }

/* Bonus card */
.dd-bonus { border-radius:12px; padding:18px 22px; margin-bottom:18px; }

/* Checklist */
.dd-checklist { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.dd-check-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border,#f1f5f9); font-size:13px; }
.dd-check-item:last-child { border-bottom:none; }
.dd-check-icon { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; }
.dd-check-icon.done { background:#dcfce7; color:#16a34a; }
.dd-check-icon.todo { background:#fef3c7; color:#d97706; }
.dd-check-icon.lock { background:#f1f5f9; color:#94a3b8; }

/* Quick links */
.dd-quick { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.dd-quick a { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:18px 14px; text-align:center; text-decoration:none; color:var(--text,#111); transition:border-color .2s,box-shadow .2s; }
.dd-quick a:hover { border-color:#16a34a; box-shadow:0 4px 12px rgba(22,163,74,.1); }
.dd-quick-icon { font-size:24px; margin-bottom:6px; }
.dd-quick-label { font-size:12px; font-weight:600; }
@media(max-width:700px){ .dd-quick { grid-template-columns:1fr 1fr; } }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $firstName = explode(' ', $user?->name ?? 'Bayi')[0];

    // Journey steps based on dealer state
    $hasLead      = ($kpis['lead_total'] ?? 0) > 0;
    $hasConverted = ($kpis['converted_total'] ?? 0) > 0;
    $hasRevenue   = ($kpis['revenue_total'] ?? 0) > 0;
    $bonusOpen    = ($bonus['status'] ?? 'locked') === 'unlocked';

    $journeySteps = [
        ['label' => 'Kayıt',           'done' => true],
        ['label' => 'İlk Yönlendirme', 'done' => $hasLead],
        ['label' => 'Öğrenci Dönüşümü','done' => $hasConverted],
        ['label' => 'İlk Kazanç',      'done' => $hasRevenue],
        ['label' => 'Bonus Aktif',     'done' => $bonusOpen],
    ];

    // Find active step (first not-done)
    $activeIdx = count($journeySteps); // past all = all done
    foreach ($journeySteps as $i => $s) {
        if (!$s['done']) { $activeIdx = $i; break; }
    }

    // Hero CTA based on current step
    $heroConfig = match($activeIdx) {
        1 => ['icon' => '🚀', 'title' => 'İlk öğrencinizi yönlendirin!', 'desc' => 'Formu doldurup bir öğrenci yönlendirdiğinizde bonusunuz beklemeye alınır.', 'url' => '/dealer/lead-create', 'cta' => 'Öğrenci Yönlendir'],
        2 => ['icon' => '📞', 'title' => 'Yönlendirmelerinizi takip edin', 'desc' => 'Lead\'lerinizin durumunu kontrol edin. Dönüşüm gerçekleştiğinde kazanç hesabınıza yansır.', 'url' => '/dealer/leads', 'cta' => 'Yönlendirmelerim'],
        3 => ['icon' => '💰', 'title' => 'İlk kazancınız yolda!', 'desc' => 'Öğrenciniz dönüşüm yaptı. Ödeme yapıldığında kazancınız aktif olur.', 'url' => '/dealer/earnings', 'cta' => 'Kazancım'],
        4 => ['icon' => '🎁', 'title' => 'Bonusunuzu çekin!', 'desc' => 'Tebrikler! 100€ hoş geldin bonusunuz aktif. Ödemeler sayfasından çekebilirsiniz.', 'url' => '/dealer/payments', 'cta' => 'Ödeme Talebi'],
        default => ['icon' => '🏆', 'title' => 'Harika gidiyorsun!', 'desc' => 'Tüm adımları tamamladınız. Daha fazla yönlendirme yaparak kazancınızı artırın.', 'url' => '/dealer/lead-create', 'cta' => 'Yeni Yönlendirme'],
    };

    // Bonus colors
    $bonusStatus = $bonus['status'] ?? 'locked';
    $bonusColors = ['locked' => ['bg' => 'linear-gradient(135deg,#1e3a5f,#2563eb)', 'border' => '#2563eb', 'icon' => '🔒', 'text' => '#fff', 'muted' => 'rgba(255,255,255,.7)'], 'pending' => ['bg' => 'linear-gradient(135deg,#1e40af,#3b82f6)', 'border' => '#3b82f6', 'icon' => '⏳', 'text' => '#fff', 'muted' => 'rgba(255,255,255,.7)'], 'unlocked' => ['bg' => 'linear-gradient(135deg,#15803d,#16a34a)', 'border' => '#16a34a', 'icon' => '🎉', 'text' => '#fff', 'muted' => 'rgba(255,255,255,.7)']];
    $bc = $bonusColors[$bonusStatus] ?? $bonusColors['locked'];
@endphp

{{-- ── 1. Greeting ── --}}
<div class="dd-greeting">
    <h1>Merhaba, {{ $firstName }}! 👋</h1>
    <p>
        @if(!$hasLead)
            İlk yönlendirmeni yap, bonusunu kazan.
        @elseif(!$hasConverted)
            {{ $kpis['lead_total'] }} yönlendirmen var. Dönüşüm bekleniyor.
        @elseif(!$hasRevenue)
            Öğrencin dönüştü! Kazancın yolda.
        @else
            {{ $kpis['lead_total'] }} lead, {{ $kpis['converted_total'] }} dönüşüm, {{ number_format($kpis['revenue_total'], 0, ',', '.') }}€ kazanç. Süper gidiyorsun!
        @endif
    </p>
</div>

{{-- ── 2. Journey Progress ── --}}
<div class="dd-journey">
    <div class="dd-journey-title">Bayi Yolculuğun</div>
    <div class="dd-steps">
        @foreach($journeySteps as $i => $step)
            <div class="dd-step {{ $step['done'] ? 'done' : ($i === $activeIdx ? 'active' : '') }}">
                <div class="dd-step-line"></div>
                <div class="dd-step-dot">
                    @if($step['done'])
                        ✓
                    @elseif($i === $activeIdx)
                        {{ $i + 1 }}
                    @else
                        {{ $i + 1 }}
                    @endif
                </div>
                <div class="dd-step-label">{{ $step['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ── 3+4. Bonus + Hero CTA yan yana ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    {{-- Bonus --}}
    <div style="background:{{ $bc['bg'] }};border-radius:14px;padding:20px 22px;display:flex;flex-direction:column;justify-content:space-between;color:{{ $bc['text'] ?? '#fff' }};">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span style="font-size:24px;">{{ $bc['icon'] }}</span>
                <div style="font-weight:700;font-size:15px;">Hoş Geldin Bonusu</div>
            </div>
            <div style="font-size:28px;font-weight:800;margin-bottom:6px;">{{ number_format((float)($bonus['amount'] ?? 100), 2, ',', '.') }} €</div>
            <div style="font-size:12px;color:{{ $bc['muted'] ?? 'rgba(255,255,255,.7)' }};line-height:1.5;">
                @if($bonusStatus === 'locked') İlk yönlendirmeni yap → bonus beklemeye alınır
                @elseif($bonusStatus === 'pending') Öğrencin dönüşüp ödeme yapınca çekilebilir olur
                @else Tebrikler! Ödemeler sayfasından çekebilirsiniz
                @endif
            </div>
        </div>
        <div style="margin-top:12px;">
            <div style="display:flex;gap:3px;">
                <div style="flex:1;height:5px;border-radius:3px;background:{{ ($bonus['progress'] ?? 0) >= 33 ? 'rgba(255,255,255,.8)' : 'rgba(255,255,255,.2)' }};"></div>
                <div style="flex:1;height:5px;border-radius:3px;background:{{ ($bonus['progress'] ?? 0) >= 66 ? 'rgba(255,255,255,.8)' : 'rgba(255,255,255,.2)' }};"></div>
                <div style="flex:1;height:5px;border-radius:3px;background:{{ ($bonus['progress'] ?? 0) >= 100 ? 'rgba(255,255,255,.8)' : 'rgba(255,255,255,.2)' }};"></div>
            </div>
            <div style="font-size:10px;color:rgba(255,255,255,.6);margin-top:3px;text-align:right;">{{ $bonus['label'] ?? '-' }}</div>
        </div>
    </div>

    {{-- Hero CTA --}}
    <div class="dd-hero" style="margin-bottom:0;border-radius:14px;flex-direction:column;align-items:flex-start;justify-content:space-between;">
        <div>
            <div style="font-size:36px;margin-bottom:10px;">{{ $heroConfig['icon'] }}</div>
            <h2 style="font-size:17px;font-weight:700;margin:0 0 6px;color:#fff;">{{ $heroConfig['title'] }}</h2>
            <p style="font-size:12px;opacity:.85;margin:0;line-height:1.5;">{{ $heroConfig['desc'] }}</p>
        </div>
        <a href="{{ $heroConfig['url'] }}" class="dd-hero-btn" style="margin-top:14px;align-self:flex-start;">{{ $heroConfig['cta'] }} →</a>
    </div>
</div>
<style>@media(max-width:700px){.dd-greeting+.dd-journey+div{grid-template-columns:1fr !important;}}</style>

{{-- ── 5. KPI Grid (4 kart) ── --}}
<div class="dd-kpis">
    <div class="dd-kpi">
        <div class="dd-kpi-icon">👥</div>
        <div class="dd-kpi-val">{{ $kpis['lead_total'] ?? 0 }}</div>
        <div class="dd-kpi-label">Toplam Lead</div>
    </div>
    <div class="dd-kpi">
        <div class="dd-kpi-icon">🎓</div>
        <div class="dd-kpi-val">{{ $kpis['converted_total'] ?? 0 }}</div>
        <div class="dd-kpi-label">Dönüşüm</div>
    </div>
    <div class="dd-kpi">
        <div class="dd-kpi-icon">📊</div>
        <div class="dd-kpi-val">%{{ $kpis['conversion_rate'] ?? 0 }}</div>
        <div class="dd-kpi-label">Dönüşüm Oranı</div>
    </div>
    <div class="dd-kpi">
        <div class="dd-kpi-icon">💰</div>
        <div class="dd-kpi-val">{{ number_format($kpis['revenue_total'] ?? 0, 0, ',', '.') }}€</div>
        <div class="dd-kpi-label">Toplam Kazanç</div>
    </div>
</div>

{{-- ── 6+7. Yapılacaklar + Hızlı Erişim yan yana ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    {{-- Yapılacaklar --}}
    <div class="dd-checklist" style="margin-bottom:0;">
        <div style="font-size:13px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Yapılacaklar</div>
        <div class="dd-check-item">
            <div class="dd-check-icon done">✓</div>
            <div style="flex:1;">Hesap oluşturuldu</div>
        </div>
        <div class="dd-check-item">
            <div class="dd-check-icon {{ $hasLead ? 'done' : 'todo' }}">{{ $hasLead ? '✓' : '!' }}</div>
            <div style="flex:1;">İlk öğrenci yönlendirmesi</div>
            @if(!$hasLead)
                <a href="/dealer/lead-create" style="font-size:12px;color:#3b82f6;text-decoration:none;font-weight:600;">Yönlendir →</a>
            @endif
        </div>
        <div class="dd-check-item">
            <div class="dd-check-icon {{ $hasRevenue ? 'done' : ($hasLead ? 'todo' : 'lock') }}">{{ $hasRevenue ? '✓' : ($hasLead ? '!' : '🔒') }}</div>
            <div style="flex:1;">İlk kazanç elde et</div>
        </div>
        <div class="dd-check-item">
            <div class="dd-check-icon {{ $bonusOpen ? 'done' : ($hasRevenue ? 'todo' : 'lock') }}">{{ $bonusOpen ? '✓' : ($hasRevenue ? '!' : '🔒') }}</div>
            <div style="flex:1;">Bonusu çek</div>
            @if($bonusOpen)
                <a href="/dealer/payments" style="font-size:12px;color:#16a34a;text-decoration:none;font-weight:600;">Çek →</a>
            @endif
        </div>
    </div>

    {{-- Hızlı Erişim --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;padding:20px 22px;">
        <div style="font-size:13px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px;">Hızlı Erişim</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <a href="/dealer/lead-create" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;text-decoration:none;color:var(--text,#111);transition:border-color .2s;">
                <span style="font-size:20px;">➕</span>
                <span style="font-size:12px;font-weight:600;">Öğrenci Yönlendir</span>
            </a>
            <a href="/dealer/leads" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;text-decoration:none;color:var(--text,#111);transition:border-color .2s;">
                <span style="font-size:20px;">👥</span>
                <span style="font-size:12px;font-weight:600;">Yönlendirmelerim</span>
            </a>
            <a href="/dealer/payments" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;text-decoration:none;color:var(--text,#111);transition:border-color .2s;">
                <span style="font-size:20px;">💳</span>
                <span style="font-size:12px;font-weight:600;">Ödemeler</span>
            </a>
            <a href="/dealer/training" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;text-decoration:none;color:var(--text,#111);transition:border-color .2s;">
                <span style="font-size:20px;">📚</span>
                <span style="font-size:12px;font-weight:600;">Eğitim Merkezi</span>
            </a>
            <a href="/dealer/referral-links" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;text-decoration:none;color:var(--text,#111);transition:border-color .2s;grid-column:span 2;">
                <span style="font-size:20px;">🔗</span>
                <span style="font-size:12px;font-weight:600;">Referans Linkim</span>
            </a>
        </div>
    </div>
</div>
<style>@media(max-width:700px){.dd-checklist+div{grid-template-columns:1fr !important;} .dd-checklist{margin-bottom:14px !important;}}</style>

{{-- ── 8. Kazanç Özeti ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:18px;">
    <div class="dd-kpi" style="border-top:3px solid #f59e0b;">
        <div style="font-size:11px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:8px;">Bu Ay Kazanç</div>
        <div class="dd-kpi-val">{{ number_format($kpis['revenue_month'] ?? 0, 2, ',', '.') }}</div>
        <div class="dd-kpi-label">EUR</div>
    </div>
    <div class="dd-kpi" style="border-top:3px solid #3b82f6;">
        <div style="font-size:11px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:8px;">Toplam Kazanç</div>
        <div class="dd-kpi-val">{{ number_format($kpis['revenue_total'] ?? 0, 2, ',', '.') }}</div>
        <div class="dd-kpi-label">EUR tüm zamanlar</div>
    </div>
    <div class="dd-kpi" style="border-top:3px solid #16a34a;">
        <div style="font-size:11px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:8px;">Bekleyen Kazanç</div>
        <div class="dd-kpi-val" style="color:#16a34a;">{{ number_format($kpis['revenue_pending'] ?? 0, 2, ',', '.') }}</div>
        <div class="dd-kpi-label">EUR ödeme bekleniyor</div>
    </div>
</div>

{{-- ── 9. Yönlendirme Tipi Karşılaştırması + Ort. Dönüşüm Süresi ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    {{-- Referral type --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Yönlendirme Tipi Analizi</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div style="background:#fefce8;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:11px;font-weight:600;color:#854d0e;margin-bottom:4px;">Tavsiye</div>
                <div style="font-size:22px;font-weight:800;color:#854d0e;">{{ $referralAnalysis['recommendation']['count'] ?? 0 }}</div>
                <div style="font-size:11px;color:#a16207;">{{ $referralAnalysis['recommendation']['converted'] ?? 0 }} dönüşüm · %{{ $referralAnalysis['recommendation']['rate'] ?? 0 }}</div>
            </div>
            <div style="background:#f0fdf4;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:11px;font-weight:600;color:#166534;margin-bottom:4px;">Kesin</div>
                <div style="font-size:22px;font-weight:800;color:#166534;">{{ $referralAnalysis['confirmed']['count'] ?? 0 }}</div>
                <div style="font-size:11px;color:#15803d;">{{ $referralAnalysis['confirmed']['converted'] ?? 0 }} dönüşüm · %{{ $referralAnalysis['confirmed']['rate'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Ort. dönüşüm süresi + eğitim + pipeline --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Hızlı Metrikler</div>
        <div style="display:grid;gap:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--bg,#f8fafc);border-radius:6px;font-size:12px;">
                <span>⏱ Ort. Dönüşüm Süresi</span>
                <strong style="color:#3b82f6;">{{ $avgConversionDays !== null ? $avgConversionDays . ' gün' : 'Veri yok' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--bg,#f8fafc);border-radius:6px;font-size:12px;">
                <span>📚 Eğitim İlerlemesi</span>
                <strong style="color:#16a34a;">%{{ $trainingProgress }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--bg,#f8fafc);border-radius:6px;font-size:12px;">
                <span>🎓 Kayıtlı Öğrenci</span>
                <strong>{{ $kpis['student_total'] ?? 0 }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--bg,#f8fafc);border-radius:6px;font-size:12px;">
                <span>📦 Paket Seçili</span>
                <strong>{{ $guestLeads->filter(fn ($g) => filled($g->selected_package_code))->count() }}</strong>
            </div>
        </div>
    </div>
</div>

{{-- ── 10. Haftalık Lead Trendi + Son Aktiviteler ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    {{-- Haftalık lead trendi (mini bar chart) --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Haftalık Lead Trendi</div>
        @php $maxWeek = max(1, max(array_column($weeklyLeads, 'count'))); @endphp
        <div style="display:flex;align-items:flex-end;gap:6px;height:80px;">
            @foreach($weeklyLeads as $wk)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;">
                    <span style="font-size:10px;font-weight:700;color:var(--text,#111);">{{ $wk['count'] }}</span>
                    <div style="width:100%;background:#3b82f6;border-radius:3px 3px 0 0;min-height:2px;height:{{ round($wk['count'] / $maxWeek * 60) }}px;transition:height .3s;"></div>
                    <span style="font-size:9px;color:var(--muted,#94a3b8);">{{ $wk['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Son Aktiviteler --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Son Aktiviteler</div>
        @if(!empty($activityFeed) && count($activityFeed) > 0)
            @foreach($activityFeed as $act)
                <div style="display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid var(--border,#f1f5f9);font-size:12px;">
                    <span style="flex-shrink:0;">{{ $act['icon'] }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="color:var(--text,#111);">{{ $act['text'] }}</div>
                        <div style="color:var(--muted,#94a3b8);font-size:11px;">{{ $act['date'] }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div style="text-align:center;padding:20px 0;color:var(--muted,#94a3b8);font-size:13px;">Henüz aktivite yok.</div>
        @endif
    </div>
</div>

{{-- ── 11. Kanal + Başvuru Tipi + Aylık Trend ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:18px;">
    {{-- Kanal Dağılımı --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Kanal Dağılımı</div>
        @php $chColors = ['#3b82f6','#16a34a','#f59e0b','#ef4444','#8b5cf6','#ec4899']; @endphp
        @forelse($channelDistribution as $channel => $count)
            @php $ci = $loop->index; @endphp
            <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $chColors[$ci % count($chColors)] }};flex-shrink:0;"></span>
                <span style="flex:1;">{{ $channel }}</span>
                <strong>{{ $count }}</strong>
            </div>
        @empty
            <div style="text-align:center;padding:16px 0;color:var(--muted,#94a3b8);font-size:12px;">Veri yok</div>
        @endforelse
    </div>

    {{-- Başvuru Tipi --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Başvuru Tipi</div>
        @forelse($typeDistribution as $type => $count)
            @php $ci = $loop->index; @endphp
            <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $chColors[$ci % count($chColors)] }};flex-shrink:0;"></span>
                <span style="flex:1;">{{ $type }}</span>
                <strong>{{ $count }}</strong>
            </div>
        @empty
            <div style="text-align:center;padding:16px 0;color:var(--muted,#94a3b8);font-size:12px;">Veri yok</div>
        @endforelse
    </div>

    {{-- Lead Durumu --}}
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;">
        <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">Lead Durumu</div>
        @php
            $statusLabels = ['new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','converted'=>'Dönüşmüş','lost'=>'Kayıp'];
            $statusColors = ['new'=>'#3b82f6','contacted'=>'#f59e0b','qualified'=>'#8b5cf6','converted'=>'#16a34a','lost'=>'#ef4444'];
        @endphp
        @forelse($statusDistribution as $sd)
            <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $statusColors[$sd['status']] ?? '#6b7280' }};flex-shrink:0;"></span>
                <span style="flex:1;">{{ $statusLabels[$sd['status']] ?? $sd['status'] }}</span>
                <strong>{{ $sd['count'] }}</strong>
            </div>
        @empty
            <div style="text-align:center;padding:16px 0;color:var(--muted,#94a3b8);font-size:12px;">Veri yok</div>
        @endforelse
    </div>
</div>

{{-- ── 12. Referans linki ── --}}
@if(!empty($dealerLink))
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px 20px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:var(--muted,#64748b);margin-bottom:8px;">REFERANS LİNKİN</div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <code id="dd-ref-link" style="flex:1;font-size:12px;background:var(--bg,#f1f5f9);padding:8px 12px;border-radius:6px;word-break:break-all;">{{ $dealerLink }}</code>
        <button type="button" class="btn alt" style="font-size:12px;padding:6px 14px;" id="dd-copy-btn">Kopyala</button>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var btn = document.getElementById('dd-copy-btn');
    var link = document.getElementById('dd-ref-link');
    if(btn && link){
        btn.addEventListener('click', function(){
            navigator.clipboard.writeText(link.textContent.trim()).then(function(){
                btn.textContent = '✓ Kopyalandı';
                setTimeout(function(){ btn.textContent = 'Kopyala'; }, 2000);
            });
        });
    }
}());
</script>
@endpush
