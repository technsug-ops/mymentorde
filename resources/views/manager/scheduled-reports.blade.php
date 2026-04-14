@extends('manager.layouts.app')
@section('title', 'Zamanlanmış Raporlar')
@section('page_title', 'Zamanlanmış Raporlar')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Zamanlanmış Raporlar</h1>
    <button class="btn" onclick="document.getElementById('newForm').style.display=document.getElementById('newForm').style.display==='none'?'':'none'">+ Yeni Rapor</button>
</div>

@if(session('status'))
<div class="badge ok" style="margin-bottom:12px;display:inline-block;">{{ session('status') }}</div>
@endif

<div id="newForm" style="display:none;margin-bottom:16px;" class="card">
    <div class="card-title">Yeni Zamanlanmış Rapor</div>
    <form method="POST" action="/manager/scheduled-reports">
        @csrf
        <div class="grid2">
            <div class="field">
                <label>Rapor Türü *</label>
                <select name="report_type" required>
                    @foreach($reportTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Sıklık *</label>
                <select name="frequency" required onchange="document.getElementById('weeklyDay').style.display=this.value==='weekly'?'':'none';document.getElementById('monthlyDay').style.display=this.value==='monthly'?'':'none'">
                    @foreach($frequencies as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" id="weeklyDay">
                <label>Haftanın Günü (1=Pzt, 7=Pzr)</label>
                <input type="number" name="day_of_week" min="1" max="7" value="1">
            </div>
            <div class="field" id="monthlyDay" style="display:none;">
                <label>Ayın Günü (1-28)</label>
                <input type="number" name="day_of_month" min="1" max="28" value="1">
            </div>
            <div class="field" style="grid-column:1/-1;">
                <label>Gönderilecek E-postalar * (virgülle ayır)</label>
                <input type="text" name="send_to" placeholder="manager@firma.com, ceo@firma.com" required>
            </div>
            <div class="field">
                <label>Eğitim Danışmanı Filtresi (opsiyonel)</label>
                <input type="email" name="senior_filter" placeholder="senior@firma.com">
            </div>
        </div>
        <button type="submit" class="btn ok">Oluştur</button>
    </form>
</div>

<div class="list">
    @forelse($reports as $report)
    <div class="item">
        <div style="flex:1;">
            <div style="font-weight:500;">{{ \App\Models\ManagerScheduledReport::REPORT_TYPE_LABELS[$report->report_type] ?? $report->report_type }}</div>
            <div class="u-muted" style="font-size:var(--tx-xs);">
                {{ \App\Models\ManagerScheduledReport::FREQUENCY_LABELS[$report->frequency] ?? $report->frequency }}
                · Gönderilecek: {{ implode(', ', (array)$report->send_to) }}
                @if($report->senior_filter) · Eğitim Danışmanı: {{ $report->senior_filter }} @endif
                @if($report->last_sent_at) · Son gönderim: {{ $report->last_sent_at->diffForHumans() }} @else · Hiç gönderilmedi @endif
            </div>
        </div>
        <span class="badge {{ $report->is_active ? 'ok' : 'pending' }}">{{ $report->is_active ? 'Aktif' : 'Pasif' }}</span>
        <form method="POST" action="/manager/scheduled-reports/{{ $report->id }}/toggle" style="margin-left:8px;">
            @csrf
            <button class="btn alt" type="submit" style="padding:4px 8px;font-size:var(--tx-xs);">{{ $report->is_active ? 'Duraklat' : 'Aktif Et' }}</button>
        </form>
        <form method="POST" action="/manager/scheduled-reports/{{ $report->id }}" style="margin-left:4px;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
            @csrf @method('DELETE')
            <button class="btn warn" type="submit" style="padding:4px 8px;font-size:var(--tx-xs);">Sil</button>
        </form>
    </div>
    @empty
    <div class="item"><span class="u-muted">Henüz zamanlanmış rapor yok.</span></div>
    @endforelse
</div>
{{ $reports->links('partials.pagination') }}
@endsection
