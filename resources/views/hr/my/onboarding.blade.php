@php
    $role = auth()->user()?->role;
    $hrLayout = in_array($role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : ($role === 'manager' ? 'manager.layouts.app' : 'layouts.staff');
@endphp
@extends($hrLayout)

@section('title', 'Onboarding')
@section('page_title', 'Onboarding Süreci')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- İlerleme Özeti --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:20px 24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
        <div>
            <div style="font-size:15px;font-weight:700;color:var(--u-text);">Genel İlerleme</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:2px;" data-progress-label>{{ $done }} / {{ $total }} görev tamamlandı</div>
        </div>
        <div style="font-size:28px;font-weight:800;color:{{ $pct >= 100 ? '#16a34a' : ($pct >= 50 ? '#d97706' : 'var(--u-brand)') }};" data-progress-pct>
            %{{ $pct }}
        </div>
    </div>
    <div style="height:10px;background:var(--u-line);border-radius:999px;overflow:hidden;">
        <div data-progress-bar style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#16a34a' : ($pct >= 50 ? '#d97706' : 'var(--u-brand)') }};border-radius:999px;transition:width .4s ease;"></div>
    </div>
    @if($pct >= 100)
    <div style="margin-top:10px;font-size:13px;color:#16a34a;font-weight:700;">🎉 Tebrikler! Onboarding sürecinizi tamamladınız.</div>
    @endif
</div>

{{-- Haftalık Görev Listeleri --}}
@php
$weekLabels = ['1' => 'Hafta 1 — İlk Gün & Kurulum', '2' => 'Hafta 2 — Gözlem & İlk Görev', '3' => 'Hafta 3 — Bağımsız Çalışma', '4' => 'Hafta 4 — Değerlendirme'];
$weekColors = ['1' => '#2563eb', '2' => '#7c3aed', '3' => '#0891b2', '4' => '#16a34a'];
@endphp

@foreach($byWeek as $week => $tasks)
@php
    $weekDone  = $tasks->where('is_done', true)->count();
    $weekTotal = $tasks->count();
    $weekPct   = $weekTotal > 0 ? (int) round($weekDone / $weekTotal * 100) : 0;
    $color     = $weekColors[$week] ?? 'var(--u-brand)';
    $label     = $weekLabels[$week] ?? "Hafta $week";
@endphp
<section class="panel" style="padding:0;margin-bottom:14px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:4px;height:32px;background:{{ $color }};border-radius:4px;flex-shrink:0;"></div>
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $label }}</div>
                <div style="font-size:11px;color:var(--u-muted);margin-top:1px;">{{ $weekDone }}/{{ $weekTotal }} tamamlandı</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:80px;height:6px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ $weekPct }}%;background:{{ $color }};border-radius:999px;"></div>
            </div>
            <span style="font-size:11px;font-weight:700;color:{{ $color }};">%{{ $weekPct }}</span>
        </div>
    </div>
    <ul style="list-style:none;margin:0;padding:0;">
    @foreach($tasks->sortBy('sort_order') as $task)
    <li style="display:flex;align-items:flex-start;gap:12px;padding:11px 18px;border-bottom:1px solid var(--u-line);{{ $loop->last ? 'border-bottom:none;' : '' }}background:{{ $task->is_done ? 'color-mix(in srgb,var(--u-card) 96%,#16a34a)' : 'var(--u-card)' }};">
        {{-- Checkbox (AJAX toggle) --}}
        <button
            type="button"
            onclick="toggleTask({{ $task->id }}, this)"
            data-done="{{ $task->is_done ? '1' : '0' }}"
            style="flex-shrink:0;margin-top:1px;width:20px;height:20px;border-radius:5px;border:2px solid {{ $task->is_done ? '#16a34a' : 'var(--u-line)' }};background:{{ $task->is_done ? '#16a34a' : 'transparent' }};cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;"
            aria-label="{{ $task->is_done ? 'Görev tamamlandı' : 'Görevi tamamla' }}"
        >
            @if($task->is_done)
            <svg width="11" height="11" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </button>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:{{ $task->is_done ? '400' : '600' }};color:{{ $task->is_done ? 'var(--u-muted)' : 'var(--u-text)' }};text-decoration:{{ $task->is_done ? 'line-through' : 'none' }};">
                {{ $task->title }}
            </div>
            @if($task->description)
            <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">{{ $task->description }}</div>
            @endif
            @if($task->is_done && $task->completed_at)
            <div style="font-size:10px;color:#16a34a;margin-top:2px;">✓ {{ $task->completed_at->format('d.m.Y H:i') }}</div>
            @endif
        </div>
    </li>
    @endforeach
    </ul>
</section>
@endforeach

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function toggleTask(id, btn) {
    const isDone = btn.dataset.done === '1';
    btn.disabled = true;

    fetch('/hr/my/onboarding/' + id + '/toggle', {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) { btn.disabled = false; return; }
        const nowDone = data.is_done;
        btn.dataset.done = nowDone ? '1' : '0';
        btn.style.borderColor   = nowDone ? '#16a34a' : 'var(--u-line)';
        btn.style.background    = nowDone ? '#16a34a' : 'transparent';
        btn.innerHTML = nowDone
            ? '<svg width="11" height="11" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '';

        const li   = btn.closest('li');
        const text = li.querySelector('div[style*="font-size:13px"]');
        if (text) {
            text.style.textDecoration = nowDone ? 'line-through' : 'none';
            text.style.color          = nowDone ? 'var(--u-muted)' : 'var(--u-text)';
            text.style.fontWeight     = nowDone ? '400' : '600';
        }
        li.style.background = nowDone
            ? 'color-mix(in srgb,var(--u-card) 96%,#16a34a)'
            : 'var(--u-card)';

        btn.disabled = false;
        updateProgress();
    })
    .catch(() => { btn.disabled = false; });
}

function updateProgress() {
    const all  = document.querySelectorAll('button[data-done]');
    const done = [...all].filter(b => b.dataset.done === '1').length;
    const pct  = all.length ? Math.round(done / all.length * 100) : 0;

    const bar = document.querySelector('[data-progress-bar]');
    if (bar) bar.style.width = pct + '%';
    const pctEl = document.querySelector('[data-progress-pct]');
    if (pctEl) pctEl.textContent = '%' + pct;
    const lblEl = document.querySelector('[data-progress-label]');
    if (lblEl) lblEl.textContent = done + ' / ' + all.length + ' görev tamamlandı';
}
</script>
@endpush
