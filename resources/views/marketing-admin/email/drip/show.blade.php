@extends('marketing-admin.layouts.app')
@section('title', $sequence->name . ' — Drip Serisi')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <a href="/mktg-admin/email/drip-sequences" class="u-muted" style="font-size:var(--tx-sm);">← Tüm Seriler</a>
        <h1 style="margin-top:4px;">{{ $sequence->name }}</h1>
    </div>
    <a href="/mktg-admin/email/drip-sequences/{{ $sequence->id }}/enrollments" class="btn alt">Kayıtlar ({{ array_sum($enrollmentCounts) }})</a>
</div>

<div class="grid3" style="margin-bottom:20px;">
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">AKTİF</div><div class="kpi">{{ $enrollmentCounts['active'] }}</div></div>
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">TAMAMLANAN</div><div class="kpi">{{ $enrollmentCounts['completed'] }}</div></div>
    <div class="card"><div class="u-muted" style="font-size:var(--tx-xs);">ABONELIK İPTAL</div><div class="kpi">{{ $enrollmentCounts['unsubscribed'] }}</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-title">Adımlar</div>
    @if($sequence->steps->isEmpty())
        <p class="u-muted">Henüz adım yok.</p>
    @else
    <div class="list">
        @foreach($sequence->steps->sortBy('step_order') as $step)
        <div class="item">
            <span class="badge info" style="min-width:28px;text-align:center;">{{ $step->step_order }}</span>
            <div style="flex:1;margin-left:8px;">
                <div>Template ID: {{ $step->template_id }}{{ $step->subject_override ? ' — '.$step->subject_override : '' }}</div>
                <div class="u-muted" style="font-size:var(--tx-xs);">{{ $step->delay_hours }} saat sonra</div>
            </div>
            <span class="badge {{ $step->is_active ? 'ok' : 'pending' }}">{{ $step->is_active ? 'Aktif' : 'Pasif' }}</span>
        </div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="/mktg-admin/email/drip-sequences/{{ $sequence->id }}/steps" style="margin-top:16px;border-top:1px solid var(--u-line);padding-top:16px;">
        @csrf
        <div class="card-title" style="font-size:var(--tx-sm);">Adım Ekle</div>
        <div class="grid3">
            <div class="field"><label>Sıra No *</label><input name="step_order" type="number" min="1" required value="{{ $sequence->steps->count() + 1 }}"></div>
            <div class="field"><label>Gecikme (saat) *</label><input name="delay_hours" type="number" min="0" required value="24"></div>
            <div class="field"><label>Template ID *</label><input name="template_id" type="number" min="1" required></div>
            <div class="field" style="grid-column:1/-1"><label>Konu (opsiyonel)</label><input name="subject_override" type="text"></div>
        </div>
        <button type="submit" class="btn ok">Adım Ekle</button>
    </form>
</div>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Drip Serisi Adımları</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li><strong>Gecikme (saat):</strong> Önceki adımdan bu adıma kadar bekleme süresi (0 = hemen)</li>
        <li><strong>Template ID:</strong> E-posta Şablonları menüsünden şablon ID'sini al</li>
        <li>Konu override boş bırakılırsa şablondaki varsayılan konu kullanılır</li>
        <li>Adımları sıralı oluştur — ilk adım (gecikme 0) tetikleyici sonrası hemen gönderilir</li>
    </ul>
</details>
@endsection
