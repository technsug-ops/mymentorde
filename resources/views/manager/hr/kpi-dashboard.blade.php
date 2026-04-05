@extends('manager.layouts.app')
@section('title', 'KPI Hedef Takibi')
@section('page_title', 'KPI Hedef Takibi')

@section('content')

{{-- Header --}}
<div style="background:linear-gradient(to right,#1e40af,#4f46e5);border-radius:14px;padding:18px 22px;margin-bottom:16px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:18px;font-weight:800;margin-bottom:3px;">📊 KPI Hedef Takibi</div>
            <div style="font-size:12px;opacity:.8;">Aylık görev, bilet ve saat hedefleri gerçekleşen karşılaştırması</div>
        </div>
        {{-- Dönem seçici --}}
        <form method="GET" action="/manager/hr/kpi" style="display:flex;align-items:center;gap:8px;">
            <select name="period" onchange="this.form.submit()"
                    style="padding:7px 12px;border-radius:8px;border:none;font-size:12px;font-weight:700;background:rgba(255,255,255,.15);color:#fff;outline:none;cursor:pointer;">
                @foreach($periods as $p)
                <option value="{{ $p }}" {{ $p === $period ? 'selected' : '' }} style="background:#1e40af;">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $p)->locale('tr')->isoFormat('MMMM YYYY') }}
                </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

{{-- Navigasyon --}}
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="/manager/hr" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">📋 Dashboard</a>
    <a href="/manager/hr/persons" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">👥 Çalışanlar</a>
    <a href="/manager/hr/leaves" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">🌴 İzinler</a>
    <a href="/manager/hr/kpi" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid #1e40af;background:#eff6ff;color:#1e40af;text-decoration:none;">📊 KPI</a>
    <a href="/manager/hr/certifications" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">🎓 Sertifikalar</a>
    <a href="/manager/hr/attendance" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">⏱ Devam</a>
</div>

@if($rows->isEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:48px;text-align:center;color:var(--u-muted);">
    <div style="font-size:32px;margin-bottom:10px;">📊</div>
    <div style="font-weight:700;font-size:14px;">Bu dönem için staff çalışan bulunamadı.</div>
    <div style="font-size:12px;margin-top:4px;">Çalışan rollerini kontrol edin.</div>
</div>
@else

{{-- Özet KPI'lar --}}
@php
    $avgScore   = $rows->avg('score');
    $onTarget   = $rows->filter(fn($r) => $r['score'] >= 80)->count();
    $atRisk     = $rows->filter(fn($r) => $r['score'] >= 50 && $r['score'] < 80)->count();
    $offTarget  = $rows->filter(fn($r) => $r['score'] < 50)->count();
    $noTarget   = $rows->filter(fn($r) => $r['target'] === null)->count();
@endphp
<div class="grid4" style="gap:10px;margin-bottom:16px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 16px;text-align:center;">
        <div style="font-size:26px;font-weight:800;color:#1e40af;">{{ $rows->count() }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Takip Edilen</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 16px;text-align:center;">
        <div style="font-size:26px;font-weight:800;color:#16a34a;">{{ $onTarget }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Hedefte (≥80%)</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 16px;text-align:center;">
        <div style="font-size:26px;font-weight:800;color:#d97706;">{{ $atRisk }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Risk Altında (50-79%)</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 16px;text-align:center;">
        <div style="font-size:26px;font-weight:800;color:#dc2626;">{{ $offTarget }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Hedefin Altında (&lt;50%)</div>
    </div>
</div>

{{-- KPI Kartları --}}
@php
if (!function_exists('kpiBar')) {
    function kpiBar(int|null $pct, string $val, int|float $target, string $unit = ''): string {
        if ($pct === null) {
            return '<span style="font-size:11px;color:var(--u-muted);">' . $val . $unit . ' <span style="opacity:.5;">(hedef yok)</span></span>';
        }
        $color = $pct >= 80 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
        return '<div style="font-size:11px;color:var(--u-text);font-weight:700;margin-bottom:3px;">'
            . $val . $unit . ' / ' . $target . $unit . ' <span style="color:' . $color . ';font-size:10px;">%' . $pct . '</span></div>'
            . '<div style="height:5px;background:var(--u-line);border-radius:999px;overflow:hidden;">'
            . '<div style="height:100%;width:' . $pct . '%;background:' . $color . ';border-radius:999px;transition:width .4s;"></div>'
            . '</div>';
    }
}
@endphp
<div style="display:flex;flex-direction:column;gap:10px;">
@foreach($rows as $i => $row)
@php
    $act    = $row['act'];
    $target = $row['target'];
    $score  = $row['score'];
    $user   = $row['user'];

    $scoreColor = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#d97706' : '#dc2626');
    $scoreBadge = $score >= 80 ? 'ok' : ($score >= 50 ? 'warn' : 'danger');

    $tTask   = $target?->target_tasks_done      ?? 0;
    $tTicket = $target?->target_tickets_resolved ?? 0;
    $tHours  = $target?->target_hours_logged     ?? 0;

    $pTask   = $tTask   > 0 ? min(100, round($act['tasks_done']       / $tTask   * 100)) : null;
    $pTicket = $tTicket > 0 ? min(100, round($act['tickets_resolved'] / $tTicket * 100)) : null;
    $pHours  = $tHours  > 0 ? min(100, round($act['hours_logged']     / $tHours  * 100)) : null;
@endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">

    {{-- Sıra + Avatar --}}
    <div style="width:28px;text-align:center;font-size:13px;font-weight:800;color:var(--u-muted);">{{ $i + 1 }}</div>
    <div style="width:38px;height:38px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">
        {{ strtoupper(substr($user->name ?: $user->email, 0, 1)) }}
    </div>

    {{-- İsim + Rol --}}
    <div style="min-width:130px;">
        <div style="font-size:13px;font-weight:800;color:var(--u-text);">{{ $user->name }}</div>
        <div style="font-size:10px;color:var(--u-muted);">{{ \App\Http\Controllers\Hr\HrPersonController::ROLE_TYPE_LABELS[$user->role] ?? $user->role }}</div>
    </div>

    {{-- Metrikler --}}
    <div style="flex:1;min-width:200px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Görevler</div>
            {!! kpiBar($pTask, (string)$act['tasks_done'], $tTask) !!}
        </div>
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Biletler</div>
            {!! kpiBar($pTicket, (string)$act['tickets_resolved'], $tTicket) !!}
        </div>
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Saat</div>
            {!! kpiBar($pHours, (string)$act['hours_logged'], $tHours, 's') !!}
        </div>
    </div>

    {{-- Skor --}}
    <div style="text-align:center;flex-shrink:0;min-width:70px;">
        <div style="font-size:28px;font-weight:900;color:{{ $scoreColor }};line-height:1;">{{ $score }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:1px;">puan</div>
        <span class="badge {{ $scoreBadge }}" style="font-size:10px;margin-top:4px;">
            {{ $score >= 80 ? 'Hedefte' : ($score >= 50 ? 'Risk' : 'Altında') }}
        </span>
    </div>

    {{-- Detay linki --}}
    <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi&period={{ $period }}"
       style="font-size:11px;font-weight:700;color:#1e40af;text-decoration:none;flex-shrink:0;">Detay →</a>

    {{-- Hedef yoksa uyarı --}}
    @if(!$target)
    <div style="width:100%;margin-top:4px;padding:6px 10px;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;font-size:11px;color:#92400e;">
        ⚠ Bu çalışan için <strong>{{ $period }}</strong> dönemi KPI hedefi tanımlanmamış.
        <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi" style="color:#92400e;font-weight:700;margin-left:4px;">Hedef Belirle →</a>
    </div>
    @endif
</div>
@endforeach
</div>

@endif

@endsection
