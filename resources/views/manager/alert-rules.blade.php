@extends('manager.layouts.app')
@section('title', 'Alert Kuralları')
@section('page_title', 'Alert Kuralları')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Alert Kuralları</h1>
    <button class="btn" onclick="document.getElementById('newForm').style.display=document.getElementById('newForm').style.display==='none'?'':'none'">+ Yeni Kural</button>
</div>

@if(session('status'))
<div class="badge ok" style="margin-bottom:12px;display:inline-block;">{{ session('status') }}</div>
@endif

<div id="newForm" style="display:none;margin-bottom:16px;" class="card">
    <div class="card-title">Yeni Alert Kuralı</div>
    <form method="POST" action="/manager/alert-rules">
        @csrf
        <div class="grid2">
            <div class="field"><label>Kural Adı *</label><input type="text" name="name" required placeholder="Risk Kritik Uyarı"></div>
            <div class="field">
                <label>Koşul Türü *</label>
                <select name="condition_type" required>
                    @foreach($conditionLabels as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Eşik Değeri *</label><input type="number" name="threshold_value" step="0.01" required placeholder="60"></div>
            <div class="field">
                <label>Kontrol Sıklığı</label>
                <select name="check_frequency">
                    @foreach($frequencyLabels as $val => $label)
                    <option value="{{ $val }}" {{ $val === 'daily' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="grid-column:1/-1;">
                <label>Bildirim E-postaları (opsiyonel, virgülle ayır)</label>
                <input type="text" name="notify_emails" placeholder="manager@firma.com, ceo@firma.com">
            </div>
        </div>
        <button type="submit" class="btn ok">Oluştur</button>
    </form>
</div>

<div class="list">
    @forelse($rules as $rule)
    <div class="item">
        <div style="flex:1;">
            <div style="font-weight:500;">{{ $rule->name }}</div>
            <div class="u-muted" style="font-size:var(--tx-xs);">
                {{ $conditionLabels[$rule->condition_type] ?? $rule->condition_type }}
                · Eşik: {{ $rule->threshold_value }}
                · {{ $frequencyLabels[$rule->check_frequency] ?? $rule->check_frequency }}
                @if($rule->last_triggered_at) · Son tetikleme: {{ $rule->last_triggered_at->diffForHumans() }} @endif
            </div>
            @if($rule->notify_emails)
            <div class="u-muted" style="font-size:var(--tx-xs);">E-posta: {{ implode(', ', (array)$rule->notify_emails) }}</div>
            @endif
        </div>
        <span class="badge {{ $rule->is_active ? 'ok' : 'pending' }}">{{ $rule->is_active ? 'Aktif' : 'Pasif' }}</span>
        <form method="POST" action="/manager/alert-rules/{{ $rule->id }}" style="margin-left:8px;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
            @csrf @method('DELETE')
            <button class="btn warn" type="submit" style="padding:4px 8px;font-size:var(--tx-xs);">Sil</button>
        </form>
    </div>
    @empty
    <div class="item"><span class="u-muted">Henüz alert kuralı yok.</span></div>
    @endforelse
</div>

@if($rules->isEmpty())
<div class="card" style="margin-top:16px;">
    <div class="card-title">Önerilen Varsayılan Kurallar</div>
    <div class="list">
        @foreach([
            ['name' => 'Risk Kritik Uyarı', 'condition_type' => 'risk_score_above', 'threshold_value' => 60],
            ['name' => 'Gelir Hedef Altı', 'condition_type' => 'revenue_below', 'threshold_value' => 5000],
            ['name' => '5+ İnaktif Öğrenci', 'condition_type' => 'inactive_students', 'threshold_value' => 5],
            ['name' => '10+ Bekleyen Belge', 'condition_type' => 'pending_docs_above', 'threshold_value' => 10],
            ['name' => 'Süresi Geçmiş Süreç', 'condition_type' => 'overdue_outcomes', 'threshold_value' => 3],
        ] as $preset)
        <div class="item">
            <div style="flex:1;">
                <span style="font-weight:500;">{{ $preset['name'] }}</span>
                <span class="u-muted" style="font-size:var(--tx-xs);margin-left:8px;">{{ $conditionLabels[$preset['condition_type']] ?? '' }} &gt; {{ $preset['threshold_value'] }}</span>
            </div>
            <form method="POST" action="/manager/alert-rules">
                @csrf
                <input type="hidden" name="name" value="{{ $preset['name'] }}">
                <input type="hidden" name="condition_type" value="{{ $preset['condition_type'] }}">
                <input type="hidden" name="threshold_value" value="{{ $preset['threshold_value'] }}">
                <input type="hidden" name="check_frequency" value="daily">
                <button class="btn alt" type="submit" style="padding:4px 10px;font-size:var(--tx-xs);">Ekle</button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
