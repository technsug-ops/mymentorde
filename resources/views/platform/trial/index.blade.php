@extends('platform.layouts.app')

@section('title', 'Trial Yonetimi — Platform Owner')

@push('styles')
<style>
    /* Trial dashboard ozel stilleri */
    .trial-stage-section { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 18px 20px; margin-bottom: 18px; }
    .trial-stage-section h3 { margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }

    .trial-status-critical { background: rgba(248,113,113,.16); color: var(--plat-danger); }
    .trial-status-warning  { background: rgba(251,191,36,.16); color: var(--plat-warn); }
    .trial-status-ok       { background: rgba(74,222,128,.14); color: var(--plat-ok); }
    .trial-status-expired  { background: rgba(248,113,113,.24); color: #fca5a5; }
    .trial-status-unlimited{ background: rgba(160,155,181,.14); color: var(--plat-muted); }

    .trial-row-critical { background: rgba(248,113,113,.05); }
    .trial-row-expired  { background: rgba(248,113,113,.08); }

    .trial-actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }
    .trial-actions-cell button, .trial-actions-cell a { padding: 5px 9px; font-size: 11px; border-radius: 6px; }

    /* Modal */
    .trial-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 100; display: none; align-items: center; justify-content: center; }
    .trial-modal-backdrop.show { display: flex; }
    .trial-modal { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 24px; width: 480px; max-width: calc(100% - 32px); }
    .trial-modal h3 { margin: 0 0 16px; color: #fff; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .trial-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }
    .trial-modal-sub { font-size: 12px; color: var(--plat-muted); margin-bottom: 14px; }

    .trial-inline-form { display: inline; margin: 0; }

    /* Trend chart */
    .trial-trend-wrap { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 18px 20px; margin-bottom: 18px; }
    .trial-trend-chart { display: block; width: 100%; height: 200px; }
    .trial-trend-axis-label { fill: var(--plat-muted); font-size: 10px; }
    .trial-trend-bar { fill: var(--plat-accent); opacity: .85; }
    .trial-trend-bar:hover { opacity: 1; }
    .trial-trend-line { fill: none; stroke: var(--plat-warn); stroke-width: 2; }
    .trial-trend-dot { fill: var(--plat-warn); }

    .trial-empty { padding: 28px 16px; text-align: center; color: var(--plat-muted); font-size: 13px; }

    .trial-recent-ext { display: grid; grid-template-columns: 1fr auto auto auto; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--plat-border); font-size: 12px; align-items: center; }
    .trial-recent-ext:last-child { border-bottom: none; }
    .trial-recent-ext-name { font-weight: 700; color: #fff; }
    .trial-recent-ext-meta { color: var(--plat-muted); font-size: 11px; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title"><x-icon name="hourglass" size="22" /> Trial Yonetimi</h1>
        <p class="plat-page-sub">Aktif trial'lar, conversion, nurture mail kampanyalari</p>
    </div>
    @if ($nurtureCount > 0)
        <form method="POST" action="{{ route('platform.trial.nurture-send') }}" class="trial-inline-form">
            @csrf
            <button type="submit" class="plat-btn plat-btn-primary">
                <x-icon name="mail" size="16" /> Bekleyen {{ $nurtureCount }} Nurture Mail'i Gonder
            </button>
        </form>
    @endif
</div>

{{-- KPI ROW --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:20px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="clock" size="12" /> Aktif Trial</div>
        <div class="plat-kpi-value">{{ number_format($activeCount) }}</div>
        <div class="plat-kpi-sub">Subscription tier = trial</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-ok);">
        <div class="plat-kpi-label" style="color:var(--plat-ok);"><x-icon name="trending-up" size="12" /> Bu Ay Conversion</div>
        <div class="plat-kpi-value">
            {{ $stats['month_rate'] !== null ? number_format($stats['month_rate'], 1) . '%' : '—' }}
        </div>
        <div class="plat-kpi-sub">
            {{ $stats['month_converted'] }} / {{ $stats['month_trials'] }} trial -> paid
        </div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-warn);">
        <div class="plat-kpi-label" style="color:var(--plat-warn);"><x-icon name="calendar-days" size="12" /> 7 Gun Icinde Bitecek</div>
        <div class="plat-kpi-value">{{ number_format($expiring3->count() + $expiring7->count()) }}</div>
        <div class="plat-kpi-sub">{{ $expiring3->count() }} kritik (&le; 3 gun)</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-danger);">
        <div class="plat-kpi-label" style="color:var(--plat-danger);"><x-icon name="circle-alert" size="12" /> Expired (Overdue)</div>
        <div class="plat-kpi-value">{{ number_format($expiredCount) }}</div>
        <div class="plat-kpi-sub">Hala trial tier, bitis tarihi gecmis</div>
    </div>
</div>

{{-- KIRMIZI UYARI — EXPIRING SOON GRUPLU --}}
@if ($expiring3->count() > 0 || $expiring7->count() > 0 || $expiring14->count() > 0)
<div class="trial-stage-section" style="border-left: 3px solid var(--plat-danger);">
    <h3><x-icon name="circle-alert" size="16" /> Yakinda Bitecek Trial'lar</h3>

    @if ($expiring3->count() > 0)
        <div style="margin-bottom: 14px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--plat-danger); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px;">
                Kritik (&le; 3 gun) — {{ $expiring3->count() }}
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach ($expiring3 as $c)
                    <span class="plat-badge trial-status-critical">
                        {{ $c->name }} · {{ $c->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($expiring7->count() > 0)
        <div style="margin-bottom: 14px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--plat-warn); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px;">
                Yakin (4-7 gun) — {{ $expiring7->count() }}
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach ($expiring7 as $c)
                    <span class="plat-badge trial-status-warning">
                        {{ $c->name }} · {{ $c->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($expiring14->count() > 0)
        <div>
            <div style="font-size: 12px; font-weight: 700; color: var(--plat-info); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px;">
                Onumuzdeki 14 gun — {{ $expiring14->count() }}
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach ($expiring14 as $c)
                    <span class="plat-badge plat-badge-trial">
                        {{ $c->name }} · {{ $c->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif

{{-- CONVERSION TREND CHART --}}
<div class="trial-trend-wrap">
    <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
        <x-icon name="trending-up" size="16" /> Conversion Rate Trend (Son 12 Ay)
    </h3>

    @php
        $trend       = $stats['trend'] ?? [];
        $chartW      = 1200;
        $chartH      = 200;
        $padL        = 36;
        $padR        = 16;
        $padT        = 16;
        $padB        = 36;
        $innerW      = $chartW - $padL - $padR;
        $innerH      = $chartH - $padT - $padB;
        $maxTrials   = max(1, max(array_column($trend, 'trials') ?: [1]));
        $maxRate     = 100; // % y eksen
        $colWidth    = count($trend) > 0 ? $innerW / count($trend) : 0;
        $barWidth    = $colWidth * 0.55;
        $linePoints  = [];
    @endphp

    @if (count($trend) === 0)
        <div class="trial-empty">Henuz veri yok.</div>
    @else
        <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="trial-trend-chart" preserveAspectRatio="none">
            {{-- Y axis gridlines (rate %) --}}
            @for ($i = 0; $i <= 4; $i++)
                @php $y = $padT + ($innerH * ($i / 4)); $val = 100 - ($i * 25); @endphp
                <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $chartW - $padR }}" y2="{{ $y }}" stroke="#2e2848" stroke-width="1" stroke-dasharray="2,3"/>
                <text x="6" y="{{ $y + 3 }}" class="trial-trend-axis-label">{{ $val }}%</text>
            @endfor

            {{-- Bars: trials count + line: rate --}}
            @foreach ($trend as $i => $t)
                @php
                    $cx        = $padL + ($colWidth * $i) + ($colWidth / 2);
                    $barH      = $maxTrials > 0 ? ($t['trials'] / $maxTrials) * $innerH * 0.7 : 0;
                    $barX      = $cx - ($barWidth / 2);
                    $barY      = $padT + $innerH - $barH;
                    $rateY     = $padT + $innerH - (($t['rate'] / $maxRate) * $innerH);
                    $linePoints[] = "{$cx},{$rateY}";
                @endphp
                <rect x="{{ $barX }}" y="{{ $barY }}" width="{{ $barWidth }}" height="{{ $barH }}" class="trial-trend-bar">
                    <title>{{ $t['label'] }}: {{ $t['trials'] }} trial, {{ $t['converted'] }} converted ({{ $t['rate'] }}%)</title>
                </rect>
                <text x="{{ $cx }}" y="{{ $chartH - 14 }}" class="trial-trend-axis-label" text-anchor="middle">{{ $t['label'] }}</text>
            @endforeach

            {{-- Rate line --}}
            @if (count($linePoints) > 1)
                <polyline points="{{ implode(' ', $linePoints) }}" class="trial-trend-line"/>
                @foreach ($trend as $i => $t)
                    @php
                        $cx    = $padL + ($colWidth * $i) + ($colWidth / 2);
                        $rateY = $padT + $innerH - (($t['rate'] / $maxRate) * $innerH);
                    @endphp
                    <circle cx="{{ $cx }}" cy="{{ $rateY }}" r="3" class="trial-trend-dot">
                        <title>{{ $t['label'] }}: {{ $t['rate'] }}% rate</title>
                    </circle>
                @endforeach
            @endif
        </svg>

        <div style="display: flex; gap: 18px; margin-top: 8px; font-size: 11px; color: var(--plat-muted);">
            <span><span style="display:inline-block;width:10px;height:10px;background:var(--plat-accent);border-radius:2px;margin-right:5px;"></span>Yeni trial sayisi</span>
            <span><span style="display:inline-block;width:18px;height:2px;background:var(--plat-warn);vertical-align:middle;margin-right:5px;"></span>Conversion rate (%)</span>
            <span style="margin-left:auto;">Yillik: {{ $stats['year_rate'] !== null ? number_format($stats['year_rate'], 1) . '%' : '—' }} ({{ $stats['year_converted'] }}/{{ $stats['year_trials'] }})</span>
        </div>
    @endif
</div>

{{-- ANA TABLO — TUM AKTIF TRIAL'LAR --}}
<div class="trial-stage-section" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--plat-border);">
        <h3 style="margin:0;"><x-icon name="building-2" size="16" /> Tum Aktif Trial Sirketler ({{ $rows->count() }})</h3>
    </div>
    @if ($rows->count() === 0)
        <div class="trial-empty">Aktif trial yok — tum musteriler paid tier'inda.</div>
    @else
    <table class="plat-table">
        <thead>
            <tr>
                <th>Sirket</th>
                <th>Trial Baslangic</th>
                <th>Trial Bitis</th>
                <th>Kalan</th>
                <th>Durum</th>
                <th>Nurture</th>
                <th style="text-align:right;">Islemler</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php
                    $c        = $row['company'];
                    $daysLeft = $row['days_left'];
                    $status   = $row['status'];
                    $rowClass = $status === 'expired' ? 'trial-row-expired' : ($status === 'critical' ? 'trial-row-critical' : '');
                    $sentCount = is_array($c->nurture_emails_sent) ? count($c->nurture_emails_sent) : 0;
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <a href="{{ route('platform.companies.show', $c->id) }}" style="font-weight:700;color:#fff;">{{ $c->name }}</a>
                        <div style="font-size:11px;color:var(--plat-muted);">{{ $c->code ?? '—' }}</div>
                    </td>
                    <td>
                        {{ $c->trial_started_at ? \Carbon\Carbon::parse($c->trial_started_at)->format('d.m.Y') : '—' }}
                    </td>
                    <td>
                        {{ $c->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td>
                        @if ($daysLeft === null)
                            <span class="plat-badge trial-status-unlimited">Sinirsiz</span>
                        @elseif ($daysLeft < 0)
                            <span class="plat-badge trial-status-expired">{{ abs($daysLeft) }} gun gecti</span>
                        @else
                            <strong style="color:#fff;">{{ $daysLeft }} gun</strong>
                        @endif
                    </td>
                    <td>
                        @switch($status)
                            @case('critical')  <span class="plat-badge trial-status-critical">Kritik</span> @break
                            @case('warning')   <span class="plat-badge trial-status-warning">Uyari</span> @break
                            @case('ok')        <span class="plat-badge trial-status-ok">Iyi</span> @break
                            @case('expired')   <span class="plat-badge trial-status-expired">Bitti</span> @break
                            @case('unlimited') <span class="plat-badge trial-status-unlimited">—</span> @break
                        @endswitch
                    </td>
                    <td>
                        <span title="Gonderilen nurture mail sayisi" style="font-size:12px;color:var(--plat-muted);">
                            <x-icon name="mail" size="11" /> {{ $sentCount }}/4
                        </span>
                    </td>
                    <td>
                        <div class="trial-actions-cell" style="justify-content:flex-end;">
                            <button type="button"
                                    class="plat-btn plat-btn-ghost plat-btn-sm js-open-extend"
                                    data-company-id="{{ $c->id }}"
                                    data-company-name="{{ $c->name }}"
                                    title="Trial uzat">
                                <x-icon name="clock" size="12" /> Uzat
                            </button>
                            <button type="button"
                                    class="plat-btn plat-btn-primary plat-btn-sm js-open-convert"
                                    data-company-id="{{ $c->id }}"
                                    data-company-name="{{ $c->name }}"
                                    title="Paid tier'a yukselt">
                                <x-icon name="party-popper" size="12" /> Convert
                            </button>
                            <button type="button"
                                    class="plat-btn plat-btn-ghost plat-btn-sm js-open-nurture"
                                    data-company-id="{{ $c->id }}"
                                    data-company-name="{{ $c->name }}"
                                    title="Nurture mail gonder">
                                <x-icon name="mail" size="12" />
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- SON UZATMALAR --}}
@if ($recentExtensions->count() > 0)
<div class="trial-stage-section">
    <h3><x-icon name="clock" size="16" /> Son Manuel Uzatmalar</h3>
    @foreach ($recentExtensions as $ext)
        <div class="trial-recent-ext">
            <div>
                <span class="trial-recent-ext-name">{{ $ext->company?->name ?? '—' }}</span>
                @if ($ext->reason)
                    <div class="trial-recent-ext-meta">"{{ \Illuminate\Support\Str::limit($ext->reason, 80) }}"</div>
                @endif
            </div>
            <div class="trial-recent-ext-meta">+{{ $ext->extension_days }} gun</div>
            <div class="trial-recent-ext-meta">{{ $ext->grantedBy?->name ?? 'sistem' }}</div>
            <div class="trial-recent-ext-meta">{{ $ext->created_at?->format('d.m.Y H:i') }}</div>
        </div>
    @endforeach
</div>
@endif

{{-- ── MODAL: EXTEND ───────────────────────────────────────────────── --}}
<div class="trial-modal-backdrop" id="modal-extend">
    <div class="trial-modal">
        <h3><x-icon name="clock" size="18" /> Trial Uzat</h3>
        <div class="trial-modal-sub" id="extend-company-label">—</div>
        <form method="POST" action="" id="form-extend">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label" for="extend_days">Kac gun uzatilsin?</label>
                <select name="extend_days" id="extend_days" class="plat-select" required>
                    <option value="7">7 gun</option>
                    <option value="14" selected>14 gun</option>
                    <option value="30">30 gun</option>
                    <option value="60">60 gun</option>
                    <option value="90">90 gun</option>
                </select>
            </div>
            <div class="plat-form-group">
                <label class="plat-form-label" for="reason">Sebep (opsiyonel)</label>
                <textarea name="reason" id="reason" class="plat-textarea" rows="3" maxlength="500"
                          placeholder="Ornek: Musteri 2 hafta uzatma talep etti, demo gorusmesi 25.06'da..."></textarea>
            </div>
            <div class="trial-modal-actions">
                <button type="button" class="plat-btn plat-btn-ghost js-close-modal" data-modal="modal-extend">Vazgec</button>
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="check" size="14" /> Uzat</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: CONVERT ──────────────────────────────────────────────── --}}
<div class="trial-modal-backdrop" id="modal-convert">
    <div class="trial-modal">
        <h3><x-icon name="party-popper" size="18" /> Paid Tier'a Yukselt</h3>
        <div class="trial-modal-sub" id="convert-company-label">—</div>
        <form method="POST" action="" id="form-convert">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label" for="tier">Hangi tier?</label>
                <select name="tier" id="tier" class="plat-select" required>
                    <option value="basic">Basic — 49€/ay</option>
                    <option value="gold" selected>Gold — 149€/ay</option>
                    <option value="premium">Premium — 299€/ay</option>
                </select>
            </div>
            <div style="font-size:12px;color:var(--plat-muted);background:var(--plat-panel-2);padding:10px 12px;border-radius:8px;">
                <x-icon name="info" size="12" /> Trial bitis tarihi temizlenir, mrr_eur tier'a gore set edilir,
                ilk donusum tarihi (converted_to_paid_at) bos ise simdiki ana yazilir.
            </div>
            <div class="trial-modal-actions">
                <button type="button" class="plat-btn plat-btn-ghost js-close-modal" data-modal="modal-convert">Vazgec</button>
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="check" size="14" /> Yukselt</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: NURTURE ──────────────────────────────────────────────── --}}
<div class="trial-modal-backdrop" id="modal-nurture">
    <div class="trial-modal">
        <h3><x-icon name="mail" size="18" /> Nurture Mail Gonder</h3>
        <div class="trial-modal-sub" id="nurture-company-label">—</div>
        <form method="POST" action="{{ route('platform.trial.nurture-send') }}" id="form-nurture">
            @csrf
            <input type="hidden" name="company_id" id="nurture_company_id" value="">
            <div class="plat-form-group">
                <label class="plat-form-label" for="stage">Hangi asama?</label>
                <select name="stage" id="stage" class="plat-select" required>
                    <option value="day3">Day 3 — Hos geldin + ilk adimlar</option>
                    <option value="day7" selected>Day 7 — Yari yolda + ozellik ipuclari</option>
                    <option value="day14">Day 14 — Bitmek uzere + tier oneri</option>
                    <option value="day17">Day 17 — Post-trial geri don</option>
                </select>
            </div>
            <div style="font-size:12px;color:var(--plat-muted);background:var(--plat-panel-2);padding:10px 12px;border-radius:8px;">
                <x-icon name="info" size="12" /> Mail, sirketin manager rolundeki ilk kullanicisina
                gider. Manager yoksa billing_email kullanilir. Gonderim sonrasi
                nurture_emails_sent JSON'una kaydedilir.
            </div>
            <div class="trial-modal-actions">
                <button type="button" class="plat-btn plat-btn-ghost js-close-modal" data-modal="modal-nurture">Vazgec</button>
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="check" size="14" /> Gonder</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
{{-- CSP-safe: nonce'lu script blogu icinde addEventListener — onclick=... CSP Level 3'te bloklanir --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    'use strict';

    // Modal helpers
    function openModal(id) { document.getElementById(id)?.classList.add('show'); }
    function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

    // Backdrop click -> close
    document.querySelectorAll('.trial-modal-backdrop').forEach(function(bd) {
        bd.addEventListener('click', function(e) {
            if (e.target === bd) bd.classList.remove('show');
        });
    });

    // Close buttons
    document.querySelectorAll('.js-close-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            closeModal(btn.getAttribute('data-modal'));
        });
    });

    // EXTEND
    document.querySelectorAll('.js-open-extend').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-company-id');
            var name = btn.getAttribute('data-company-name');
            var form = document.getElementById('form-extend');
            form.setAttribute('action', '/platform/trial/' + id + '/extend');
            document.getElementById('extend-company-label').textContent = name;
            openModal('modal-extend');
        });
    });

    // CONVERT
    document.querySelectorAll('.js-open-convert').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-company-id');
            var name = btn.getAttribute('data-company-name');
            var form = document.getElementById('form-convert');
            form.setAttribute('action', '/platform/trial/' + id + '/convert');
            document.getElementById('convert-company-label').textContent = name;
            openModal('modal-convert');
        });
    });

    // NURTURE — tek company icin
    document.querySelectorAll('.js-open-nurture').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-company-id');
            var name = btn.getAttribute('data-company-name');
            document.getElementById('nurture_company_id').value = id;
            document.getElementById('nurture-company-label').textContent = name;
            openModal('modal-nurture');
        });
    });

    // Esc tusu -> tum modal'lar kapansin
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.trial-modal-backdrop.show').forEach(function(bd) {
                bd.classList.remove('show');
            });
        }
    });
})();
</script>
@endpush
