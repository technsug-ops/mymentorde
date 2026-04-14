@extends('dealer.layouts.app')

@section('title', 'Danışmanım')
@section('page_title', 'Danışmanım')
@section('page_subtitle', 'Bağlı seniorlar, destek talepleri ve iletişim')

@push('head')
<style>
/* KPI */
.adv-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:700px){ .adv-kpi-strip { grid-template-columns:1fr; } }

.adv-kpi {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-top:3px solid var(--border,#e2e8f0);
    border-radius:12px;
    padding:16px 18px;
}
.adv-kpi.seniors  { border-top-color:#16a34a; }
.adv-kpi.students { border-top-color:#0891b2; }
.adv-kpi.tickets  { border-top-color:#d97706; }
.adv-kpi-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:6px; }
.adv-kpi-val   { font-size:28px;font-weight:900;color:var(--text,#0f172a);line-height:1; }

/* Card shell */
.adv-card {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:12px;
    overflow:hidden;
    margin-bottom:16px;
}
.adv-card-head {
    padding:14px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:center; justify-content:space-between;
    gap:8px; flex-wrap:wrap;
}
.adv-card-head h3 { margin:0; font-size:14px; font-weight:700; }

/* Senior item */
.adv-senior-item {
    padding:16px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    transition:background .12s;
}
.adv-senior-item:last-child { border-bottom:none; }
.adv-senior-item:hover { background:var(--bg,#f8fafc); }
.adv-senior-email { font-size:14px; font-weight:700; color:var(--text,#0f172a); margin-bottom:4px; }
.adv-senior-sub   { font-size:12px; color:var(--muted,#64748b); margin-bottom:10px; }
.adv-senior-actions { display:flex; gap:6px; flex-wrap:wrap; }
.adv-btn-wa {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 14px; border-radius:8px;
    font-size:12px; font-weight:600; text-decoration:none;
    background:#25d366; color:#fff; border:none;
    transition:opacity .15s;
}
.adv-btn-wa:hover { opacity:.88; }
.adv-btn-sm {
    display:inline-flex; align-items:center;
    padding:7px 12px; border-radius:8px;
    font-size:12px; font-weight:600; text-decoration:none;
    background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0);
    color:var(--text,#0f172a); transition:border-color .15s;
}
.adv-btn-sm:hover { border-color:#16a34a; color:#16a34a; }

/* Ticket item */
.adv-ticket-item {
    padding:14px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; justify-content:space-between;
    align-items:flex-start; gap:12px; flex-wrap:wrap;
    transition:background .12s;
}
.adv-ticket-item:last-child { border-bottom:none; }
.adv-ticket-item:hover { background:var(--bg,#f8fafc); }
.adv-ticket-id      { font-size:11px; color:var(--muted,#64748b); margin-bottom:3px; }
.adv-ticket-subject { font-size:14px; font-weight:700; color:var(--text,#0f172a); margin-bottom:6px; }
.adv-ticket-chips   { display:flex; gap:4px; flex-wrap:wrap; align-items:center; }
.adv-ticket-date    { font-size:11px; color:var(--muted,#64748b); margin-left:2px; align-self:center; }

/* Badge */
.adv-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
.adv-badge.ok      { background:rgba(22,163,74,.12);  color:#15803d; }
.adv-badge.warn    { background:rgba(217,119,6,.12);   color:#b45309; }
.adv-badge.danger  { background:rgba(220,38,38,.1);    color:#b91c1c; }
.adv-badge.neutral { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }

/* New ticket form */
.adv-form-wrap { padding:20px; }
.adv-field { margin-bottom:14px; }
.adv-field label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:6px; }
.adv-field input, .adv-field select, .adv-field textarea {
    width:100%; box-sizing:border-box;
    border:1.5px solid var(--border,#e2e8f0);
    border-radius:8px; padding:10px 12px;
    font-size:13px; color:var(--text,#0f172a); background:var(--surface,#fff);
    transition:border-color .15s, box-shadow .15s;
}
.adv-field input:focus, .adv-field select:focus, .adv-field textarea:focus {
    outline:none; border-color:#16a34a;
    box-shadow:0 0 0 3px rgba(22,163,74,.12);
}
.adv-field .adv-err { font-size:12px; color:var(--c-danger,#dc2626); margin-top:4px; }

.adv-empty { padding:36px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }

/* Guide */
.adv-guide { background:var(--bg,#f1f5f9); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:16px 20px; margin-top:4px; }
.adv-guide-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px; }
.adv-guide ul { margin:0; padding-left:18px; }
.adv-guide li { font-size:13px; color:var(--muted,#64748b); margin-bottom:6px; }
</style>
@endpush

@section('content')

{{-- T1: Operasyon bilgilendirme banner --}}
@if(isset($tierPerms) && $tierPerms->isBasic())
<div style="background:#eff6ff;border:1.5px solid #93c5fd;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <span style="font-size:22px;flex-shrink:0;">🎫</span>
    <div style="flex:1;min-width:200px;">
        <strong style="color:#1e40af;display:block;margin-bottom:3px;">Destek Talebi Oluştur</strong>
        <span style="font-size:12px;color:#3b82f6;">
            Talebiniz
            @if(isset($opsAdmin))
                <strong>{{ $opsAdmin->name ?: $opsAdmin->email }}</strong> (Operasyon Admin)
            @else
                operasyon ekibine
            @endif
            iletilecektir.
        </span>
    </div>
</div>
@endif

{{-- KPI --}}
<div class="adv-kpi-strip">
    @if(!isset($tierPerms) || $tierPerms->isStandard())
    <div class="adv-kpi seniors">
        <div class="adv-kpi-label">Bağlı Eğitim Danışmanı</div>
        <div class="adv-kpi-val">{{ $seniors->count() }}</div>
    </div>
    <div class="adv-kpi students">
        <div class="adv-kpi-label">Toplam Öğrenci</div>
        <div class="adv-kpi-val">{{ (int) $seniors->sum('total_students') }}</div>
    </div>
    @endif
    <div class="adv-kpi tickets">
        <div class="adv-kpi-label">Destek Talebi</div>
        <div class="adv-kpi-val">{{ $tickets->count() }}</div>
    </div>
</div>

<div class="{{ isset($tierPerms) && $tierPerms->isBasic() ? '' : 'grid2' }}" style="align-items:start;">

{{-- Bağlı Eğitim Danışmanları — sadece T2+ --}}
@if(!isset($tierPerms) || $tierPerms->isStandard())
<div class="adv-card">
    <div class="adv-card-head">
        <h3>👨‍🏫 Bağlı Eğitim Danışmanları</h3>
        @if($seniors->isNotEmpty())
            <span class="adv-badge neutral">{{ $seniors->count() }} senior</span>
        @endif
    </div>
    @forelse($seniors as $s)
    <div class="adv-senior-item">
        <div class="adv-senior-email">{{ $s->senior_email }}</div>
        <div class="adv-senior-sub">{{ $s->total_students }} öğrenci takibinde</div>
        <div class="adv-senior-actions">
            <a class="adv-btn-wa"
               href="https://wa.me/?text={{ urlencode('Merhaba, ' . config('brand.name', 'MentorDE') . ' dealer portalından yazıyorum.') }}"
               target="_blank">
                💬 WhatsApp
            </a>
            <a class="adv-btn-sm" href="/dealer/leads?q={{ urlencode((string) $s->senior_email) }}">
                Leadlarda Ara
            </a>
            <a class="adv-btn-sm" target="_blank" href="/manager/preview/senior/{{ urlencode((string) $s->senior_email) }}">
                Preview
            </a>
        </div>
    </div>
    @empty
    <div class="adv-empty">Henüz bağlı senior görünmüyor.</div>
    @endforelse
</div>
@endif

{{-- Ticketlar --}}
<div class="adv-card">
    <div class="adv-card-head">
        <h3>🎫 Destek Talepleri</h3>
        @if($tickets->isNotEmpty())
            <span class="adv-badge neutral">{{ $tickets->count() }} ticket</span>
        @endif
    </div>
    @forelse($tickets as $t)
        @php
            $tStatusCls = match($t->status ?? '') { 'open'=>'ok','in_progress'=>'warn','resolved'=>'ok', default=>'' };
            $tStatusLbl = match($t->status ?? '') { 'open'=>'Açık','in_progress'=>'İşlemde','closed'=>'Kapalı','resolved'=>'Çözüldü',default=>($t->status??'–') };
            $tPrioCls   = match($t->priority ?? '') { 'high'=>'danger','normal'=>'neutral','low'=>'neutral',default=>'neutral' };
            $tPrioLbl   = match($t->priority ?? '') { 'low'=>'Düşük','normal'=>'Normal','high'=>'Yüksek',default=>'–' };
        @endphp
        <div class="adv-ticket-item">
            <div style="flex:1;min-width:0;">
                <div class="adv-ticket-id">#{{ $t->id }}</div>
                <div class="adv-ticket-subject">{{ $t->subject }}</div>
                <div class="adv-ticket-chips">
                    <span class="adv-badge {{ $tStatusCls }}">{{ $tStatusLbl }}</span>
                    @if($t->department)<span class="adv-badge neutral">{{ $t->department }}</span>@endif
                    <span class="adv-badge {{ $tPrioCls }}">{{ $tPrioLbl }}</span>
                    <span class="adv-ticket-date">{{ optional($t->updated_at)->format('d.m.Y') }}</span>
                </div>
            </div>
            <a class="adv-btn-sm" href="{{ route('dealer.advisor.tickets.show', $t->id) }}"
               style="flex-shrink:0;">Detay →</a>
        </div>
    @empty
        <div class="adv-empty">Henüz destek talebi yok.</div>
    @endforelse
</div>

</div>{{-- /grid2 --}}

{{-- Yeni Destek Talebi --}}
<div class="adv-card">
    <div class="adv-card-head">
        <h3>✉️ Yeni Destek Talebi</h3>
        <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">Komisyon, lead takibi veya operasyon sorularını iletin</span>
    </div>
    <form method="POST" action="{{ route('dealer.advisor.ticket.store') }}" class="adv-form-wrap">
        @csrf
        <div class="grid2" style="margin-bottom:0;">
            <div class="adv-field">
                <label>Konu *</label>
                <input name="subject" value="{{ old('subject') }}" placeholder="Örn: Komisyon sorgusu, lead takibi..." required>
                @error('subject')<div class="adv-err">{{ $message }}</div>@enderror
            </div>
            <div class="adv-field">
                <label>Öncelik</label>
                <select name="priority">
                    <option value="normal" @selected(old('priority','normal')==='normal')>Normal</option>
                    <option value="high"   @selected(old('priority')==='high')>🔴 Yüksek</option>
                    <option value="low"    @selected(old('priority')==='low')>🔵 Düşük</option>
                </select>
            </div>
        </div>
        <div class="adv-field">
            <label>Mesaj *</label>
            <textarea name="message" rows="4" placeholder="Destek talebinizi detaylı açıklayın..." required>{{ old('message') }}</textarea>
            @error('message')<div class="adv-err">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary">Talep Oluştur →</button>
    </form>
</div>

<div class="adv-guide">
    <div class="adv-guide-title">💡 Nasıl Çalışır?</div>
    <ul>
        <li>Bu ekran dealer'a bağlı öğrencilerin çalıştığı seniorları ve destek taleplerini gösterir.</li>
        <li>Yeni Destek Talebi formu ile komisyon, lead takip ve operasyon sorularını iletebilirsin.</li>
        <li>WhatsApp butonu ile senior ile anında iletişim başlatabilirsin.</li>
        <li>Ticket listesi dealer kaynaklı destek taleplerinin durumunu özetler.</li>
    </ul>
</div>

@endsection
