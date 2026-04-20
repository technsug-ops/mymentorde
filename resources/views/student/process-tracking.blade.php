@extends('student.layouts.app')

@section('title', 'Süreç Takibi')
@section('page_title', 'Süreç Takibi')

@push('head')
<style>
    .pt { display:flex; flex-direction:column; gap:14px; }

    /* ══════ Hero (Option B) ══════ */
    .pt-hero {
        color:#fff; border-radius:14px; overflow:hidden; position:relative;
        box-shadow:0 6px 24px rgba(0,0,0,.1);
        background:#4c1d95 url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1400&q=80') center/cover;
    }
    .pt-hero::before {
        content:''; position:absolute; inset:0;
        background:linear-gradient(135deg, rgba(76,29,149,.92) 0%, rgba(124,58,237,.85) 100%);
    }
    .pt-hero-body { position:relative; display:flex; align-items:center; gap:22px; padding:22px 26px; }
    .pt-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
    .pt-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
    .pt-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
    .pt-hero-title { font-size:26px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.4px; }
    .pt-hero-sub { font-size:13px; opacity:.88; line-height:1.5; }
    .pt-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
    .pt-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
    .pt-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }

    @media (max-width:640px){
        .pt-hero { border-radius:12px; }
        .pt-hero-body { gap:14px; padding:18px; align-items:flex-start; }
        .pt-hero-title { font-size:20px; }
        .pt-hero-sub { font-size:12px; }
        .pt-hero-icon { font-size:36px; }
        .pt-hero-stat { padding:3px 9px; font-size:10.5px; }
    }

    /* ── Pipeline ── */
    .pt-pipeline { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:18px 20px 22px; overflow-x:auto; }
    .pt-pipeline-title {
        font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:20px;
        display:flex; align-items:center; gap:8px; position:relative;
    }
    .pt-pipeline-title::before {
        content:''; display:inline-block; width:3px; height:15px;
        background:var(--u-brand,#7c3aed); border-radius:2px;
    }
    .pt-steps { display:flex; min-width:max-content; }
    .pt-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:110px; position:relative; }
    .pt-step::before {
        content:''; position:absolute; top:19px; left:0; right:0;
        height:2px; background:var(--u-line); z-index:0;
    }
    .pt-step:first-child::before { left:50%; }
    .pt-step:last-child::before  { right:50%; }
    .pt-step.done::before { background:#22c55e; }
    .pt-step.active::before { background:linear-gradient(to right, #22c55e 50%, var(--u-line) 50%); }
    .pt-dot {
        width:40px; height:40px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:13px; font-weight:800; z-index:1; position:relative;
        border:2px solid var(--u-line); background:var(--u-card); color:#9ab2d0;
        transition:all .2s;
    }
    .pt-step.done .pt-dot {
        background:#22c55e; border-color:#22c55e; color:#fff; font-size:16px;
        box-shadow:0 4px 12px rgba(34,197,94,.28);
    }
    .pt-step.active .pt-dot {
        background:var(--u-brand,#7c3aed); border-color:var(--u-brand,#7c3aed); color:#fff;
        box-shadow:0 0 0 4px color-mix(in srgb, var(--u-brand,#7c3aed) 22%, transparent);
        animation:pt-pulse 2s ease-in-out infinite;
    }
    @keyframes pt-pulse {
        0%,100% { box-shadow:0 0 0 4px color-mix(in srgb, var(--u-brand,#7c3aed) 22%, transparent); }
        50%     { box-shadow:0 0 0 8px color-mix(in srgb, var(--u-brand,#7c3aed) 10%, transparent); }
    }
    .pt-step-body { text-align:center; margin-top:10px; }
    .pt-step-label { font-size:11px; font-weight:700; color:#5a7290; text-transform:uppercase; letter-spacing:.03em; line-height:1.3; }
    .pt-step.done .pt-step-label { color:#15803d; }
    .pt-step.active .pt-step-label { color:var(--u-brand,#7c3aed); }
    .pt-step.active::after {
        content:'SIRA BURDA'; display:block; text-align:center;
        margin-top:4px; font-size:9px; font-weight:800; letter-spacing:.5px;
        color:var(--u-brand,#7c3aed);
        padding:2px 7px; border-radius:10px;
        background:color-mix(in srgb, var(--u-brand,#7c3aed) 10%, transparent);
        width:max-content; margin-left:auto; margin-right:auto;
    }
    .pt-step-count { font-size:12px; font-weight:800; color:var(--u-text); margin-top:3px; }
    .pt-step-date  { font-size:10px; color:#9ab2d0; margin-top:2px; }

    /* ── Timeline ── */
    .pt-history { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:18px 20px; }
    .pt-history-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
    .pt-history-head h4 {
        margin:0; font-size:13px; font-weight:700; color:var(--u-text);
        display:flex; align-items:center; gap:8px;
    }
    .pt-history-head h4::before {
        content:''; display:inline-block; width:3px; height:15px;
        background:var(--u-brand,#7c3aed); border-radius:2px;
    }
    .pt-empty { text-align:center; padding:32px 0; }
    .pt-empty-icon { font-size:38px; margin-bottom:10px; }
    .pt-empty-msg { font-size:13px; color:#5a6f8f; }
    .pt-empty-sub { font-size:11px; color:#9ab2d0; margin-top:4px; }
    .tl { position:relative; padding-left:28px; }
    .tl::before { content:''; position:absolute; left:6px; top:8px; bottom:8px; width:2px; background:var(--u-line); border-radius:2px; }
    .tl-row { position:relative; margin-bottom:16px; }
    .tl-row:last-child { margin-bottom:0; }
    .tl-dot { position:absolute; left:-23px; top:6px; width:14px; height:14px; border-radius:50%; border:2px solid var(--u-line); background:var(--u-card); }
    .tl-row.ok     .tl-dot { border-color:#22c55e; background:#22c55e; }
    .tl-row.danger .tl-dot { border-color:#ef4444; background:#ef4444; }
    .tl-row.warn   .tl-dot { border-color:#f59e0b; background:#f59e0b; }
    .tl-row.info   .tl-dot { border-color:#3b82f6; background:#3b82f6; }
    .tl-card {
        border:1px solid var(--u-line); border-radius:10px;
        padding:13px 15px; background:var(--u-bg);
        transition:transform .15s, box-shadow .15s;
    }
    .tl-card:hover { transform:translateX(2px); box-shadow:0 4px 12px rgba(0,0,0,.06); }
    .tl-row.ok     .tl-card { border-color:#bbf7d0; background:#f0fdf4; }
    .tl-row.danger .tl-card { border-color:#fecaca; background:#fff5f5; }
    .tl-row.warn   .tl-card { border-color:#fde68a; background:#fffbeb; }
    .tl-row.info   .tl-card { border-color:#bfdbfe; background:#eff6ff; }
    .tl-meta {
        display:flex; gap:6px; align-items:center; flex-wrap:wrap;
        margin-bottom:8px; padding-bottom:6px;
        border-bottom:1px dashed color-mix(in srgb, currentColor 15%, transparent);
    }
    .tl-meta .badge { font-size:10px; font-weight:700; padding:2px 8px; }
    .tl-time { font-size:10.5px; color:#9ab2d0; margin-left:auto; white-space:nowrap; flex-shrink:0; font-weight:600; }
    .tl-title { font-weight:700; font-size:13.5px; color:var(--u-text); margin-bottom:4px; line-height:1.3; }
    .tl-body  { font-size:12.5px; color:#3a5070; line-height:1.55; }
    .tl-deadline { display:inline-flex; align-items:center; gap:4px; margin-top:6px; font-size:11px; font-weight:600; color:#92400e; background:#fef3c7; border:1px solid #fcd34d; border-radius:999px; padding:2px 9px; }

    /* ── Request ── */
    .pt-request { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:18px 20px; }
    .pt-request-head {
        display:flex; align-items:center; gap:10px; margin-bottom:6px;
    }
    .pt-request-head::before {
        content:''; display:inline-block; width:3px; height:15px;
        background:var(--u-brand,#7c3aed); border-radius:2px;
    }
    .pt-request h4 { margin:0; font-size:13.5px; font-weight:800; color:var(--u-text); }
    .pt-request-sub { font-size:12px; color:#5a6f8f; margin-bottom:16px; margin-left:12px; }
    .step-pills { display:flex; gap:7px; flex-wrap:wrap; margin-bottom:16px; }
    .step-pills input[type=radio] { display:none; }
    .step-pills label {
        border:1.5px solid var(--u-line); border-radius:999px;
        padding:7px 14px; font-size:12px; font-weight:600;
        color:var(--u-text); cursor:pointer;
        line-height:1; min-height:30px;
        display:flex; align-items:center;
        user-select:none; transition:all .15s;
    }
    .step-pills label:hover {
        background:color-mix(in srgb, var(--u-brand,#7c3aed) 6%, var(--u-card));
        border-color:var(--u-brand); color:var(--u-brand);
    }
    .step-pills input[type=radio]:checked + label {
        background:var(--u-brand); border-color:var(--u-brand);
        color:#fff; font-weight:700;
        box-shadow:0 2px 8px color-mix(in srgb, var(--u-brand,#7c3aed) 35%, transparent);
    }
    .pt-request-btn {
        padding:10px 22px; border:none; border-radius:22px;
        background:linear-gradient(135deg, var(--u-brand,#7c3aed), #a78bfa);
        color:#fff; font-size:13px; font-weight:700;
        cursor:pointer; display:inline-flex; align-items:center; gap:6px;
        box-shadow:0 4px 14px color-mix(in srgb, var(--u-brand,#7c3aed) 35%, transparent);
        transition:transform .15s, box-shadow .15s;
    }
    .pt-request-btn:hover {
        transform:translateY(-1px);
        box-shadow:0 6px 18px color-mix(in srgb, var(--u-brand,#7c3aed) 45%, transparent);
    }

    @media (max-width:700px) { .pt-step { min-width:80px; } }

    /* ══════ Next step CTA ══════ */
    .pt-next-cta {
        position:relative; overflow:hidden;
        background:linear-gradient(135deg, #2563eb, #0891b2);
        color:#fff; border-radius:14px; padding:18px 22px;
        display:flex; align-items:center; gap:16px;
        box-shadow:0 6px 20px rgba(37,99,235,.25);
    }
    .pt-next-cta::before {
        content:'🔜'; position:absolute; top:-10px; right:-10px;
        font-size:90px; opacity:.08; pointer-events:none;
    }
    .pt-next-icon {
        width:46px; height:46px; border-radius:12px; flex-shrink:0;
        background:rgba(255,255,255,.2);
        display:flex; align-items:center; justify-content:center;
        font-size:22px;
    }
    .pt-next-body { flex:1; min-width:0; position:relative; }
    .pt-next-label { font-size:10.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; opacity:.85; margin-bottom:3px; }
    .pt-next-title { font-size:15px; font-weight:800; line-height:1.25; letter-spacing:-.2px; }
    .pt-next-btn {
        flex-shrink:0; padding:9px 18px; border-radius:20px;
        background:#fff; color:#2563eb; font-weight:700; font-size:12.5px;
        text-decoration:none; box-shadow:0 3px 10px rgba(0,0,0,.15);
        transition:transform .15s;
        display:inline-flex; align-items:center; gap:4px;
    }
    .pt-next-btn:hover { transform:translateY(-1px); text-decoration:none; color:#2563eb; }
    @media (max-width:640px){
        .pt-next-cta { padding:14px 16px; gap:12px; flex-wrap:wrap; }
        .pt-next-icon { width:38px; height:38px; font-size:18px; }
        .pt-next-title { font-size:13.5px; }
        .pt-next-btn { width:100%; justify-content:center; padding:10px; }
    }
</style>
@endpush

@section('content')
@php
    $outcomeTypeLbl = [
        'acceptance'             => 'Kabul',
        'rejection'              => 'Red',
        'conditional_acceptance' => 'Koşullu Kabul',
        'correction_request'     => 'Düzeltme Talebi',
        'waitlist'               => 'Bekleme Listesi',
    ];
    $outcomeTypeBadge = [
        'acceptance'             => 'ok',
        'rejection'              => 'danger',
        'conditional_acceptance' => 'warn',
        'correction_request'     => 'warn',
        'waitlist'               => 'info',
    ];
    $stepLabels = [
        'application_prep'  => 'Başvuru Hazırlık',
        'uni_assist'        => 'Uni Assist',
        'visa_application'  => 'Vize Başvurusu',
        'language_course'   => 'Dil Kursu',
        'residence'         => 'İkamet',
        'official_services' => 'Resmi Hizmetler',
    ];
    $summaryByStep = collect($processSummary ?? [])->keyBy('step');
@endphp

<div class="pt">

@php
    $totalRecords    = ($outcomes ?? collect())->count();
    $completedSteps  = collect($summaryByStep ?? [])->filter(fn($s) => (int)($s['count'] ?? 0) > 0)->count();
    $totalSteps      = count($stepLabels);
    $lastDate = null;
    foreach ($summaryByStep ?? [] as $s) {
        $d = $s['last'] ?? null;
        if ($d && (!$lastDate || $d > $lastDate)) $lastDate = $d;
    }
    $lastDateFmt = $lastDate ? \Carbon\Carbon::parse($lastDate)->format('d.m.Y') : '—';
@endphp

{{-- ══════ Hero ══════ --}}
<div class="pt-hero">
    <div class="pt-hero-body">
        <div class="pt-hero-main">
            <div class="pt-hero-label"><span class="pt-hero-marker"></span>Akademik Süreç Takibi</div>
            <h1 class="pt-hero-title">Süreç Takibi</h1>
            <div class="pt-hero-sub">Başvuru, belge, vize ve kayıt — Almanya yolculuğunun her aşamasındaki ilerlemen burada.</div>
            <div class="pt-hero-stats">
                <span class="pt-hero-stat">✓ {{ $completedSteps }}/{{ $totalSteps }} adım</span>
                <span class="pt-hero-stat">📋 {{ $totalRecords }} kayıt</span>
                <span class="pt-hero-stat">📅 Son: {{ $lastDateFmt }}</span>
            </div>
        </div>
        <div class="pt-hero-icon">🎯</div>
    </div>
</div>

{{-- ── Sıradaki Adım CTA ── --}}
@if(isset($nextExpectedStep) && $nextExpectedStep)
<div class="pt-next-cta">
    <div class="pt-next-icon">🔜</div>
    <div class="pt-next-body">
        <div class="pt-next-label">Beklenen Sonraki Adım</div>
        <div class="pt-next-title">{{ $nextExpectedStep['label'] }}</div>
    </div>
    <a href="/student/messages" class="pt-next-btn">Danışmana Sor <span>→</span></a>
</div>
@endif

{{-- ── Pipeline ── --}}
@php
    // Active step = first incomplete step OR matches nextExpectedStep
    $nextStepKey = $nextExpectedStep['step'] ?? $nextExpectedStep['key'] ?? null;
    $firstIncomplete = null;
    foreach ($stepLabels as $k => $_) {
        if (!isset($summaryByStep[$k]) || (int)($summaryByStep[$k]['count'] ?? 0) === 0) {
            $firstIncomplete = $k;
            break;
        }
    }
    $activeStepKey = $nextStepKey ?: $firstIncomplete;
@endphp
<section class="pt-pipeline">
    <div class="pt-pipeline-title">Süreç Aşamaları</div>
    <div class="pt-steps">
        @foreach($stepLabels as $key => $label)
            @php
                $s     = $summaryByStep[$key] ?? null;
                $count = (int)($s['count'] ?? 0);
                $done  = $count > 0;
                $active = !$done && $key === $activeStepKey;
                $last  = $s['last'] ?? null;
                $cls   = $done ? 'done' : ($active ? 'active' : '');
            @endphp
            <div class="pt-step {{ $cls }}">
                <div class="pt-dot">{{ $done ? '✓' : $loop->iteration }}</div>
                <div class="pt-step-body">
                    <div class="pt-step-label">{{ $label }}</div>
                    @if($done)
                        <div class="pt-step-count">{{ $count }} kayıt</div>
                        @if($last)
                            <div class="pt-step-date">{{ \Carbon\Carbon::parse($last)->format('d.m.Y') }}</div>
                        @endif
                    @elseif(!$active)
                        <div class="pt-step-date">Bekleniyor</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ── Timeline ── --}}
<section class="pt-history">
    <div class="pt-history-head">
        <h4>Süreç Geçmişi</h4>
        @php $total = ($outcomes ?? collect())->count(); @endphp
        @if($total > 0)
            <span class="badge info">{{ $total }} kayıt</span>
        @endif
    </div>

    @if(($outcomes ?? collect())->isEmpty())
        <div class="pt-empty">
            <div class="pt-empty-icon">📭</div>
            <div class="pt-empty-msg">Henüz paylaşılan bir süreç kaydı yok.</div>
            <div class="pt-empty-sub">Danışmanınız ilerleme sağladığında burada görünecek.</div>
        </div>
    @else
        <div class="tl">
            @foreach(($timeline ?? $outcomes) as $item)
                @php
                    // $timeline (array) veya $outcomes (Model) desteği
                    $isArr     = is_array($item);
                    $stepLbl   = $isArr ? ($item['step_label'] ?? $item['step']) : ($stepLabels[$item->process_step] ?? $item->process_step);
                    $typeLbl   = $isArr ? ($item['outcome_label'] ?? $item['outcome']) : ($outcomeTypeLbl[$item->outcome_type] ?? ucfirst($item->outcome_type));
                    $typeBadge = $isArr ? ($item['color'] ?? '') : ($outcomeTypeBadge[$item->outcome_type] ?? '');
                    $tlIcon    = $isArr ? ($item['icon'] ?? '📌') : match($isArr ? '' : $item->outcome_type) { 'acceptance' => '✅', 'rejection' => '❌', 'conditional_acceptance' => '🔵', 'correction_request' => '🔄', 'waitlist' => '⏳', default => '📌' };
                    $tlDate    = $isArr ? ($item['date'] ?? '') : optional($item->created_at)->format('d.m.Y H:i');
                    $tlUniv    = $isArr ? ($item['university'] ?? null) : $item->university;
                    $tlProg    = $isArr ? ($item['program'] ?? null) : $item->program;
                    $tlDetail  = $isArr ? ($item['details'] ?? null) : $item->details_tr;
                    $tlDeadline= $isArr ? ($item['deadline'] ?? null) : ($item->deadline ? \Carbon\Carbon::parse($item->deadline)->format('d.m.Y') : null);
                    $tlDoc     = $isArr ? null : $item->document;
                @endphp
                <div class="tl-row {{ $typeBadge }}">
                    <div class="tl-dot"></div>
                    <div class="tl-card">
                        <div class="tl-meta">
                            <span style="font-size:var(--tx-sm);flex-shrink:0;">{{ $tlIcon }}</span>
                            <span class="badge info">{{ $stepLbl }}</span>
                            <span class="badge {{ $typeBadge }}">{{ $typeLbl }}</span>
                            <span class="tl-time">{{ $tlDate }}</span>
                        </div>
                        @if($tlUniv || $tlProg)
                            <div class="tl-title">{{ implode(' · ', array_filter([(string)($tlUniv ?? ''), (string)($tlProg ?? '')])) }}</div>
                        @endif
                        @if($tlDetail)
                            <div class="tl-body">{{ $tlDetail }}</div>
                        @endif
                        @if($tlDeadline)
                            <div><span class="tl-deadline">⏰ Son tarih: {{ $tlDeadline }}</span></div>
                        @endif
                        @if(!$isArr && $tlDoc)
                            <div style="margin-top:8px;">
                                <a class="btn ok" style="font-size:var(--tx-xs);padding:4px 12px;min-height:30px;"
                                   href="{{ route('student.registration.documents.download', $tlDoc->id) }}">
                                    📥 {{ $tlDoc->original_file_name ?: 'Belgeyi İndir' }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- ── Request ── --}}
<section class="pt-request">
    <div class="pt-request-head"><h4>Sonraki Adımı Talep Et</h4></div>
    <div class="pt-request-sub">Belirli bir süreçte ilerleme talep etmek için danışmanına ilet.</div>
    <form method="post" action="/student/workflow/request-next-step">
        @csrf
        <div class="step-pills">
            @foreach($stepLabels as $key => $label)
                <span>
                    <input type="radio" name="current_step" id="sp-{{ $key }}" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                    <label for="sp-{{ $key }}">{{ $label }}</label>
                </span>
            @endforeach
        </div>
        <button class="pt-request-btn" type="submit">Danışmana İlet <span>→</span></button>
    </form>
</section>

</div>
@endsection
