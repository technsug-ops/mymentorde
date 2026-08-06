@extends('manager.layouts.app')

@section('title', 'Süreç Bilgisi – ' . $studentName)
@section('page_title', 'Süreç Bilgisi')

@push('head')
<style>
.pi-head { display:flex; flex-wrap:wrap; gap:14px; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
.pi-name { font-size:19px; font-weight:800; color:var(--text,#0f172a); line-height:1.2; }
.pi-sub  { font-size:11px; color:var(--muted,#64748b); margin-top:2px; }
.pi-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:14px; margin-bottom:12px; }
.pi-card-title { font-size:11px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; }

.pi-steps { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; }
@media(max-width:820px){ .pi-steps { grid-template-columns:repeat(2,1fr); } }
.pi-stepbox { border:1px solid var(--border,#e2e8f0); border-top:3px solid var(--border,#e2e8f0); border-radius:8px; padding:9px 10px; }
.pi-stepbox.done { border-top-color:#1e40af; background:rgba(30,64,175,.04); }
.pi-stepbox-label { font-size:11px; font-weight:600; color:var(--text,#0f172a); line-height:1.3; }
.pi-stepbox-meta  { font-size:10px; color:var(--muted,#64748b); margin-top:4px; }

.pi-tl { list-style:none; margin:0; padding:0; }
.pi-tl li { position:relative; padding:0 0 14px 20px; border-left:2px solid var(--border,#e2e8f0); }
.pi-tl li:last-child { border-left-color:transparent; padding-bottom:0; }
.pi-tl li::before { content:''; position:absolute; left:-6px; top:3px; width:10px; height:10px; border-radius:50%; background:#1e40af; }
.pi-tl li.ok::before      { background:#16a34a; }
.pi-tl li.warn::before    { background:#d97706; }
.pi-tl li.danger::before  { background:#dc2626; }
.pi-tl-title { font-size:13px; font-weight:600; color:var(--text,#0f172a); }
.pi-tl-meta  { font-size:11px; color:var(--muted,#64748b); margin-top:2px; }
.pi-tl-body  { font-size:12px; color:var(--text,#0f172a); margin-top:5px; line-height:1.5; }

.pi-table { width:100%; border-collapse:collapse; font-size:12px; }
.pi-table thead tr { background:var(--bg,#f8fafc); }
.pi-table th { padding:6px 9px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pi-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.pi-table td { padding:7px 9px; }

.pi-pending { background:rgba(217,119,6,.06); border:1px solid rgba(217,119,6,.25); border-radius:8px; padding:9px 12px; font-size:12px; color:var(--text,#0f172a); margin-top:10px; line-height:1.5; }
.pi-note { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.5; }
.pi-empty { padding:16px 4px; color:var(--muted,#64748b); font-size:12px; }
.pi-back { font-size:12px; color:var(--muted,#64748b); text-decoration:none; }
</style>
@endpush

@section('content')

<a href="/manager/process-info" class="pi-back">← Süreç listesi</a>

<div class="pi-head" style="margin-top:10px;">
    <div>
        <div class="pi-name">{{ $studentName }}</div>
        <div class="pi-sub">{{ $studentId }}</div>
    </div>
</div>

<div class="pi-note">
    Süreci <strong>operasyonu yürüten firma</strong> ilerletir; bu sayfa bilgi amaçlıdır.
    Bir sorunuz olursa öğrenciye atanmış danışmanla <a href="/im">Mesajlar</a> üzerinden görüşebilirsiniz.
</div>

{{-- Atanan danışman: üst firmanın elemanı, partner atayamaz ama kiminle
     konuşacağını bilmek zorunda. --}}
<div class="pi-card">
    <div class="pi-card-title">Atanan Danışman</div>
    @if($advisor)
        <div style="font-size:14px;font-weight:600;">{{ $advisor->name ?: $advisor->email }}</div>
        <div class="pi-sub">
            {{ $advisor->email }}@if($advisor->phone) · {{ $advisor->phone }}@endif
        </div>
    @else
        <div class="pi-empty">Bu öğrenciye henüz danışman atanmadı. Atamayı operasyonu yürüten firma yapar.</div>
    @endif
</div>

{{-- 6 ana adım --}}
<div class="pi-card">
    <div class="pi-card-title">Süreç Adımları</div>
    <div class="pi-steps">
        @foreach($progress as $p)
            <div class="pi-stepbox {{ $p['count'] > 0 ? 'done' : '' }}">
                <div class="pi-stepbox-label">{{ $p['label'] }}</div>
                <div class="pi-stepbox-meta">
                    @if($p['count'] > 0)
                        {{ $p['count'] }} kayıt · {{ $p['last']?->format('d.m.Y') }}
                    @else
                        Başlamadı
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($pendingCount > 0)
        {{-- İçerik değil, yalnızca sayı: partner işin sürdüğünü bilir, henüz
             paylaşılmamış kaydın içeriğini görmez. --}}
        <div class="pi-pending">
            Operasyon tarafında paylaşıma hazırlanan <strong>{{ $pendingCount }}</strong> kayıt var.
            Tamamlandığında bu sayfada görünecek.
        </div>
    @endif
</div>

{{-- Zaman çizelgesi --}}
<div class="pi-card">
    <div class="pi-card-title">Gelişmeler</div>
    @if($outcomes->isEmpty())
        <div class="pi-empty">Henüz paylaşılmış bir gelişme yok.</div>
    @else
        <ul class="pi-tl">
            @foreach($outcomes as $o)
                @php
                    $tone = match($o->outcome_type) {
                        'acceptance'             => 'ok',
                        'conditional_acceptance' => '',
                        'rejection'              => 'danger',
                        'correction_request', 'waitlist' => 'warn',
                        default                  => '',
                    };
                    $outcomeLabel = match($o->outcome_type) {
                        'acceptance'             => 'Kabul',
                        'conditional_acceptance' => 'Şartlı Kabul',
                        'rejection'              => 'Red',
                        'correction_request'     => 'Düzeltme Talebi',
                        'waitlist'               => 'Bekleme Listesi',
                        default                  => $o->outcome_type,
                    };
                @endphp
                <li class="{{ $tone }}">
                    <div class="pi-tl-title">
                        {{ $steps[$o->process_step] ?? $o->process_step }} — {{ $outcomeLabel }}
                    </div>
                    <div class="pi-tl-meta">
                        {{ $o->created_at?->format('d.m.Y H:i') }}
                        @if($o->university) · {{ $o->university }} @endif
                        @if($o->program) · {{ $o->program }} @endif
                        @if($o->deadline) · Son tarih: {{ $o->deadline->format('d.m.Y') }} @endif
                    </div>
                    @if($o->details_tr)
                        <div class="pi-tl-body">{{ $o->details_tr }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- Üniversite başvuruları --}}
<div class="pi-card">
    <div class="pi-card-title">Üniversite Başvuruları</div>
    @if($uniApplications->isEmpty())
        <div class="pi-empty">Paylaşılmış başvuru kaydı yok.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pi-table">
                <thead>
                    <tr>
                        <th>Üniversite</th><th>Bölüm</th><th>Dönem</th>
                        <th>Durum</th><th>Son Tarih</th><th>Sonuç</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($uniApplications as $ua)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $ua->university_name ?: '—' }}</div>
                                @if($ua->city)<div class="pi-sub">{{ $ua->city }}</div>@endif
                            </td>
                            <td>{{ $ua->department_name ?: '—' }}</td>
                            <td>{{ $ua->semester ?: '—' }}</td>
                            <td>{{ $ua->status ?: '—' }}</td>
                            <td>{{ $ua->deadline?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $ua->result_at?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Vize --}}
<div class="pi-card">
    <div class="pi-card-title">Vize</div>
    @if($visa)
        <div style="display:flex;flex-wrap:wrap;gap:22px;font-size:12px;">
            <div><div class="pi-sub">Tür</div>{{ $visa->visa_type ?: '—' }}</div>
            <div><div class="pi-sub">Durum</div>{{ $visa->status ?: '—' }}</div>
            <div><div class="pi-sub">Konsolosluk</div>{{ $visa->consulate_city ?: '—' }}</div>
            <div><div class="pi-sub">Randevu</div>{{ $visa->appointment_date?->format('d.m.Y') ?? '—' }}</div>
            <div><div class="pi-sub">Karar</div>{{ $visa->decision_date?->format('d.m.Y') ?? '—' }}</div>
        </div>
    @else
        <div class="pi-empty">Paylaşılmış vize kaydı yok.</div>
    @endif
</div>

{{-- Kurum belgeleri --}}
<div class="pi-card">
    <div class="pi-card-title">Kurum Belgeleri</div>
    @if($institutionDocs->isEmpty())
        <div class="pi-empty">Paylaşılmış kurum belgesi yok.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pi-table">
                <thead>
                    <tr><th>Belge</th><th>Kurum</th><th>Durum</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                    @foreach($institutionDocs as $d)
                        <tr>
                            <td>{{ $d->document_type_label ?: '—' }}</td>
                            <td>{{ $d->institution_name ?: '—' }}</td>
                            <td>{{ $d->status ?: '—' }}</td>
                            <td>{{ $d->received_date ? \Illuminate\Support\Carbon::parse($d->received_date)->format('d.m.Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
