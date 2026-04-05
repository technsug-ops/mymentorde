@extends('manager.layouts.app')
@section('title', 'Duyuru Analitik')
@section('page_title', 'Duyuru Analitik')

@section('topbar-actions')
<a href="/manager/bulletins" class="btn alt" style="font-size:12px;padding:6px 14px;">← Listeye Dön</a>
<a href="/manager/bulletins/{{ $bulletin->id }}/edit" class="btn alt" style="font-size:12px;padding:6px 14px;">Düzenle</a>
@endsection

@section('content')
@php
    $color    = \App\Models\CompanyBulletin::$categoryColors[$bulletin->category] ?? '#1e40af';
    $catLabel = \App\Models\CompanyBulletin::$categoryLabels[$bulletin->category] ?? $bulletin->category;
@endphp

{{-- Başlık Kartı --}}
<section class="panel" style="padding:20px 24px;margin-bottom:16px;border-left:4px solid {{ $color }};">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <span style="background:{{ $color }}18;color:{{ $color }};border:1px solid {{ $color }}30;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:700;">{{ $catLabel }}</span>
                @if($bulletin->is_pinned)<span style="font-size:12px;">📌</span>@endif
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--u-text);">{{ $bulletin->title }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">
                {{ $bulletin->author?->name ?? '—' }} · {{ $bulletin->published_at->format('d.m.Y H:i') }}
                @if($bulletin->expires_at) · Bitiş: {{ $bulletin->expires_at->format('d.m.Y') }} @endif
                @if(!empty($bulletin->target_roles) || !empty($bulletin->target_departments))
                    · 🎯 Hedefli
                @else
                    · 👥 Tüm Çalışanlar
                @endif
            </div>
        </div>
    </div>
</section>

{{-- KPI Satırı --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;">
    <div class="panel" style="padding:16px 20px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:var(--u-brand);">{{ $reads->count() }}</div>
        <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">Okuma</div>
    </div>
    <div class="panel" style="padding:16px 20px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:var(--u-ok);">{{ $readRate }}%</div>
        <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">Okuma Oranı ({{ $totalStaff }} kişi)</div>
    </div>
    <div class="panel" style="padding:16px 20px;text-align:center;">
        @php $totalReactions = $reactionGroups->sum(fn($g) => $g['count']); @endphp
        <div style="font-size:28px;font-weight:800;color:var(--u-warn);">{{ $totalReactions }}</div>
        <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">Reaksiyon</div>
    </div>
    <div class="panel" style="padding:16px 20px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:var(--u-muted);">{{ $totalStaff - $reads->count() }}</div>
        <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">Henüz Okumayan</div>
    </div>
</div>

{{-- Okuma Oranı Bar --}}
<section class="panel" style="padding:16px 20px;margin-bottom:16px;">
    <div style="font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Okuma Oranı</div>
    <div style="background:var(--u-bg);border-radius:999px;height:12px;overflow:hidden;">
        <div style="background:{{ $color }};height:100%;width:{{ $readRate }}%;border-radius:999px;transition:width .4s;"></div>
    </div>
    <div style="font-size:11px;color:var(--u-muted);margin-top:6px;">{{ $reads->count() }} / {{ $totalStaff }} kişi okudu</div>
</section>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    {{-- Okuma Listesi --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);font-size:13px;font-weight:700;">
            👁️ Okuyanlar ({{ $reads->count() }})
        </div>
        @if($reads->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz kimse okumadı.</div>
        @else
        <div style="max-height:460px;overflow-y:auto;">
            @foreach($reads as $r)
            <div style="padding:10px 18px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--u-text);">{{ $r->user?->name ?? '—' }}</div>
                    <div style="font-size:11px;color:var(--u-muted);">{{ $r->user?->email ?? '' }} · {{ $r->user?->role ?? '' }}</div>
                </div>
                <div style="font-size:11px;color:var(--u-muted);white-space:nowrap;">{{ $r->read_at?->format('d.m H:i') ?? '—' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    {{-- Reaksiyon Analizi --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);font-size:13px;font-weight:700;">
            ❤️ Reaksiyonlar ({{ $totalReactions }})
        </div>
        @if($reactionGroups->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz reaksiyon yok.</div>
        @else
        <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px;">
            @foreach($reactionGroups as $emoji => $data)
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:20px;">{{ $emoji }}</span>
                    <span style="font-size:14px;font-weight:700;color:var(--u-text);">{{ $data['count'] }}</span>
                </div>
                @if($totalReactions > 0)
                <div style="background:var(--u-bg);border-radius:999px;height:6px;overflow:hidden;margin-bottom:4px;">
                    <div style="background:var(--u-brand);height:100%;width:{{ round(($data['count']/$totalReactions)*100) }}%;border-radius:999px;"></div>
                </div>
                @endif
                <div style="font-size:11px;color:var(--u-muted);">{{ $data['users']->implode(', ') }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

</div>
@endsection
