@extends('senior.layouts.app')
@section('title','Sözleşme Yönetimi')
@section('page_title','Sözleşme Yönetimi')

@section('content')
@php
$statusLabels = [
    'all'              => 'Tümü',
    'requested'        => 'Sözleşme Bekleniyor',
    'signed_uploaded'  => 'İmza Yüklendi',
    'approved'         => 'Onaylandı',
    'rejected'         => 'Reddedildi',
    'cancelled'        => 'İptal Edildi',
    'reopen_requested' => 'Yeniden Değerlendirme',
];
$statusBadge = [
    'requested'        => 'warn',
    'signed_uploaded'  => 'info',
    'approved'         => 'ok',
    'rejected'         => 'danger',
    'cancelled'        => 'danger',
    'reopen_requested' => 'info',
];
$curStatus = $filters['status'] ?? 'all';
$curType   = $filters['type']   ?? 'all';
$curQ      = $filters['q']      ?? '';
$counts    = $counts ?? [];
@endphp

{{-- Gradient Header + Status Chips --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">📋 Sözleşme Yönetimi</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Guest ve öğrenci sözleşmeleri</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($statusLabels as $val => $label)
        @php
            $cnt    = $counts[$val] ?? 0;
            $active = $curStatus === $val;
            $url    = url('/senior/contracts').'?status='.$val
                      .($curQ ? '&q='.urlencode($curQ) : '')
                      .($curType !== 'all' ? '&type='.$curType : '');
        @endphp
        <a href="{{ $url }}" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;transition:all .15s;
            background:{{ $active ? 'rgba(255,255,255,.3)' : 'rgba(255,255,255,.12)' }};
            color:#fff;
            border:1.5px solid {{ $active ? 'rgba(255,255,255,.7)' : 'rgba(255,255,255,.2)' }};">
            {{ $label }}
            <span style="background:rgba(255,255,255,.22);border-radius:999px;padding:1px 8px;font-size:var(--tx-xs);">{{ $cnt }}</span>
        </a>
        @endforeach
    </div>
</div>

{{-- Filter Bar --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <form method="GET" action="{{ url('/senior/contracts') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <input type="hidden" name="status" value="{{ $curStatus }}">
        <div style="flex:2;min-width:200px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Ad / E-posta / ID</div>
            <input type="text" name="q" value="{{ $curQ }}" placeholder="Ara…"
                   style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
        </div>
        <div style="flex:1;min-width:150px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Kayıt Tipi</div>
            <select name="type" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                <option value="all"     @selected($curType === 'all')>Tümü</option>
                <option value="guest"   @selected($curType === 'guest')>Yalnızca Guest</option>
                <option value="student" @selected($curType === 'student')>Yalnızca Öğrenci</option>
            </select>
        </div>
        <div style="flex:1;min-width:150px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Durum</div>
            <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                @foreach($statusLabels as $val => $label)
                    <option value="{{ $val }}" @selected($curStatus === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
            <a href="{{ url('/senior/contracts') }}" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Temizle</a>
        </div>
    </form>
</div>

{{-- Results --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-weight:700;font-size:var(--tx-base);">Sözleşmeler</span>
        <span class="badge info">{{ $contracts->count() }} kayıt</span>
        @if($curStatus !== 'all')
            <span class="badge {{ $statusBadge[$curStatus] ?? '' }}">{{ $statusLabels[$curStatus] ?? $curStatus }}</span>
        @endif
        @if($curQ !== '')
            <span style="font-size:var(--tx-xs);color:var(--u-muted);">· "{{ $curQ }}" araması</span>
        @endif
    </div>

    @forelse($contracts as $row)
    @php
        $extras   = collect(is_array($row->selected_extra_services) ? $row->selected_extra_services : [])
            ->map(fn($x) => trim((string)($x['title'] ?? '')))->filter()->values();
        $cs       = $row->contract_status ?? '';
        $bCls     = $statusBadge[$cs] ?? '';
        $isGuest  = !$row->converted_student_id;
        $fullName = trim(($row->first_name ?? '').' '.($row->last_name ?? ''));
    @endphp
    <div style="padding:16px 18px;border-bottom:1px solid var(--u-line);transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                {{-- Name + type badge + email --}}
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
                    <span style="font-weight:800;font-size:var(--tx-base);color:var(--u-text);">{{ $fullName ?: ($row->email ?? '—') }}</span>
                    @if($isGuest)
                        <span class="badge info" style="font-size:var(--tx-xs);">Guest</span>
                    @else
                        <span style="font-size:var(--tx-xs);background:#eef4fb;color:#2a567a;border:1px solid #c0d5ee;border-radius:999px;padding:1px 8px;font-weight:700;">Öğrenci</span>
                    @endif
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $row->email ?? '' }}</span>
                </div>

                {{-- Meta row --}}
                <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
                    @if($row->converted_student_id)<span>ID: {{ $row->converted_student_id }}</span>@endif
                    <span>Talep: {{ optional($row->contract_requested_at)->format('d.m.Y H:i') ?? '—' }}</span>
                    @if($row->contract_signed_file_path)
                        <span style="color:#16a34a;font-weight:600;">📎 İmzalı dosya yüklendi</span>
                    @else
                        <span>İmza: —</span>
                    @endif
                </div>

                {{-- Package + extras --}}
                @if(trim((string)($row->selected_package_title ?? '')) !== '')
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span style="background:#eef2fd;border:1px solid #c9d5f0;border-radius:999px;padding:2px 10px;color:#3b5bdb;font-weight:700;font-size:var(--tx-xs);">{{ $row->selected_package_title }}</span>
                    @foreach($extras as $ex)
                        <span style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:999px;padding:2px 8px;color:var(--u-muted);font-size:var(--tx-xs);">+ {{ $ex }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Status badge --}}
            <div style="flex-shrink:0;text-align:right;">
                <span class="badge {{ $bCls }}" style="font-size:var(--tx-xs);">{{ $statusLabels[$cs] ?? $cs }}</span>
            </div>
        </div>
    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Filtre koşuluna uyan sözleşme bulunamadı.</div>
    @endforelse
</div>

@endsection
