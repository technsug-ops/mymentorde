@extends('manager.layouts.app')

@section('title', 'Onboarding Takibi')
@section('page_title', 'İşe Alım — Onboarding Takibi')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div style="font-size:13px;color:var(--u-muted);">Son 90 günde sisteme eklenen çalışanların onboarding durumu</div>
    <a href="/manager/hr/recruitment" class="btn" style="font-size:11px;">← İlanlar</a>
</div>

@if($employees->isEmpty())
<section class="panel" style="padding:40px;text-align:center;">
    <div style="font-size:32px;margin-bottom:8px;">🎯</div>
    <div style="font-size:14px;color:var(--u-muted);">Son 90 günde sisteme eklenen çalışan bulunamadı.</div>
</section>
@else
<div style="display:flex;flex-direction:column;gap:14px;">
@foreach($employees as $emp)
@php
    $od       = $onboardingData[$emp->id] ?? ['tasks'=>collect(),'total'=>0,'done'=>0,'progress'=>0];
    $progress = $od['progress'];
    $barColor = $progress >= 100 ? '#16a34a' : ($progress >= 50 ? '#d97706' : '#dc2626');
@endphp
<section class="panel" style="padding:16px 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--u-text);">{{ $emp->name }}</div>
            <div style="font-size:11px;color:var(--u-muted);">{{ $emp->email }} &nbsp;·&nbsp; {{ $emp->role }} &nbsp;·&nbsp; {{ $emp->created_at->format('d.m.Y') }} tarihinde katıldı</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="text-align:center;">
                <div style="font-size:18px;font-weight:800;color:{{ $barColor }};">%{{ $progress }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $od['done'] }}/{{ $od['total'] }} görev</div>
            </div>
            @if($od['total'] === 0)
            <form method="POST" action="/manager/hr/recruitment/onboarding/{{ $emp->id }}/init">
                @csrf
                <button type="submit" class="btn ok" style="font-size:11px;padding:5px 14px;">🚀 Onboarding Başlat</button>
            </form>
            @endif
        </div>
    </div>

    @if($od['total'] > 0)
    {{-- İlerleme Barı --}}
    <div style="height:6px;background:var(--u-line);border-radius:3px;margin-bottom:14px;overflow:hidden;">
        <div style="height:100%;width:{{ $progress }}%;background:{{ $barColor }};border-radius:3px;transition:width .3s;"></div>
    </div>

    {{-- Haftalar --}}
    @foreach($od['tasks']->groupBy('week') as $week => $weekTasks)
    @php $weekDone = $weekTasks->where('is_done',true)->count(); @endphp
    <div style="margin-bottom:12px;">
        <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
            {{ $week }}. Hafta
            <span style="font-weight:400;margin-left:6px;">{{ $weekDone }}/{{ $weekTasks->count() }}</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
        @foreach($weekTasks->sortBy('sort_order') as $task)
        <label style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:7px;background:{{ $task->is_done ? '#f0fdf4' : 'var(--u-bg)' }};border:1px solid {{ $task->is_done ? '#bbf7d0' : 'var(--u-line)' }};cursor:pointer;font-size:12px;color:{{ $task->is_done ? '#166534' : 'var(--u-text)' }};">
            <input type="checkbox" {{ $task->is_done ? 'checked' : '' }}
                   data-toggle-url="/manager/hr/recruitment/onboarding-tasks/{{ $task->id }}/toggle"
                   style="width:15px;height:15px;accent-color:#16a34a;flex-shrink:0;">
            <span style="{{ $task->is_done ? 'text-decoration:line-through;opacity:.6;' : '' }}">{{ $task->title }}</span>
            @if($task->is_done && $task->completed_at)
            <span style="margin-left:auto;font-size:10px;color:#16a34a;white-space:nowrap;">✓ {{ $task->completed_at->format('d.m') }}</span>
            @endif
        </label>
        @endforeach
        </div>
    </div>
    @endforeach
    @endif
</section>
@endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('input[data-toggle-url]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var url = this.getAttribute('data-toggle-url');
        var row = this.closest('label');
        var span = row ? row.querySelector('span') : null;
        var isDone = this.checked;

        fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (row) {
                row.style.background   = data.is_done ? '#f0fdf4' : 'var(--u-bg)';
                row.style.borderColor  = data.is_done ? '#bbf7d0' : 'var(--u-line)';
                row.style.color        = data.is_done ? '#166534' : 'var(--u-text)';
            }
            if (span) span.style.textDecoration = data.is_done ? 'line-through' : '';
        })
        .catch(function(){ cb.checked = !cb.checked; });
    });
});
</script>
@endpush
