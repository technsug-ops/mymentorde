@extends('platform.layouts.app')

@section('title', 'Audit Event #' . $log->id . ' - DGmarkt Platform')

@php
    /** @var \App\Models\PlatformAuditLog $log */
    /** @var array $related */
    $sevClass = $log->severityBadgeClass();
    $sevIcon  = $log->severityIcon();
@endphp

@push('styles')
<style>
    .pal-show-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
    @media (max-width: 900px) { .pal-show-grid { grid-template-columns: 1fr; } }
    .pal-show-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--plat-border); font-size: 13px; }
    .pal-show-row:last-child { border-bottom: none; }
    .pal-show-row .lbl { color: var(--plat-muted); font-weight: 600; }
    .pal-show-row .val { color: var(--plat-text); font-family: ui-monospace, monospace; font-size: 12px; max-width: 60%; text-align: right; word-break: break-all; }
    .pal-ctx-json {
        background: #0a0716;
        color: #d6d2ee;
        padding: 16px 18px;
        border-radius: 10px;
        border: 1px solid var(--plat-border);
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-size: 12.5px;
        line-height: 1.7;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 540px;
        overflow: auto;
    }
    .pal-related-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px;
        border: 1px solid var(--plat-border);
        border-radius: 8px;
        margin-bottom: 8px;
        background: var(--plat-panel-2);
        transition: border-color .12s;
    }
    .pal-related-item:hover { border-color: var(--plat-accent); }
    .pal-related-item a { color: var(--plat-text); }
</style>
@endpush

@section('content')

    {{-- Header --}}
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title">
                <x-icon name="shield" size="20" /> Audit Event #{{ $log->id }}
            </h1>
            <p class="plat-page-sub">
                <span class="plat-badge {{ $sevClass }}" style="margin-right: 8px;">
                    <x-icon :name="$sevIcon" size="10" />
                    {{ strtoupper($log->severity) }}
                </span>
                {{ $log->humanEvent() }} · {{ $log->created_at?->format('d.m.Y H:i:s') }}
            </p>
        </div>
        <div>
            <a href="{{ route('platform.audit-log') }}" class="plat-btn plat-btn-ghost">
                <x-icon name="arrow-left" size="14" /> Listeye don
            </a>
        </div>
    </div>

    {{-- Event header card --}}
    <div class="plat-card" style="margin-bottom: 18px;">
        <h3 class="plat-card-title"><x-icon name="info" size="16" /> Event</h3>
        <div class="pal-show-row">
            <span class="lbl">Event Key</span>
            <span class="val" style="font-weight: 700; color: var(--plat-accent-2);">{{ $log->event }}</span>
        </div>
        <div class="pal-show-row">
            <span class="lbl">Severity</span>
            <span class="val">{{ $log->severity }}</span>
        </div>
        <div class="pal-show-row">
            <span class="lbl">Tarih</span>
            <span class="val">{{ $log->created_at?->toIso8601String() }}</span>
        </div>
    </div>

    {{-- Actor + Target --}}
    <div class="pal-show-grid">
        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="user" size="16" /> Aktor</h3>
            <div class="pal-show-row">
                <span class="lbl">Ad</span>
                <span class="val">{{ $log->actor?->name ?? 'Sistem' }}</span>
            </div>
            <div class="pal-show-row">
                <span class="lbl">User ID</span>
                <span class="val">{{ $log->actor_user_id ?? '—' }}</span>
            </div>
            <div class="pal-show-row">
                <span class="lbl">E-posta</span>
                <span class="val">{{ $log->actor_email ?? '—' }}</span>
            </div>
            <div class="pal-show-row">
                <span class="lbl">Rol</span>
                <span class="val">{{ $log->actor_role ?? '—' }}</span>
            </div>
            <div class="pal-show-row">
                <span class="lbl">IP</span>
                <span class="val">{{ $log->actor_ip ?? '—' }}</span>
            </div>
        </div>

        <div class="plat-card">
            <h3 class="plat-card-title"><x-icon name="target" size="16" /> Hedef</h3>
            <div class="pal-show-row">
                <span class="lbl">Tip</span>
                <span class="val">{{ $log->target_type ?? '—' }}</span>
            </div>
            <div class="pal-show-row">
                <span class="lbl">ID</span>
                <span class="val">{{ $log->target_id ?? '—' }}</span>
            </div>
            @if ($log->target_type === 'company' && $log->target_id)
                <div class="pal-show-row">
                    <span class="lbl">Goruntule</span>
                    <span class="val">
                        <a href="{{ route('platform.companies.show', $log->target_id) }}">
                            <x-icon name="external-link" size="12" /> Company sayfasi
                        </a>
                    </span>
                </div>
            @endif
            @if ($log->target_type === 'invoice' && $log->target_id)
                <div class="pal-show-row">
                    <span class="lbl">Goruntule</span>
                    <span class="val">
                        <a href="{{ route('platform.billing.show', $log->target_id) }}">
                            <x-icon name="external-link" size="12" /> Fatura sayfasi
                        </a>
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Context JSON --}}
    <div class="plat-card" style="margin-bottom: 18px;">
        <h3 class="plat-card-title"><x-icon name="file-text" size="16" /> Context (JSON)</h3>
        <pre class="pal-ctx-json">{{ $log->context ? json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '— bos —' }}</pre>
    </div>

    {{-- Related events --}}
    @if (!empty($related))
        <div class="plat-card">
            <h3 class="plat-card-title">
                <x-icon name="history" size="16" />
                Iliskili Eventler ({{ count($related) }})
                <span class="plat-card-sub" style="font-weight: 500; margin-left: 8px;">
                    Ayni aktor, +/- 5 dakika
                </span>
            </h3>

            @foreach ($related as $r)
                <div class="pal-related-item">
                    <div>
                        <span class="plat-badge {{ $r->severityBadgeClass() }}" style="font-size: 10px;">
                            {{ strtoupper($r->severity) }}
                        </span>
                        <span style="font-family: ui-monospace, monospace; font-size: 12px; margin-left: 8px; color: var(--plat-accent-2); font-weight: 700;">
                            {{ $r->event }}
                        </span>
                        <span style="margin-left: 10px; font-size: 11px; color: var(--plat-muted);">
                            {{ $r->humanEvent() }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <span style="font-size: 11px; color: var(--plat-muted);">
                            {{ $r->created_at?->format('H:i:s') }}
                        </span>
                        <a href="{{ route('platform.audit-log.show', $r->id) }}" class="plat-btn plat-btn-ghost plat-btn-sm">
                            <x-icon name="external-link" size="12" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection
