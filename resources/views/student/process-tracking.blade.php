@extends('student.layouts.app')

@section('title', 'Süreç Takibi')
@section('page_title', 'Süreç Takibi')

@push('head')
<style>
    .pt { display:flex; flex-direction:column; gap:12px; }

    /* ── Pipeline ── */
    .pt-pipeline { background:var(--u-card); border:1px solid var(--u-line); border-radius:16px; padding:16px 20px 20px; overflow-x:auto; }
    .pt-pipeline-title { font-size:13px; font-weight:700; color:var(--u-text); margin-bottom:18px; }
    .pt-steps { display:flex; min-width:max-content; }
    .pt-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:110px; position:relative; }
    .pt-step::before {
        content:''; position:absolute; top:17px; left:0; right:0;
        height:2px; background:var(--u-line); z-index:0;
    }
    .pt-step:first-child::before { left:50%; }
    .pt-step:last-child::before  { right:50%; }
    .pt-step.done::before { background:#22c55e; }
    .pt-dot {
        width:36px; height:36px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:13px; font-weight:800; z-index:1; position:relative;
        border:2px solid var(--u-line); background:var(--u-bg); color:#9ab2d0;
        transition:all .2s;
    }
    .pt-step.done .pt-dot { background:#22c55e; border-color:#22c55e; color:#fff; font-size:16px; }
    .pt-step-body { text-align:center; margin-top:10px; }
    .pt-step-label { font-size:11px; font-weight:700; color:#5a7290; text-transform:uppercase; letter-spacing:.03em; line-height:1.3; }
    .pt-step.done .pt-step-label { color:#15803d; }
    .pt-step-count { font-size:12px; font-weight:800; color:var(--u-text); margin-top:3px; }
    .pt-step-date  { font-size:10px; color:#9ab2d0; margin-top:2px; }

    /* ── Timeline ── */
    .pt-history { background:var(--u-card); border:1px solid var(--u-line); border-radius:16px; padding:16px 20px; }
    .pt-history-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .pt-history-head h4 { margin:0; font-size:13px; font-weight:700; color:var(--u-text); }
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
    .tl-card { border:1px solid var(--u-line); border-radius:10px; padding:12px 14px; background:var(--u-bg); }
    .tl-row.ok     .tl-card { border-color:#bbf7d0; background:#f0fdf4; }
    .tl-row.danger .tl-card { border-color:#fecaca; background:#fff5f5; }
    .tl-row.warn   .tl-card { border-color:#fde68a; background:#fffbeb; }
    .tl-meta { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-bottom:6px; }
    .tl-time { font-size:10px; color:#9ab2d0; margin-left:auto; white-space:nowrap; flex-shrink:0; }
    .tl-title { font-weight:700; font-size:13px; color:var(--u-text); margin-bottom:4px; }
    .tl-body  { font-size:13px; color:#3a5070; line-height:1.5; }
    .tl-deadline { display:inline-flex; align-items:center; gap:4px; margin-top:6px; font-size:11px; font-weight:600; color:#92400e; background:#fef3c7; border:1px solid #fcd34d; border-radius:999px; padding:2px 9px; }

    /* ── Request ── */
    .pt-request { background:var(--u-card); border:1px solid var(--u-line); border-radius:16px; padding:16px 20px; }
    .pt-request h4  { margin:0 0 2px; font-size:13px; font-weight:700; color:var(--u-text); }
    .pt-request-sub { font-size:12px; color:#5a6f8f; margin-bottom:14px; }
    .step-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
    .step-pills input[type=radio] { display:none; }
    .step-pills label { border:1px solid var(--u-line); border-radius:999px; padding:5px 12px; font-size:12px; color:var(--u-text); cursor:pointer; line-height:1; min-height:28px; display:flex; align-items:center; user-select:none; }
    .step-pills label:hover { background:var(--u-bg); border-color:var(--u-brand); }
    .step-pills input[type=radio]:checked + label { background:var(--u-brand); border-color:var(--u-brand); color:#fff; font-weight:700; }

    @media (max-width:700px) { .pt-step { min-width:80px; } }
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

{{-- ── Sıradaki Adım CTA ── --}}
@if(isset($nextExpectedStep) && $nextExpectedStep)
<div style="background:var(--u-bg);border:1.5px solid var(--u-brand);border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
    <span style="font-size:var(--tx-lg);">🔜</span>
    <div style="flex:1;min-width:160px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">Beklenen Sonraki Adım</div>
        <div style="font-size:var(--tx-xs);color:var(--u-brand);">{{ $nextExpectedStep['label'] }}</div>
    </div>
</div>
@endif

{{-- ── Pipeline ── --}}
<section class="pt-pipeline">
    <div class="pt-pipeline-title">Süreç Aşamaları</div>
    <div class="pt-steps">
        @foreach($stepLabels as $key => $label)
            @php
                $s     = $summaryByStep[$key] ?? null;
                $count = (int)($s['count'] ?? 0);
                $done  = $count > 0;
                $last  = $s['last'] ?? null;
            @endphp
            <div class="pt-step {{ $done ? 'done' : '' }}">
                <div class="pt-dot">{{ $done ? '✓' : $loop->iteration }}</div>
                <div class="pt-step-body">
                    <div class="pt-step-label">{{ $label }}</div>
                    @if($done)
                        <div class="pt-step-count">{{ $count }} kayıt</div>
                        @if($last)
                            <div class="pt-step-date">{{ \Carbon\Carbon::parse($last)->format('d.m.Y') }}</div>
                        @endif
                    @else
                        <div class="pt-step-date">Henüz yok</div>
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
    <h4>Sonraki Adımı Talep Et</h4>
    <div class="pt-request-sub">Belirli bir süreçte ilerleme talep etmek için danışmanınıza iletebilirsiniz.</div>
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
        <button class="btn primary" type="submit">Danışmana İlet</button>
    </form>
</section>

</div>
@endsection
