@extends('layouts.staff')

@section('title', 'İzin Taleplerim')
@section('page_title', 'İzin Taleplerim')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Bakiye --}}
@php
    $quota     = $quota ?? 14;
    $used      = $used ?? 0;
    $remaining = max(0, $quota - $used);
    $pct       = $quota > 0 ? min(100, round($used / $quota * 100)) : 0;
    $barColor  = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#d97706' : '#16a34a');
@endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:10px;">{{ $year }} Yılı Yıllık İzin Bakiyesi</div>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <div style="height:8px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:999px;"></div>
            </div>
            <div style="font-size:10px;color:var(--u-muted);margin-top:4px;">{{ $used }} / {{ $quota }} gün kullanıldı (%{{ $pct }})</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:24px;font-weight:800;color:{{ $barColor }};line-height:1;">{{ $remaining }}</div>
            <div style="font-size:11px;color:var(--u-muted);">gün kalan</div>
        </div>
    </div>
</div>

<div class="grid2" style="gap:12px;align-items:start;">

{{-- Talepler Listesi --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">İzin Geçmişim</div>
    @if($leaves->isEmpty())
    <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz izin talebiniz bulunmuyor.</div>
    @else
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:var(--u-bg);">
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tür</th>
            <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tarih</th>
            <th style="padding:7px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Gün</th>
            <th style="padding:7px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Durum</th>
            <th style="padding:7px 12px;"></th>
        </tr></thead>
        <tbody>
        @foreach($leaves as $lv)
        <tr style="border-bottom:1px solid var(--u-line);">
            <td style="padding:8px 12px;">{{ \App\Models\Hr\HrLeaveRequest::$typeLabels[$lv->leave_type] ?? $lv->leave_type }}</td>
            <td style="padding:8px 12px;white-space:nowrap;">{{ $lv->start_date->format('d.m.Y') }} – {{ $lv->end_date->format('d.m.Y') }}</td>
            <td style="padding:8px 12px;text-align:center;font-weight:700;">{{ $lv->days_count }}</td>
            <td style="padding:8px 12px;text-align:center;">
                <span class="badge {{ \App\Models\Hr\HrLeaveRequest::$statusBadge[$lv->status] ?? '' }}" style="font-size:10px;">
                    {{ \App\Models\Hr\HrLeaveRequest::$statusLabels[$lv->status] ?? $lv->status }}
                </span>
            </td>
            <td style="padding:8px 12px;">
                @if($lv->status === 'pending')
                <form method="POST" action="/hr/my/leaves/{{ $lv->id }}" onsubmit="return confirm('Talebi iptal etmek istediğinizden emin misiniz?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:10px;font-weight:600;border:1px solid #fca5a5;border-radius:6px;padding:2px 8px;background:#fef2f2;color:#dc2626;cursor:pointer;">İptal</button>
                </form>
                @elseif($lv->status === 'rejected' && $lv->rejection_note)
                <span style="font-size:10px;color:#dc2626;" title="{{ $lv->rejection_note }}">⚠ Not var</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</section>

{{-- Yeni Talep Formu --}}
<section class="panel" style="padding:14px 18px;">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">+ Yeni İzin Talebi</div>
    <form method="POST" action="/hr/my/leaves">
        @csrf
        <div style="margin-bottom:8px;">
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">İzin Türü</label>
            <select name="leave_type" required style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                @foreach(\App\Models\Hr\HrLeaveRequest::$typeLabels as $v => $l)
                <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Başlangıç</label>
                <input type="date" name="start_date" required min="{{ now()->toDateString() }}"
                       style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bitiş</label>
                <input type="date" name="end_date" required min="{{ now()->toDateString() }}"
                       style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            </div>
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Sebep (opsiyonel)</label>
            <textarea name="reason" rows="2" style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;"></textarea>
        </div>
        <button type="submit" class="btn ok" style="width:100%;padding:8px;font-size:13px;">Talep Gönder</button>
    </form>

    <div style="margin-top:12px;padding:10px;background:var(--u-bg);border-radius:8px;font-size:11px;color:var(--u-muted);line-height:1.5;">
        ℹ Talebiniz manager onayına gönderilecektir. Onaylanan izinler bakiyenize yansır.
    </div>
</section>

</div>

@endsection
