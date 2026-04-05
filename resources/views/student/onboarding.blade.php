@extends('student.layouts.app')

@section('title', 'Hoş Geldiniz')
@section('page_title', 'Hoş Geldiniz!')

@push('head')
<style>
.ob-wrap { max-width:780px; margin:0 auto; }

/* ── Hero ── */
.ob-hero {
    background: linear-gradient(to right, var(--u-brand-2), var(--u-brand));
    border-radius:12px; padding:14px 22px;
    display:flex; align-items:center; gap:14px; margin-bottom:16px;
}
.ob-hero-icon { font-size:30px; flex-shrink:0; }
.ob-hero-title { font-size:17px; font-weight:800; color:#fff; margin-bottom:2px; }
.ob-hero-sub   { font-size:11px; color:rgba(255,255,255,.8); }
.ob-hero-right { margin-left:auto; text-align:right; }
.ob-hero-pct   { font-size:22px; font-weight:800; color:#fff; line-height:1; }
.ob-hero-pct-lbl { font-size:10px; color:rgba(255,255,255,.7); margin-top:2px; }

/* ── Roadmap yolu ── */
.ob-road {
    position:relative;
    display:flex; align-items:flex-end; justify-content:space-between;
    padding:0 24px; margin-bottom:14px; min-height:100px;
}
/* dashed yol çizgisi */
.ob-road::before {
    content:'';
    position:absolute; bottom:28px; left:24px; right:24px; height:4px;
    background: repeating-linear-gradient(
        90deg,
        #cbd5e1 0px, #cbd5e1 14px,
        transparent 14px, transparent 22px
    );
    border-radius:2px;
    z-index:0;
}
/* tamamlanan kısım rengi */
.ob-road-fill {
    position:absolute; bottom:28px; left:24px; height:4px;
    background: linear-gradient(90deg,#22c55e,#16a34a);
    border-radius:2px; z-index:1;
    transition:width .5s ease;
}

/* Her adım noktası */
.ob-pin {
    position:relative; z-index:2;
    display:flex; flex-direction:column; align-items:center; gap:0;
    cursor:pointer;
}
.ob-pin-marker {
    width:44px; height:44px; border-radius:50% 50% 50% 0;
    transform:rotate(-45deg);
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 12px rgba(0,0,0,.2);
    transition:transform .2s, box-shadow .2s;
    margin-bottom:4px;
}
.ob-pin:hover .ob-pin-marker { transform:rotate(-45deg) scale(1.12); box-shadow:0 6px 18px rgba(0,0,0,.25); }
.ob-pin-num {
    transform:rotate(45deg);
    font-size:15px; font-weight:800; color:#fff; line-height:1;
}
.ob-pin-dot {
    width:6px; height:6px; border-radius:50%; background:#94a3b8; margin-bottom:2px;
}
.ob-pin-label {
    font-size:10px; font-weight:600; color:var(--u-muted,#6b7280);
    max-width:80px; text-align:center; line-height:1.3;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.ob-pin.done .ob-pin-label { color:#16a34a; }
.ob-pin.active .ob-pin-label { color:var(--u-brand); font-weight:700; }

/* pin renkleri */
.ob-pin:nth-child(1) .ob-pin-marker  { background:linear-gradient(135deg,#ef4444,#dc2626); }
.ob-pin:nth-child(2) .ob-pin-marker  { background:linear-gradient(135deg,#f97316,#ea580c); }
.ob-pin:nth-child(3) .ob-pin-marker  { background:linear-gradient(135deg,#22c55e,#16a34a); }
.ob-pin:nth-child(4) .ob-pin-marker  { background:linear-gradient(135deg,#3b82f6,#2563eb); }
.ob-pin:nth-child(5) .ob-pin-marker  { background:linear-gradient(135deg,#a855f7,#9333ea); }
.ob-pin:nth-child(6) .ob-pin-marker  { background:linear-gradient(135deg,#ec4899,#db2777); }
.ob-pin:nth-child(7) .ob-pin-marker  { background:linear-gradient(135deg,#14b8a6,#0d9488); }

/* done pin: yeşil üzeri tik */
.ob-pin.done .ob-pin-marker { background:linear-gradient(135deg,#22c55e,#16a34a) !important; }
/* pending pin: gri */
.ob-pin.pending .ob-pin-marker { background:linear-gradient(135deg,#d1d5db,#9ca3af) !important; box-shadow:none; }

/* ── Step kartları ── */
.ob-steps { display:flex; flex-direction:column; gap:6px; }
.ob-step {
    display:flex; align-items:center; gap:12px;
    background:var(--u-card,#fff);
    border:1.5px solid var(--u-line,#e5e7eb);
    border-radius:10px; padding:9px 14px;
    transition:border-color .15s, box-shadow .15s;
}
.ob-step.active-step { border-color:var(--u-brand); background:var(--u-bg); }
.ob-step.done  { border-color:#bbf7d0; background:#f0fdf4; }
.ob-step.skipped { opacity:.6; }

.ob-dot {
    flex-shrink:0; width:26px; height:26px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800; color:#fff;
}
.ob-step:nth-child(1) .ob-dot { background:linear-gradient(135deg,#ef4444,#dc2626); }
.ob-step:nth-child(2) .ob-dot { background:linear-gradient(135deg,#f97316,#ea580c); }
.ob-step:nth-child(3) .ob-dot { background:linear-gradient(135deg,#22c55e,#16a34a); }
.ob-step:nth-child(4) .ob-dot { background:linear-gradient(135deg,#3b82f6,#2563eb); }
.ob-step:nth-child(5) .ob-dot { background:linear-gradient(135deg,#a855f7,#9333ea); }
.ob-step:nth-child(6) .ob-dot { background:linear-gradient(135deg,#ec4899,#db2777); }
.ob-step:nth-child(7) .ob-dot { background:linear-gradient(135deg,#14b8a6,#0d9488); }
.ob-step.done .ob-dot  { background:linear-gradient(135deg,#22c55e,#16a34a) !important; }
.ob-step.skipped .ob-dot { background:#d1d5db !important; }

.ob-text { flex:1; min-width:0; }
.ob-label { font-size:13px; font-weight:600; color:var(--u-text,#111827); }
.ob-step.done .ob-label { text-decoration:line-through; color:var(--u-muted,#9ca3af); }
.ob-desc  { font-size:11px; color:var(--u-muted,#6b7280); margin-top:1px; }

.ob-actions { display:flex; gap:6px; flex-shrink:0; }
.ob-btn-done {
    padding:4px 12px; background:#22c55e; color:#fff;
    border:none; border-radius:7px; font-size:11px; font-weight:700;
    cursor:pointer; transition:background .12s; white-space:nowrap;
}
.ob-btn-done:hover { background:#16a34a; }
.ob-btn-skip {
    padding:4px 10px; background:var(--u-bg,#f3f4f6); color:var(--u-muted,#6b7280);
    border:1px solid var(--u-line,#e5e7eb); border-radius:7px;
    font-size:11px; cursor:pointer; white-space:nowrap;
}

.ob-footer {
    display:flex; justify-content:center; margin-top:10px; padding-top:10px;
    border-top:1px solid var(--u-line,#e5e7eb);
}
.ob-back-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 16px; border-radius:8px;
    background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb);
    color:var(--u-text,#374151); font-size:12px; font-weight:600;
    text-decoration:none; transition:border-color .12s;
}
.ob-back-btn:hover { border-color:var(--u-brand,#2563eb); }
</style>
@endpush

@section('content')
@php
    $total     = count($steps);
    $doneCount = collect($steps)->where('done', true)->count();
    $percent   = $total > 0 ? (int) round($doneCount / $total * 100) : 0;
    // roadmap fill width percentage (between pins)
    $fillPct   = $total > 1 ? round($doneCount / ($total - 1) * 100, 1) : ($doneCount > 0 ? 100 : 0);
    $fillPct   = min(100, $fillPct);
@endphp

<div class="ob-wrap">

    {{-- Hero --}}
    <div class="ob-hero">
        <div class="ob-hero-icon">🚀</div>
        <div>
            <div class="ob-hero-title">Portala Hoş Geldiniz!</div>
            <div class="ob-hero-sub">Adımları tamamlayarak başlangıcınızı yapın.</div>
        </div>
        <div class="ob-hero-right">
            <div class="ob-hero-pct" id="ob-pct-display">%{{ $percent }}</div>
            <div class="ob-hero-pct-lbl">{{ $doneCount }}/{{ $total }} tamamlandı</div>
        </div>
    </div>

    {{-- Roadmap görsel --}}
    <div class="ob-road" id="ob-road">
        <div class="ob-road-fill" id="ob-road-fill" style="width:{{ $fillPct }}%"></div>
        @foreach($steps as $i => $step)
        @php
            $pinClass = $step['done'] ? 'done' : ((!collect($steps)->take($i)->where('done',false)->where('skipped',false)->count() && !$step['skipped']) ? 'active' : 'pending');
        @endphp
        <div class="ob-pin {{ $pinClass }}" onclick="document.getElementById('ob-step-{{ $step['code'] }}').scrollIntoView({behavior:'smooth',block:'nearest'})">
            <div class="ob-pin-marker">
                <div class="ob-pin-num">{{ $step['done'] ? '✓' : ($i + 1) }}</div>
            </div>
            <div class="ob-pin-dot"></div>
            <div class="ob-pin-label">{{ Str::limit($step['label'], 14) }}</div>
        </div>
        @endforeach
    </div>

    {{-- Step listesi --}}
    <div class="ob-steps">
        @foreach($steps as $i => $step)
        @php
            $isActive = !$step['done'] && !$step['skipped'] && collect($steps)->take($i)->where('done',false)->where('skipped',false)->isEmpty();
        @endphp
        <div class="ob-step {{ $step['done'] ? 'done' : '' }} {{ $step['skipped'] ? 'skipped' : '' }} {{ $isActive ? 'active-step' : '' }}"
             id="ob-step-{{ $step['code'] }}">
            <div class="ob-dot">{{ $step['done'] ? '✓' : ($i + 1) }}</div>
            <div class="ob-text">
                <div class="ob-label">{{ $step['label'] }}</div>
                <div class="ob-desc">{{ $step['desc'] }}</div>
            </div>
            @if(!$step['done'] && !$step['skipped'])
            <div class="ob-actions">
                <button class="ob-btn-done" onclick="obComplete('{{ $step['code'] }}')">✓ Tamamlandı</button>
                <button class="ob-btn-skip"  onclick="obSkip('{{ $step['code'] }}')">Atla</button>
            </div>
            @elseif($step['skipped'])
                <span class="badge" style="font-size:var(--tx-xs);flex-shrink:0;">Atlandı</span>
            @else
                <span class="badge ok" style="font-size:var(--tx-xs);flex-shrink:0;">✓ Tamamlandı</span>
            @endif
        </div>
        @endforeach
    </div>

    <div class="ob-footer">
        <a href="/student/dashboard" class="ob-back-btn">← Dashboard'a Dön</a>
    </div>

</div>
@push('scripts')
<script>
var obTotal = {{ $total }};
var obDone  = {{ $doneCount }};

function obUpdateRoad() {
    var fillPct = obTotal > 1 ? Math.min(100, obDone / (obTotal - 1) * 100) : (obDone > 0 ? 100 : 0);
    var fill = document.getElementById('ob-road-fill');
    if (fill) fill.style.width = fillPct + '%';
    var pct = obTotal > 0 ? Math.round(obDone / obTotal * 100) : 0;
    var pctEl = document.getElementById('ob-pct-display');
    if (pctEl) pctEl.textContent = '%' + pct;
}

async function obComplete(code) {
    const res = await fetch('/student/onboarding/' + code + '/complete', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content??'','Accept':'application/json'}
    });
    if (!res.ok) return;
    const data = await res.json();
    const el = document.getElementById('ob-step-' + code);
    if (el) {
        el.classList.remove('active-step');
        el.classList.add('done');
        el.querySelector('.ob-dot').textContent = '✓';
        el.querySelector('.ob-actions')?.remove();
        el.querySelector('.ob-label').style.textDecoration = 'line-through';
        const badge = document.createElement('span');
        badge.className = 'badge ok';
        badge.style.cssText = 'font-size:10px;flex-shrink:0;';
        badge.textContent = '✓ Tamamlandı';
        el.appendChild(badge);
    }
    obDone++;
    obUpdateRoad();
    if (data.remaining === 0) {
        setTimeout(() => { window.location.href = '/student/dashboard'; }, 800);
    }
}

async function obSkip(code) {
    await fetch('/student/onboarding/' + code + '/skip', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content??'','Accept':'application/json'}
    });
    const el = document.getElementById('ob-step-' + code);
    if (el) {
        el.classList.add('skipped');
        el.querySelector('.ob-actions')?.remove();
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.style.cssText = 'font-size:10px;flex-shrink:0;';
        badge.textContent = 'Atlandı';
        el.appendChild(badge);
    }
}
</script>
@endpush
@endsection
