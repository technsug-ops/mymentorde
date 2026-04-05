@extends('dealer.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('head')
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<style>
/* ── Tier Lock Banner ── */
.drd-tier-lock {
    background: var(--u-card); border: 1.5px dashed #cbd5e1;
    border-radius: 12px; padding: 18px 20px;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    color: #64748b; margin-bottom: 14px;
}
.drd-tier-lock-icon { font-size: 24px; flex-shrink: 0; }
.drd-tier-lock-text strong { display: block; color: #374151; margin-bottom: 3px; }
.drd-tier-lock-text span { font-size: 12px; color: #94a3b8; }
/* ── Dealer Dashboard Hero ── */
.drd-hero {
    background: linear-gradient(to right, #14532d 0%, #15803d 60%, #16a34a 100%);
    border-radius: 0 0 16px 16px;
    padding: 32px 28px 24px;
    position: relative;
    overflow: hidden;
    margin: -20px -20px 20px 0;
}
.drd-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.drd-hero::after {
    content: '';
    position: absolute;
    bottom: -70px; left: 40%;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.drd-hero-top {
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    position: relative; z-index: 1; margin-bottom: 18px;
}
.drd-avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2.5px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 22px;
    flex-shrink: 0;
}
.drd-hero-info { flex: 1; min-width: 180px; }
.drd-hero-name { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.drd-hero-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.drd-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 2px 10px; font-size: 11px; color: #fff; font-weight: 600;
}
.drd-hero-badge.active { background: rgba(134,239,172,.25); border-color: rgba(134,239,172,.5); }
.drd-hero-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-left: auto; flex-shrink: 0; }
.drd-hstat { text-align: center; }
.drd-hstat-val { font-size: 18px; font-weight: 700; color: #fff; line-height: 1; margin-bottom: 3px; }
.drd-hstat-label { font-size: 11px; color: rgba(255,255,255,.65); font-weight: 500; }
.drd-hstat-sep { width: 1px; background: rgba(255,255,255,.2); align-self: stretch; }
.drd-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }
.drd-hero-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
}
.drd-hero-btn.primary { background: #fff; color: #15803d; }
.drd-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
/* KPI grid */
.drd-kpis { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 14px; }
@media (max-width: 700px) { .drd-kpis { grid-template-columns: repeat(2,1fr); } }
.drd-kpi-card {
    background: var(--u-card, #fff);
    border: 1px solid var(--u-line, #e5e7eb);
    border-radius: 14px;
    padding: 16px 18px;
}
.drd-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 6px; }
.drd-kpi-val { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; margin-bottom: 4px; }
.drd-kpi-sub { font-size: 11px; color: #9ca3af; }
.drd-rev3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 14px; }
@media (max-width: 600px) { .drd-rev3 { grid-template-columns: 1fr; } }
/* hide default top bar on dashboard */
.top { display: none !important; }
/* ── Hızlı Erişim ── */
.drd-quick-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .drd-quick-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px) { .drd-quick-grid { grid-template-columns: repeat(2, 1fr); } }
.drd-quick-link {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 8px; padding: 14px 8px 12px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; text-decoration: none;
    color: #374151; font-size: 12px; font-weight: 600;
    text-align: center; line-height: 1.3;
    transition: background .15s, border-color .15s, transform .12s;
}
.drd-quick-link:hover {
    background: #eef3fb; border-color: #93c5fd;
    color: #1d4ed8; transform: translateY(-2px);
    text-decoration: none;
}
.drd-quick-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')
@if(!$dealerCode)
    <div style="background:var(--u-card,#fff);border:1.5px solid #fca5a5;border-radius:14px;padding:20px 24px;margin-bottom:14px;">
        <h2 style="margin:0 0 8px;">Dealer Hesabı Eşleşmedi</h2>
        <div class="muted">Bu kullanıcıya <code>dealer_code</code> atanmadığı için dealer verileri gösterilemiyor.</div>
    </div>
@else
@php
    $dealerInitials = strtoupper(substr($dealer?->name ?: ($dealerCode ?: 'DR'), 0, 2));
    $isActive = (bool) ($dealer?->is_active ?? false);
@endphp

{{-- ── Hero ── --}}
<div class="drd-hero">
    <div class="drd-hero-top">
        <div class="drd-avatar">{{ $dealerInitials }}</div>
        <div class="drd-hero-info">
            <div class="drd-hero-name">{{ $dealer?->name ?: ($dealerCode ?: 'Dealer') }}</div>
            <div class="drd-hero-badges">
                <span class="drd-hero-badge">{{ $dealerCode }}</span>
                @if(isset($tierPerms))
                    <span class="drd-hero-badge" style="background:{{ $tierPerms->tierColor() }}44;border-color:{{ $tierPerms->tierColor() }}80;">
                        T{{ $tierPerms->tier() }} · {{ $tierPerms->tierLabel() }}
                    </span>
                @endif
                <span class="drd-hero-badge {{ $isActive ? 'active' : '' }}">{{ $isActive ? 'Aktif' : 'Pasif' }}</span>
            </div>
        </div>
        <div class="drd-hero-stats">
            <div class="drd-hstat">
                <div class="drd-hstat-val">{{ $kpis['lead_total'] ?? 0 }}</div>
                <div class="drd-hstat-label">Toplam Lead</div>
            </div>
            <div class="drd-hstat-sep"></div>
            <div class="drd-hstat">
                <div class="drd-hstat-val">{{ $kpis['converted_total'] ?? 0 }}</div>
                <div class="drd-hstat-label">Dönüşüm</div>
            </div>
            <div class="drd-hstat-sep"></div>
            <div class="drd-hstat">
                <div class="drd-hstat-val">{{ number_format((float)($kpis['revenue_month'] ?? 0), 0, ',', '.') }} EUR</div>
                <div class="drd-hstat-label">Bu Ay Kazanç</div>
            </div>
        </div>
    </div>
    <div class="drd-hero-actions">
        <a class="drd-hero-btn primary" href="/dealer/lead-create">Öğrenci Yönlendir</a>
        <a class="drd-hero-btn ghost" href="/dealer/leads">Yönlendirmelerim</a>
        @if(!isset($tierPerms) || $tierPerms->isStandard())
        <a class="drd-hero-btn ghost" href="/dealer/referral-links">Referans Linkleri</a>
        @endif
        <a class="drd-hero-btn ghost" href="/dealer/earnings">Kazançlarım</a>
        <a class="drd-hero-btn ghost" href="/dealer/profile">Profilim</a>
        <a class="drd-hero-btn ghost" href="/logout" style="border-color:rgba(255,255,255,.7);color:#fff;">Çıkış</a>
    </div>
</div>

{{-- ── Motivasyon Kartı ── --}}
@if(!empty($motivationCard))
<div style="background:var(--u-card);border:1.5px solid var(--u-line);border-radius:12px;padding:16px 20px;margin-bottom:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <span style="font-size:var(--tx-2xl);flex-shrink:0;">{{ $motivationCard['emoji'] }}</span>
    <div style="flex:1;min-width:200px;">
        <div style="font-weight:600;color:var(--u-text);">{{ $motivationCard['text'] }}</div>
    </div>
    <a href="{{ $motivationCard['url'] }}" class="btn ok" style="white-space:nowrap;">{{ $motivationCard['cta'] }}</a>
</div>
@endif

{{-- ── KPI Kartları ── --}}
<div class="drd-kpis">
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Toplam Lead</div>
        <div class="drd-kpi-val">{{ $kpis['lead_total'] ?? 0 }}</div>
        <div class="drd-kpi-sub">Tüm zamanlar</div>
    </div>
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Dönüşüm</div>
        <div class="drd-kpi-val">{{ $kpis['converted_total'] ?? 0 }}</div>
        <div class="drd-kpi-sub">Öğrenciye dönüşen</div>
    </div>
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Dönüşüm Oranı</div>
        <div class="drd-kpi-val">%{{ $kpis['conversion_rate'] ?? 0 }}</div>
        <div class="drd-kpi-sub">Lead → Student</div>
    </div>
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Bağlı Öğrenci</div>
        <div class="drd-kpi-val">{{ $kpis['student_total'] ?? 0 }}</div>
        <div class="drd-kpi-sub">Aktif bağlantı</div>
    </div>
</div>

{{-- ── Hızlı Erişim ── --}}
<div class="drd-quick-grid">
    <a class="drd-quick-link" href="/dealer/leads">
        <span class="drd-quick-icon" style="background:#15803d;">L</span>
        Lead'ler
    </a>
    <a class="drd-quick-link" href="/dealer/advisor">
        <span class="drd-quick-icon" style="background:#0891b2;">{{ isset($tierPerms) && $tierPerms->isBasic() ? 'S' : 'D' }}</span>
        {{ isset($tierPerms) && $tierPerms->isBasic() ? 'Destek' : 'Danışman' }}
    </a>
    <a class="drd-quick-link" href="/dealer/training">
        <span class="drd-quick-icon" style="background:#7c3aed;">E</span>
        Eğitim
    </a>
    <a class="drd-quick-link" href="/dealer/earnings">
        <span class="drd-quick-icon" style="background:#d97706;">K</span>
        Komisyonlar
    </a>
    @if(!isset($tierPerms) || $tierPerms->isStandard())
    <a class="drd-quick-link" href="/dealer/referral-links">
        <span class="drd-quick-icon" style="background:#2563eb;">U</span>
        UTM Linkler
    </a>
    @endif
    <a class="drd-quick-link" href="/dealer/payments">
        <span class="drd-quick-icon" style="background:#dc2626;">Ö</span>
        Ödemeler
    </a>
</div>

<div class="drd-rev3">
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Bu Ay Kazanç</div>
        <div class="drd-kpi-val" style="font-size:var(--tx-xl);">{{ number_format((float)($kpis['revenue_month'] ?? 0), 2, ',', '.') }}</div>
        <div class="drd-kpi-sub">EUR</div>
    </div>
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Toplam Kazanç</div>
        <div class="drd-kpi-val" style="font-size:var(--tx-xl);">{{ number_format((float)($kpis['revenue_total'] ?? 0), 2, ',', '.') }}</div>
        <div class="drd-kpi-sub">EUR tüm zamanlar</div>
    </div>
    <div class="drd-kpi-card">
        <div class="drd-kpi-label">Bekleyen Kazanç</div>
        <div class="drd-kpi-val" style="font-size:var(--tx-xl);color:#d97706;">{{ number_format((float)($kpis['revenue_pending'] ?? 0), 2, ',', '.') }}</div>
        <div class="drd-kpi-sub">EUR ödeme bekleniyor</div>
    </div>
</div>

{{-- ── Lead Pipeline Mini ── --}}
@if(!empty($leadPipeline))
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);margin-bottom:10px;">Lead Pipeline</div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        @foreach([
            ['key'=>'new',            'label'=>'Yeni',       'color'=>'#6b7280'],
            ['key'=>'contacted',      'label'=>'İletişim',   'color'=>'#2563eb'],
            ['key'=>'docs_pending',   'label'=>'Belgeler',   'color'=>'#d97706'],
            ['key'=>'contract_stage', 'label'=>'Sözleşme',   'color'=>'#7c3aed'],
            ['key'=>'converted',      'label'=>'Dönüşüm',    'color'=>'#16a34a'],
        ] as $stage)
        <div style="flex:1;min-width:70px;text-align:center;padding:10px 6px;border-radius:8px;background:{{ $stage['color'] }}14;border:1px solid {{ $stage['color'] }}40;">
            <div style="font-size:var(--tx-xl);font-weight:700;color:{{ $stage['color'] }};">{{ $leadPipeline[$stage['key']] ?? 0 }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">{{ $stage['label'] }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Next Milestone Progress ── --}}
@php $nm = $earningsHero['next_milestone'] ?? null; @endphp
@if($nm)
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);margin-bottom:8px;">Sıradaki Milestone</div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
        <span style="font-weight:600;">{{ $nm['label'] }}</span>
        <span style="font-size:var(--tx-sm);color:var(--u-muted);">%{{ $nm['progress'] }} · kalan {{ number_format($nm['remaining'],2,',','.') }} EUR</span>
    </div>
    <div style="background:var(--u-line);height:8px;border-radius:4px;">
        <div style="background:var(--u-ok);height:8px;border-radius:4px;width:{{ min(100, $nm['progress']) }}%;transition:width .4s;"></div>
    </div>
</div>
@endif

{{-- ── Tier Upgrade Banner (T1 only) ── --}}
@if(isset($tierPerms) && $tierPerms->isBasic())
<div style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1.5px solid #93c5fd;border-radius:14px;padding:18px 22px;margin-bottom:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <span style="font-size:28px;flex-shrink:0;">⬆️</span>
    <div style="flex:1;min-width:200px;">
        <div style="font-weight:700;color:#1e40af;margin-bottom:4px;">Freelance Danışman (T2) ile daha fazlasını aç</div>
        <div style="font-size:12px;color:#3b82f6;line-height:1.6;">
            T2 yetkisiyle: Öğrenci detayları · Dönüşüm grafikleri · UTM linkleri · Süreç takibi · Performans raporu
        </div>
    </div>
    <a href="/dealer/support" class="btn" style="background:#1d4ed8;color:#fff;white-space:nowrap;flex-shrink:0;">Bilgi Al →</a>
</div>
@endif

<div class="grid2" style="margin-bottom:14px;">
    {{-- ── Share Kit ── --}}
    <section class="panel">
        <h2>Paylaşım Araçları</h2>
        <div>
            <div class="muted" style="font-size:var(--tx-xs);">REFERANS KODUN</div>
            <strong style="font-size:var(--tx-lg);letter-spacing:2px;">{{ $dealerCode }}</strong>
        </div>
        <div style="margin-top:10px;">
            <div class="muted" style="font-size:var(--tx-xs);">REFERANS LİNKİ</div>
            <code id="dealer-ref-link" style="word-break:break-all;font-size:var(--tx-xs);">{{ $dealerLink ?: '-' }}</code>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
            <button type="button" class="btn" onclick="copyDealerLink()">Kopyala</button>
            <a class="btn" target="_blank" href="https://wa.me/?text={{ urlencode('MentorDE ile Almanyada egitim firsatlari! '.($dealerLink ?? '')) }}">WhatsApp</a>
            <a class="btn" target="_blank" href="mailto:?subject={{ urlencode('Almanya Eğitim Danışmanlığı') }}&body={{ urlencode('Merhaba! MentorDE başvuru linkim: '.($dealerLink ?? '')) }}">E-posta</a>
            <a class="btn alt" href="/dealer/referral-links">UTM Linkler →</a>
        </div>
        @if($dealerLink)
        <div style="margin-top:12px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode((string)$dealerLink) }}"
                 alt="QR" width="80" height="80" loading="lazy" style="border:1px solid var(--u-line);border-radius:6px;">
        </div>
        @endif
    </section>

    <section class="panel">
        <h2>Talep Tipi Dağılımı</h2>
        @php $maxType = $typeDistribution->max() ?: 1; @endphp
        <div class="list">
            @forelse($typeDistribution as $type => $count)
                <div class="item">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <strong style="font-size:var(--tx-sm);">{{ $type }}</strong>
                        <span class="muted" style="font-size:var(--tx-xs);">{{ $count }}</span>
                    </div>
                    <div style="background:var(--u-line);border-radius:3px;height:5px;">
                        <div style="background:var(--u-brand);height:100%;border-radius:3px;width:{{ round(($count/$maxType)*100) }}%;"></div>
                    </div>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Talep kaydı yok.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="grid3" style="margin-bottom:14px;">
    <section class="panel">
        <h2>Son Aktiviteler</h2>
        <div class="list">
            @forelse($recentActivity as $a)
                <div class="item">
                    <strong>{{ $a['label'] }}</strong><br>
                    <span class="muted">{{ $a['meta'] }}</span><br>
                    <span class="muted">{{ optional($a['created_at'])->format('Y-m-d H:i') }}</span>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Aktivite yok.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <h2>Aylık Kazanç (son 6)</h2>
        @if($monthlyRevenue->isEmpty())
            <div class="muted">Aylık kazanç verisi yok.</div>
        @else
            <canvas id="chart-monthly" style="max-height:170px;"></canvas>
        @endif
    </section>

    <section class="panel">
        <h2>Kanal Dağılımı</h2>
        @php $maxChan = $channelDistribution->max() ?: 1; @endphp
        <div class="list">
            @forelse($channelDistribution as $channel => $count)
                <div class="item">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <strong style="font-size:var(--tx-sm);">{{ $channel }}</strong>
                        <span class="muted" style="font-size:var(--tx-xs);">{{ $count }}</span>
                    </div>
                    <div style="background:var(--u-line);border-radius:3px;height:5px;">
                        <div style="background:var(--u-ok);height:100%;border-radius:3px;width:{{ round(($count/$maxChan)*100) }}%;"></div>
                    </div>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Kanal verisi yok.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="grid2" style="margin-bottom:14px;">
    @if(!isset($tierPerms) || $tierPerms->can('canViewStudentDetails'))
    <section class="panel">
        <h2>Son Bağlı Öğrenciler</h2>
        <div class="list">
            @forelse($students as $s)
                <div class="item">
                    <strong>{{ $s->student_id }}</strong>
                    <span class="muted"> | {{ optional($s->updated_at)->format('Y-m-d H:i') }}</span><br>
                    <span class="muted">senior:{{ $s->senior_email ?: '-' }} | branch:{{ $s->branch ?: '-' }} | risk:{{ $s->risk_level ?: '-' }} | payment:{{ $s->payment_status ?: '-' }}</span>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Bağlı öğrenci kaydı yok.</div>
            @endforelse
        </div>
    </section>
    @endif

    <section class="panel">
        <h2>Son Revenue Kayıtları</h2>
        <div class="list">
            @forelse($revenues as $r)
                <div class="item">
                    <strong>{{ $r->student_id }}</strong>
                    <span class="muted"> | {{ optional($r->updated_at)->format('Y-m-d H:i') }}</span><br>
                    <span class="muted">earned:{{ number_format((float)($r->total_earned ?? 0), 2, ',', '.') }} EUR | pending:{{ number_format((float)($r->total_pending ?? 0), 2, ',', '.') }} EUR</span>
                </div>
            @empty
                <div class="muted" style="padding:10px 0;text-align:center;">Gelir kaydı yok.</div>
            @endforelse
        </div>
    </section>
</div>

@include('dealer._partials.usage-guide', [
    'items' => [
        'KPI kartları lead, dönüşüm ve kazanç durumunu özetler.',
        'Referans Link Kutusu alanından paylaşım linkini kopyalayıp kampanyalarda kullan.',
        'Talep Tipi ve Kanal Dağılımı panelleri hangi kaynaklardan başvuru geldiğini gösterir.',
        'Detaylı işlem için menüden Yönlendirmelerim / Kazançlarım / Ödemeler ekranlarına geç.',
    ]
])
@endif
@endsection

@push('scripts')
<script>
window.__dealerDashboard = {
    dealerLink: @json($dealerLink ?? ''),
    hasMonthlyRevenue: @json(!empty($monthlyRevenue) && $monthlyRevenue->isNotEmpty()),
    monthlyRevenue: @json(!empty($monthlyRevenue) ? $monthlyRevenue->sortKeys()->all() : new stdClass()),
};
</script>
<script defer src="{{ Vite::asset('resources/js/dealer-dashboard.js') }}"></script>
@endpush
