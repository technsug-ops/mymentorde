@extends('platform.layouts.app')

@section('title', 'Denetim Kayitlari - MentorDE Platform')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $logs */
    /** @var array $filters */
    /** @var array $kpis */
    /** @var array $distinctEvents */
    /** @var array $distinctActors */
    /** @var array $severities */

    $hasActiveFilters = (bool) array_filter([
        $filters['event'] ?? null,
        $filters['actor_user_id'] ?? null,
        $filters['actor_email'] ?? null,
        $filters['target_type'] ?? null,
        $filters['severity'] ?? null,
        $filters['from'] ?? null,
        $filters['to'] ?? null,
        $filters['q'] ?? null,
    ]);
@endphp

@push('styles')
<style>
    .pal-filters { display: grid; grid-template-columns: 1.4fr 1fr 1fr 0.8fr 0.8fr auto auto; gap: 10px; align-items: end; }
    @media (max-width: 1300px) { .pal-filters { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px)  { .pal-filters { grid-template-columns: 1fr; } }

    .pal-row { cursor: pointer; }
    .pal-row.expanded + .pal-row-detail { display: table-row; }
    .pal-row-detail { display: none; background: var(--plat-bg); }
    .pal-row-detail td { padding: 14px 18px; border-bottom: 1px solid var(--plat-border); }
    .pal-context-json {
        background: #0a0716;
        color: #d6d2ee;
        padding: 14px 16px;
        border-radius: 8px;
        border: 1px solid var(--plat-border);
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-size: 12px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 360px;
        overflow: auto;
    }
    .pal-event-pill {
        display: inline-block;
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: var(--plat-panel-2);
        color: var(--plat-accent-2);
        border: 1px solid var(--plat-border);
        letter-spacing: .2px;
    }
    .pal-meta-line { font-size: 11px; color: var(--plat-muted); margin-top: 3px; }
    .pal-auto-toggle { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 8px; cursor: pointer; font-size: 12px; color: var(--plat-text); }
    .pal-auto-toggle.active { background: rgba(74,222,128,.14); border-color: rgba(74,222,128,.35); color: var(--plat-ok); }
    .pal-auto-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--plat-muted); }
    .pal-auto-toggle.active .pal-auto-dot { background: var(--plat-ok); box-shadow: 0 0 0 4px rgba(74,222,128,.20); animation: pal-pulse 1.4s infinite; }
    @keyframes pal-pulse { 0% { opacity: 1; } 50% { opacity: .35; } 100% { opacity: 1; } }
    .pal-empty { text-align: center; padding: 50px 20px; color: var(--plat-muted); }
</style>
@endpush

@section('content')

    {{-- Header --}}
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title">
                <x-icon name="shield" size="22" /> Denetim Kayitlari
            </h1>
            <p class="plat-page-sub">
                Tum platform owner aksiyonlari — impersonate, tier/modul degisikligi, fatura, ayar guncellemesi
                {{-- son refresh zamani --}}
                <span id="pal-last-refresh" style="margin-left: 12px; font-size: 11px;">— Son guncelleme: {{ now()->format('H:i:s') }}</span>
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" id="pal-auto-toggle" class="pal-auto-toggle" data-active="0">
                <span class="pal-auto-dot"></span>
                <span class="pal-auto-label">Otomatik (30sn)</span>
            </button>
            <button type="button" id="pal-refresh-now" class="plat-btn plat-btn-ghost">
                <x-icon name="refresh-cw" size="14" /> Yenile
            </button>
            <a href="{{ route('platform.audit-log.export', $filters) }}" class="plat-btn plat-btn-primary">
                <x-icon name="download" size="14" /> CSV indir
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="plat-grid plat-grid-4" style="margin-bottom: 22px;">
        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="activity" size="13" /> Bugun Toplam Event</div>
            <div class="plat-kpi-value">{{ number_format($kpis['total']) }}</div>
            <div class="plat-kpi-sub">son 24 saat</div>
        </div>

        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="alert-triangle" size="13" /> Kritik Event</div>
            <div class="plat-kpi-value" style="color: {{ $kpis['critical'] > 0 ? 'var(--plat-danger)' : '#fff' }};">
                {{ number_format($kpis['critical']) }}
            </div>
            <div class="plat-kpi-sub">{{ $kpis['critical'] > 0 ? 'inceleme gerek' : 'temiz' }}</div>
        </div>

        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="users" size="13" /> Aktif Aktor</div>
            <div class="plat-kpi-value">{{ number_format($kpis['unique_actors']) }}</div>
            <div class="plat-kpi-sub">bugun en az 1 aksiyon</div>
        </div>

        <div class="plat-kpi">
            <div class="plat-kpi-label"><x-icon name="trending-up" size="13" /> En Sik Event</div>
            <div class="plat-kpi-value" style="font-size: 14px; line-height: 1.3; word-break: break-word;">
                {{ $kpis['top_event'] ?? '—' }}
            </div>
            <div class="plat-kpi-sub">
                @if ($kpis['top_event_count'] > 0)
                    {{ $kpis['top_event_count'] }} kez
                @else
                    bugun veri yok
                @endif
            </div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="plat-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('platform.audit-log') }}" id="pal-filter-form">
            <div class="pal-filters">
                <div>
                    <label class="plat-form-label"><x-icon name="search" size="11" /> Arama</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="event/email/target..." class="plat-input">
                </div>

                <div>
                    <label class="plat-form-label">Event</label>
                    <select name="event" class="plat-select">
                        <option value="">Tum eventler</option>
                        @foreach ($distinctEvents as $ev)
                            <option value="{{ $ev }}" @selected($filters['event'] === $ev)>{{ $ev }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="plat-form-label">Aktor</label>
                    <select name="actor_user_id" class="plat-select">
                        <option value="">Tum aktorler</option>
                        @foreach ($distinctActors as $uid => $email)
                            <option value="{{ $uid }}" @selected((int) $filters['actor_user_id'] === (int) $uid)>{{ $email }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="plat-form-label">Severity</label>
                    <select name="severity" class="plat-select">
                        <option value="">Hepsi</option>
                        @foreach ($severities as $sev)
                            <option value="{{ $sev }}" @selected($filters['severity'] === $sev)>{{ strtoupper($sev) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="plat-form-label">Baslangic</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="plat-input">
                </div>

                <div>
                    <label class="plat-form-label">Bitis</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="plat-input">
                </div>

                <div style="display: flex; gap: 6px;">
                    <button type="submit" class="plat-btn plat-btn-primary">
                        <x-icon name="filter" size="14" /> Uygula
                    </button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('platform.audit-log') }}" class="plat-btn plat-btn-ghost">
                            <x-icon name="x" size="14" />
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="plat-card" style="padding: 0;">
        @if ($logs->isEmpty())
            <div class="pal-empty">
                <x-icon name="shield-check" size="40" />
                <div style="margin-top: 12px; font-size: 15px; color: var(--plat-text);">
                    Filtrelere uyan kayit bulunamadi.
                </div>
                <div style="margin-top: 6px; font-size: 12px;">
                    Platform owner aksiyonlari burada listelenir.
                </div>
            </div>
        @else
            <table class="plat-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Severity</th>
                        <th>Event</th>
                        <th>Aktor</th>
                        <th>Hedef</th>
                        <th style="width: 150px;">Zaman</th>
                        <th style="width: 90px; text-align: right;">Detay</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $sevClass = $log->severityBadgeClass();
                            $sevIcon  = $log->severityIcon();
                        @endphp
                        <tr class="pal-row" data-row-id="{{ $log->id }}">
                            <td>
                                <span class="plat-badge {{ $sevClass }}">
                                    <x-icon :name="$sevIcon" size="10" />
                                    {{ strtoupper($log->severity) }}
                                </span>
                            </td>
                            <td>
                                <span class="pal-event-pill">{{ $log->event }}</span>
                                <div class="pal-meta-line">{{ $log->humanEvent() }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $log->actor_email ?? '—' }}</div>
                                <div class="pal-meta-line">
                                    {{ $log->actor_role ?? '?' }}
                                    @if ($log->actor_ip) · {{ $log->actor_ip }} @endif
                                </div>
                            </td>
                            <td>
                                @if ($log->target_type)
                                    <span style="font-family: ui-monospace, monospace; font-size: 12px;">
                                        {{ $log->target_type }}@if ($log->target_id) #{{ $log->target_id }}@endif
                                    </span>
                                @else
                                    <span style="color: var(--plat-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 12px;">{{ $log->created_at?->format('d.m.Y') }}</div>
                                <div class="pal-meta-line">{{ $log->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="plat-btn plat-btn-ghost plat-btn-sm pal-expand-btn" data-target="{{ $log->id }}">
                                    <x-icon name="eye" size="12" />
                                </button>
                                <a href="{{ route('platform.audit-log.show', $log->id) }}" class="plat-btn plat-btn-ghost plat-btn-sm" title="Tam detay">
                                    <x-icon name="external-link" size="12" />
                                </a>
                            </td>
                        </tr>
                        <tr class="pal-row-detail" data-detail-id="{{ $log->id }}">
                            <td colspan="6">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                                    <div>
                                        <div class="plat-form-label">Aktor Detay</div>
                                        <div style="font-size: 12px; color: var(--plat-text);">
                                            <div><strong>User ID:</strong> {{ $log->actor_user_id ?? '—' }}</div>
                                            <div><strong>E-posta:</strong> {{ $log->actor_email ?? '—' }}</div>
                                            <div><strong>Rol:</strong> {{ $log->actor_role ?? '—' }}</div>
                                            <div><strong>IP:</strong> {{ $log->actor_ip ?? '—' }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="plat-form-label">Hedef Detay</div>
                                        <div style="font-size: 12px; color: var(--plat-text);">
                                            <div><strong>Tip:</strong> {{ $log->target_type ?? '—' }}</div>
                                            <div><strong>ID:</strong> {{ $log->target_id ?? '—' }}</div>
                                            <div><strong>Tarih:</strong> {{ $log->created_at?->format('d.m.Y H:i:s') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="plat-form-label">Context</div>
                                    <pre class="pal-context-json">{{ $log->context ? json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '— bos —' }}</pre>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($logs->hasPages())
                <div style="padding: 14px 18px; border-top: 1px solid var(--plat-border);">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    'use strict';

    // ── Inline expand ─────────────────────────────────────────────
    document.querySelectorAll('.pal-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;
            row.classList.toggle('expanded');
        });
    });
    document.querySelectorAll('.pal-expand-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = btn.getAttribute('data-target');
            var row = document.querySelector('.pal-row[data-row-id="' + id + '"]');
            if (row) row.classList.toggle('expanded');
        });
    });

    // ── Auto-refresh (30sn) ───────────────────────────────────────
    var autoBtn   = document.getElementById('pal-auto-toggle');
    var refreshBtn= document.getElementById('pal-refresh-now');
    var lastLabel = document.getElementById('pal-last-refresh');
    var autoTimer = null;

    function reloadKeepingScroll() {
        var url = new URL(window.location.href);
        url.searchParams.set('_t', Date.now());
        window.location.href = url.toString();
    }

    function updateLastRefreshLabel() {
        if (!lastLabel) return;
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        var ss = String(now.getSeconds()).padStart(2, '0');
        lastLabel.textContent = '— Son guncelleme: ' + hh + ':' + mm + ':' + ss;
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            reloadKeepingScroll();
        });
    }

    if (autoBtn) {
        autoBtn.addEventListener('click', function () {
            var active = autoBtn.getAttribute('data-active') === '1';
            if (active) {
                autoBtn.setAttribute('data-active', '0');
                autoBtn.classList.remove('active');
                if (autoTimer) clearInterval(autoTimer);
                autoTimer = null;
                try { localStorage.setItem('pal_auto_refresh', '0'); } catch (e) {}
            } else {
                autoBtn.setAttribute('data-active', '1');
                autoBtn.classList.add('active');
                autoTimer = setInterval(reloadKeepingScroll, 30000);
                try { localStorage.setItem('pal_auto_refresh', '1'); } catch (e) {}
            }
        });

        // localStorage'tan resume
        try {
            if (localStorage.getItem('pal_auto_refresh') === '1') {
                autoBtn.click();
            }
        } catch (e) {}
    }

    updateLastRefreshLabel();
})();
</script>
@endpush
