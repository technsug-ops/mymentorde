@extends('student.layouts.app')

@section('title', 'Yapılacaklar Listesi')
@section('page_title', 'Yapılacaklar Listesi')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.cl-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#4c1d95 url('https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=1400&q=80') center/cover; }
.cl-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(76,29,149,.92) 0%, rgba(124,58,237,.85) 100%); }
.cl-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.cl-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.cl-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.cl-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.cl-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.cl-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.cl-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.cl-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.cl-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .cl-hero-body { gap:14px; padding:18px; align-items:flex-start; } .cl-hero-title { font-size:20px; } .cl-hero-sub { font-size:12px; } .cl-hero-icon { font-size:36px; } }

.cl-header { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.cl-progress { flex:1; min-width:180px; }
.cl-progress-bar { height:10px; border-radius:999px; background:#e5e7eb; overflow:hidden; margin-top:4px; }
.cl-progress-fill { height:100%; border-radius:999px; background:#22c55e; transition:width .4s; }
.cl-kpis { display:flex; gap:10px; flex-wrap:wrap; }
.cl-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:12px; padding:10px 16px; text-align:center; min-width:80px; }
.cl-kpi-val { font-size:22px; font-weight:700; color:#111827; }
.cl-kpi-label { font-size:11px; color:#6b7280; margin-top:2px; }
.cl-list { display:flex; flex-direction:column; gap:8px; }
.cl-item { display:flex; align-items:flex-start; gap:12px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:12px; padding:12px 14px; transition:opacity .2s; }
.cl-item.done { opacity:.65; }
.cl-item.overdue { border-color:#fca5a5; background:#fff5f5; }
.cl-check { flex-shrink:0; width:22px; height:22px; border-radius:6px; border:2px solid #d1d5db; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .15s; margin-top:1px; }
.cl-check.checked { background:#22c55e; border-color:#22c55e; color:#fff; font-weight:800; font-size:13px; }
.cl-body { flex:1; min-width:0; }
.cl-label { font-size:13px; font-weight:600; color:#111827; line-height:1.35; }
.cl-item.done .cl-label { text-decoration:line-through; color:#9ca3af; }
.cl-desc { font-size:12px; color:#6b7280; margin-top:3px; line-height:1.4; }
.cl-meta { display:flex; gap:6px; flex-wrap:wrap; align-items:center; margin-top:5px; }
.cl-empty { text-align:center; padding:40px 0; color:#9ca3af; font-size:14px; }

/* Kategori + tarih rozetleri — mor tema ile uyumlu ve subtle */
.cl-item .cl-cat-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; border-radius:10px;
    background:rgba(124,58,237,.10); color:#7c3aed;
    font-size:var(--tx-xs); font-weight:600;
    border:1px solid rgba(124,58,237,.18);
}
.cl-item .cl-date-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; border-radius:10px;
    background:var(--u-bg,#f8fafc); color:var(--u-muted,#64748b);
    font-size:var(--tx-xs); font-weight:500;
    border:1px solid var(--u-line,#e5e7eb);
}
.cl-item .cl-date-badge.danger {
    background:rgba(220,38,38,.08); color:#dc2626; border-color:rgba(220,38,38,.22);
}
</style>
@endpush

@section('content')
@php
    $total   = (int) ($checklistSummary['total'] ?? 0);
    $done    = (int) ($checklistSummary['done'] ?? 0);
    $percent = (int) ($checklistSummary['percent'] ?? 0);
    $overdue = (int) ($checklistSummary['overdue'] ?? 0);
@endphp

{{-- ══════ Hero ══════ --}}
<div class="cl-hero">
    <div class="cl-hero-body">
        <div class="cl-hero-main">
            <div class="cl-hero-label"><span class="cl-hero-marker"></span>İlerleme Takibi</div>
            <h1 class="cl-hero-title">Yapılacaklar Listesi</h1>
            <div class="cl-hero-sub">Danışmanınla birlikte oluşturduğun görevler. Her tamamladığın adım, Almanya yolculuğunda bir sonraki aşamaya yaklaştırır.</div>
            <div class="cl-hero-stats">
                <span class="cl-hero-stat">✅ {{ $done }}/{{ $total }} tamam</span>
                <span class="cl-hero-stat">📊 %{{ $percent }} ilerleme</span>
                @if($overdue > 0)<span class="cl-hero-stat">⏰ {{ $overdue }} gecikmiş</span>@endif
            </div>
        </div>
        <div class="cl-hero-icon">✅</div>
    </div>
</div>

<div class="cl-header">
    <div class="cl-progress" style="flex:1;min-width:200px;">
        <div style="font-size:var(--tx-sm);font-weight:600;color:#374151;margin-bottom:4px;">
            İlerleme: {{ $done }} / {{ $total }} görev tamamlandı
        </div>
        <div class="cl-progress-bar">
            <div class="cl-progress-fill" style="width:{{ $percent }}%"></div>
        </div>
    </div>
    <div class="cl-kpis">
        <div class="cl-kpi">
            <div class="cl-kpi-val">{{ $total }}</div>
            <div class="cl-kpi-label">Toplam</div>
        </div>
        <div class="cl-kpi">
            <div class="cl-kpi-val" style="color:#22c55e;">{{ $done }}</div>
            <div class="cl-kpi-label">Tamamlanan</div>
        </div>
        @if($overdue > 0)
        <div class="cl-kpi">
            <div class="cl-kpi-val" style="color:#ef4444;">{{ $overdue }}</div>
            <div class="cl-kpi-label">Geciken</div>
        </div>
        @endif
        <div class="cl-kpi">
            <div class="cl-kpi-val" style="color:#f59e0b;">{{ $percent }}%</div>
            <div class="cl-kpi-label">Tamamlama</div>
        </div>
    </div>
</div>

<div class="cl-list">
    @forelse($checklistItems as $item)
    @php
        $isOverdue = !$item->is_done && $item->due_date && $item->due_date->lt(today());
        $catLabel  = $categories[$item->category] ?? $item->category;
    @endphp
    <div class="cl-item {{ $item->is_done ? 'done' : '' }} {{ $isOverdue ? 'overdue' : '' }}"
         id="cl-item-{{ $item->id }}">
        <div class="cl-check {{ $item->is_done ? 'checked' : '' }}"
             onclick="toggleChecklist({{ $item->id }}, this)"
             title="{{ $item->is_done ? 'Tamamlandı — tekrar açmak için tıkla' : 'Tamamlandı olarak işaretle' }}">
            {{ $item->is_done ? '✓' : '' }}
        </div>
        <div class="cl-body">
            <div class="cl-label">{{ $item->label }}</div>
            @if($item->description)
                <div class="cl-desc">{{ $item->description }}</div>
            @endif
            <div class="cl-meta">
                <span class="cl-cat-badge">{{ $catLabel }}</span>
                @if($item->due_date)
                    @if($isOverdue)
                        <span class="cl-date-badge danger">⏰ {{ $item->due_date->format('d.m.Y') }} — gecikti</span>
                    @else
                        <span class="cl-date-badge">📅 {{ $item->due_date->format('d.m.Y') }}</span>
                    @endif
                @endif
                @if($item->is_done && $item->done_at)
                    <span class="cl-date-badge">✓ {{ $item->done_at->format('d.m.Y') }}</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="cl-empty">
        <div style="font-size:32px;margin-bottom:8px;">✅</div>
        <div>Danışmanınız henüz bir görev eklemedi.</div>
        <div style="font-size:var(--tx-xs);margin-top:4px;color:#d1d5db;">Görevler burada görünecek.</div>
    </div>
    @endforelse
</div>

@push('scripts')
<script>
async function toggleChecklist(id, el) {
    const row = document.getElementById('cl-item-' + id);
    const res = await fetch('/student/checklist/' + id + '/toggle', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
    });
    if (!res.ok) return;
    const data = await res.json();
    if (data.is_done) {
        el.classList.add('checked');
        el.textContent = '✓';
        row.classList.add('done');
        row.classList.remove('overdue');
    } else {
        el.classList.remove('checked');
        el.textContent = '';
        row.classList.remove('done');
    }
}
</script>
@endpush
@endsection
